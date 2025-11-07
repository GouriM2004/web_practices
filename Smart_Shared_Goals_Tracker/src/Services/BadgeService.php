<?php

namespace Services;

class BadgeService
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    protected function ensureBadge(string $slug, string $title, string $description = '', string $icon = null)
    {
        $stmt = $this->pdo->prepare('SELECT id, slug, title FROM badges WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row) return $row;
        $ins = $this->pdo->prepare('INSERT INTO badges (slug, title, description, icon) VALUES (?, ?, ?, ?)');
        $ins->execute([$slug, $title, $description, $icon]);
        $id = (int)$this->pdo->lastInsertId();
        return ['id' => $id, 'slug' => $slug, 'title' => $title];
    }

    // Evaluate badge rules for a user & goal. Returns array of awarded badges.
    public function evaluate(int $userId, int $goalId): array
    {
        $awarded = [];

        // ensure badge catalog rows exist
        $this->ensureBadge('first_checkin', 'First Check-in', 'Awarded when you complete your first check-in');
        $this->ensureBadge('7_day_streak', '7-day Streak', 'Keep a 7-day streak');
        $this->ensureBadge('30_day_streak', '30-day Streak', 'Keep a 30-day streak');

        // helper to check existing user_badge
        $hasBadge = function ($slug) use ($userId, $goalId) {
            $stmt = $this->pdo->prepare('SELECT ub.id FROM user_badges ub JOIN badges b ON ub.badge_id = b.id WHERE ub.user_id = ? AND b.slug = ? AND (ub.goal_id = ? OR ub.goal_id IS NULL)');
            $stmt->execute([$userId, $slug, $goalId]);
            return (bool)$stmt->fetchColumn();
        };

        // 1) first_checkin: if user has at least one checkin for this goal
        $stmtCount = $this->pdo->prepare('SELECT COUNT(*) FROM checkins WHERE user_id = ? AND goal_id = ?');
        $stmtCount->execute([$userId, $goalId]);
        $count = (int)$stmtCount->fetchColumn();
        if ($count >= 1 && !$hasBadge('first_checkin')) {
            // award
            $b = $this->getBadgeBySlug('first_checkin');
            $this->awardBadge($userId, $b['id'], $goalId);
            $awarded[] = $b;
        }

        // 2) streak-based badges
        $stmtMeta = $this->pdo->prepare('SELECT current_streak FROM goal_user_meta WHERE user_id = ? AND goal_id = ?');
        $stmtMeta->execute([$userId, $goalId]);
        $meta = $stmtMeta->fetch(\PDO::FETCH_ASSOC);
        $streak = $meta ? (int)$meta['current_streak'] : 0;

        if ($streak >= 7 && !$hasBadge('7_day_streak')) {
            $b = $this->getBadgeBySlug('7_day_streak');
            $this->awardBadge($userId, $b['id'], $goalId);
            $awarded[] = $b;
        }
        if ($streak >= 30 && !$hasBadge('30_day_streak')) {
            $b = $this->getBadgeBySlug('30_day_streak');
            $this->awardBadge($userId, $b['id'], $goalId);
            $awarded[] = $b;
        }

        return $awarded;
    }

    protected function getBadgeBySlug(string $slug)
    {
        $stmt = $this->pdo->prepare('SELECT id, slug, title, description, icon FROM badges WHERE slug = ?');
        $stmt->execute([$slug]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    protected function awardBadge(int $userId, int $badgeId, ?int $goalId = null)
    {
        $stmt = $this->pdo->prepare('INSERT INTO user_badges (user_id, badge_id, goal_id) VALUES (?, ?, ?)');
        $stmt->execute([$userId, $badgeId, $goalId]);
    }
}
