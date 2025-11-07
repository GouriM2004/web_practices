<?php

namespace Services;

class StreakService
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Calculates updated streak given user_id and goal_id; transactional update of goal_user_meta expected
    public function updateStreak(int $userId, int $goalId, string $date): array
    {
        // returns ['current' => int, 'longest' => int]
        $d = (new \DateTime($date))->format('Y-m-d');

        $stmt = $this->pdo->prepare('SELECT id, current_streak, longest_streak, last_checkin FROM goal_user_meta WHERE user_id = ? AND goal_id = ? FOR UPDATE');
        $started = false;
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $started = true;
            }
            $stmt->execute([$userId, $goalId]);
            $meta = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$meta) {
                $insert = $this->pdo->prepare('INSERT INTO goal_user_meta (user_id, goal_id, current_streak, longest_streak, last_checkin) VALUES (?, ?, ?, ?, ?)');
                $insert->execute([$userId, $goalId, 1, 1, $d]);
                $res = ['current' => 1, 'longest' => 1];
                if ($started) $this->pdo->commit();
                return $res;
            }

            $last = $meta['last_checkin'];
            if ($last === $d) {
                $res = ['current' => (int)$meta['current_streak'], 'longest' => (int)$meta['longest_streak']];
                if ($started) $this->pdo->commit();
                return $res;
            }

            $yesterday = (new \DateTime($d))->modify('-1 day')->format('Y-m-d');
            if ($last === $yesterday) {
                $current = (int)$meta['current_streak'] + 1;
            } else {
                $current = 1;
            }
            $longest = max($current, (int)$meta['longest_streak']);

            $upd = $this->pdo->prepare('UPDATE goal_user_meta SET current_streak = ?, longest_streak = ?, last_checkin = ? WHERE id = ?');
            $upd->execute([$current, $longest, $d, $meta['id']]);

            $res = ['current' => $current, 'longest' => $longest];
            if ($started) $this->pdo->commit();
            return $res;
        } catch (\Exception $e) {
            if ($started && $this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }
}
