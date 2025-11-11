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

    /**
     * Recompute streaks for a user+goal from historical checkins.
     * Updates goal_user_meta with computed current_streak, longest_streak and last_checkin.
     * Returns ['current' => int, 'longest' => int, 'last_checkin' => 'YYYY-MM-DD'|null]
     */
    public function computeAndStoreStreaks(int $userId, int $goalId): array
    {
        // fetch distinct dates
        $stmt = $this->pdo->prepare('SELECT DISTINCT date FROM checkins WHERE goal_id = ? AND user_id = ? ORDER BY date ASC');
        $stmt->execute([$goalId, $userId]);
        $rows = $stmt->fetchAll(
            \PDO::FETCH_COLUMN
        );

        if (!$rows) {
            // remove meta if exists or set zeros
            $stmtDel = $this->pdo->prepare('DELETE FROM goal_user_meta WHERE goal_id = ? AND user_id = ?');
            $stmtDel->execute([$goalId, $userId]);
            return ['current' => 0, 'longest' => 0, 'last_checkin' => null];
        }

        // compute longest streak
        $msDay = 24 * 60 * 60 * 1000;
        $longest = 0;
        $current = 0;
        $prev = null;
        foreach ($rows as $d) {
            if ($prev === null) {
                $current = 1;
            } else {
                $prevDate = new \DateTime($prev);
                $curDate = new \DateTime($d);
                $diff = (int)$curDate->diff($prevDate)->format('%a');
                if ($diff === 1) {
                    $current += 1;
                } else {
                    if ($current > $longest) $longest = $current;
                    $current = 1;
                }
            }
            $prev = $d;
        }
        if ($current > $longest) $longest = $current;

        // compute current streak ending at most recent checkin
        $last = end($rows);
        $curStreak = 0;
        $dt = new \DateTime($last);
        // walk back from last contiguous dates
        while (true) {
            $key = $dt->format('Y-m-d');
            if (in_array($key, $rows, true)) {
                $curStreak++;
                $dt->modify('-1 day');
            } else {
                break;
            }
        }

        // upsert into goal_user_meta
        $stmtChk = $this->pdo->prepare('SELECT id FROM goal_user_meta WHERE user_id = ? AND goal_id = ?');
        $stmtChk->execute([$userId, $goalId]);
        $exists = $stmtChk->fetchColumn();
        if ($exists) {
            $stmtUpd = $this->pdo->prepare('UPDATE goal_user_meta SET current_streak = ?, longest_streak = ?, last_checkin = ?, updated_at = NOW() WHERE id = ?');
            $stmtUpd->execute([$curStreak, $longest, $last, $exists]);
        } else {
            $stmtIns = $this->pdo->prepare('INSERT INTO goal_user_meta (user_id, goal_id, current_streak, longest_streak, last_checkin) VALUES (?, ?, ?, ?, ?)');
            $stmtIns->execute([$userId, $goalId, $curStreak, $longest, $last]);
        }

        return ['current' => (int)$curStreak, 'longest' => (int)$longest, 'last_checkin' => $last];
    }
}
