<?php

namespace App\Models;

use App\Core\Model;

class GymClass extends Model
{
    protected static string $table = 'classes';

    protected static array $fillable = [
        'id',
        'name',
        'description',
        'coach_name',
        'day_of_week',
        'start_time',
        'duration_min',
        'capacity',
        'tier_min',
        'status',
    ];

    private const TIER_RANK = ['digital' => 0, 'standard' => 1, 'premium' => 2];

    /** Active classes a member of $tier may book, ordered by weekday and time. */
    public static function upcomingForTier(string $tier): array
    {
        $rank = self::TIER_RANK[$tier] ?? -1;
        if ($rank < 0) {
            return [];
        }

        $allowed = array_keys(array_filter(
            self::TIER_RANK,
            fn($r) => $r <= $rank
        ));

        $in = implode(',', array_fill(0, count($allowed), '?'));
        $sql = 'SELECT * FROM `classes` WHERE `status` = ? AND `tier_min` IN (' . $in . ')'
            . ' ORDER BY `day_of_week` ASC, `start_time` ASC';
        $stmt = \App\Config\Database::pdo()->prepare($sql);
        $stmt->execute(array_merge(['active'], $allowed));
        return $stmt->fetchAll();
    }

    public static function canTierBook(string $tier, string $tierMin): bool
    {
        return (self::TIER_RANK[$tier] ?? -1) >= (self::TIER_RANK[$tierMin] ?? 99);
    }
}
