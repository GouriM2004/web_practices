<?php
// includes/Helpers/Calculator.php
require_once __DIR__ . '/../Database.php';

class Calculator
{
    private $db;
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function computeBalances($group_id)
    {
        $members = [];
        $stmt = $this->db->prepare('SELECT u.id, u.name FROM users u JOIN group_members gm ON u.id = gm.user_id WHERE gm.group_id = ?');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $balances = [];
        foreach ($members as $m) $balances[$m['id']] = 0.0;

        $stmt = $this->db->prepare('SELECT id, amount, paid_by FROM expenses WHERE group_id = ?');
        $stmt->bind_param('i', $group_id);
        $stmt->execute();
        $expenses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($expenses as $exp) {
            $eid = $exp['id'];
            $amt = (float)$exp['amount'];
            $paid_by = intval($exp['paid_by']);
            if (isset($balances[$paid_by])) $balances[$paid_by] += $amt;

            $stmt2 = $this->db->prepare('SELECT user_id, share_amount FROM expense_shares WHERE expense_id = ?');
            $stmt2->bind_param('i', $eid);
            $stmt2->execute();
            $shares = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt2->close();
            foreach ($shares as $s) {
                $uid = intval($s['user_id']);
                $share = (float)$s['share_amount'];
                if (isset($balances[$uid])) $balances[$uid] -= $share;
            }
        }
        // Apply expense-level payments (partial payments recorded against specific expenses)
        $stmtPay = $this->db->prepare('SELECT ep.payer_id, ep.receiver_id, ep.amount FROM expense_payments ep JOIN expenses e ON ep.expense_id = e.id WHERE e.group_id = ?');
        if ($stmtPay) {
            $stmtPay->bind_param('i', $group_id);
            $stmtPay->execute();
            $payments = $stmtPay->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtPay->close();
            foreach ($payments as $pmt) {
                $p = intval($pmt['payer_id']);
                $r = intval($pmt['receiver_id']);
                $a = (float)$pmt['amount'];
                if (isset($balances[$p])) $balances[$p] += $a; // payer paid some of their debt
                if (isset($balances[$r])) $balances[$r] -= $a; // receiver received money
            }
        }

        // Apply recorded settlements (group-level settlements)
        $stmt3 = $this->db->prepare('SELECT payer_id, receiver_id, amount FROM settlements WHERE group_id = ?');
        if ($stmt3) {
            $stmt3->bind_param('i', $group_id);
            $stmt3->execute();
            $sett = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt3->close();
            foreach ($sett as $s) {
                $p = intval($s['payer_id']);
                $r = intval($s['receiver_id']);
                $a = (float)$s['amount'];
                if (isset($balances[$p])) $balances[$p] += $a; // payer paid, so their owed decreases (increase balance)
                if (isset($balances[$r])) $balances[$r] -= $a; // receiver received money, so their receivable decreases
            }
        }

        foreach ($balances as $k => $v) $balances[$k] = round($v, 2);
        return $balances;
    }

    /**
     * Return components of a user's balance: total paid, total share owed, payments made/received
     */
    public function getUserBalanceComponents($group_id, $user_id)
    {
        $components = [
            'total_paid' => 0.0,
            'total_share' => 0.0,
            'payments_made' => 0.0,
            'payments_received' => 0.0,
            'net' => 0.0,
        ];
        // total paid by user (expenses they paid)
        $stmt = $this->db->prepare('SELECT SUM(amount) as s FROM expenses WHERE group_id = ? AND paid_by = ?');
        $stmt->bind_param('ii', $group_id, $user_id);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $components['total_paid'] = (float)($r['s'] ?? 0);

        // total share owed by user across expense_shares
        $stmt2 = $this->db->prepare('SELECT SUM(share_amount) as s FROM expense_shares es JOIN expenses e ON es.expense_id = e.id WHERE e.group_id = ? AND es.user_id = ?');
        $stmt2->bind_param('ii', $group_id, $user_id);
        $stmt2->execute();
        $r2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $components['total_share'] = (float)($r2['s'] ?? 0);

        // payments made (expense_payments where user is payer)
        $stmt3 = $this->db->prepare('SELECT SUM(ep.amount) as s FROM expense_payments ep JOIN expenses e ON ep.expense_id = e.id WHERE e.group_id = ? AND ep.payer_id = ?');
        $stmt3->bind_param('ii', $group_id, $user_id);
        $stmt3->execute();
        $r3 = $stmt3->get_result()->fetch_assoc();
        $stmt3->close();
        $components['payments_made'] = (float)($r3['s'] ?? 0);

        // payments received
        $stmt4 = $this->db->prepare('SELECT SUM(ep.amount) as s FROM expense_payments ep JOIN expenses e ON ep.expense_id = e.id WHERE e.group_id = ? AND ep.receiver_id = ?');
        $stmt4->bind_param('ii', $group_id, $user_id);
        $stmt4->execute();
        $r4 = $stmt4->get_result()->fetch_assoc();
        $stmt4->close();
        $components['payments_received'] = (float)($r4['s'] ?? 0);

        $components['net'] = round($components['total_paid'] - $components['total_share'] + $components['payments_made'] - $components['payments_received'], 2);
        return $components;
    }

    // alias for settleBalances to make intent clearer
    public function simplifyDebts($balances)
    {
        return $this->settleBalances($balances);
    }

    public function settleBalances($balances)
    {
        $receivers = [];
        $payers = [];
        foreach ($balances as $uid => $amt) {
            if ($amt > 0.01) $receivers[] = ['id' => $uid, 'amt' => $amt];
            elseif ($amt < -0.01) $payers[] = ['id' => $uid, 'amt' => abs($amt)];
        }
        usort($receivers, function ($a, $b) {
            return $b['amt'] <=> $a['amt'];
        });
        usort($payers, function ($a, $b) {
            return $b['amt'] <=> $a['amt'];
        });
        $i = 0;
        $j = 0;
        $tx = [];
        while ($i < count($payers) && $j < count($receivers)) {
            $pay = min($payers[$i]['amt'], $receivers[$j]['amt']);
            $tx[] = ['from' => $payers[$i]['id'], 'to' => $receivers[$j]['id'], 'amount' => round($pay, 2)];
            $payers[$i]['amt'] -= $pay;
            $receivers[$j]['amt'] -= $pay;
            if ($payers[$i]['amt'] <= 0.01) $i++;
            if ($receivers[$j]['amt'] <= 0.01) $j++;
        }
        return $tx;
    }
}
