<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class Program extends Model
{
    protected static string $table = 'programs';

    protected static array $fillable = [
        'id',
        'name',
        'tier',
        'price',
        'duration_days',
        'description',
        'active',
        'icon',
        'features',
        'operational_cost',
        'portal_visible',
        'auto_renew',
        'grace_period_days',
        'sort_order',
    ];

    /* =============================================================
     |  LOOKUPS
     ============================================================= */

    public static function findByName(string $name): ?array
    {
        return self::first(['name' => $name]);
    }

    /**
     * Enforce unique program names (spec 1.1).
     * Pass $exceptId when editing so the row doesn't clash with itself.
     */
    public static function nameExists(string $name, ?string $exceptId = null): bool
    {
        $pdo    = Database::pdo();
        $sql    = 'SELECT COUNT(*) FROM `programs` WHERE `name` = ?';
        $params = [$name];

        if ($exceptId !== null && $exceptId !== '') {
            $sql .= ' AND `id` <> ?';
            $params[] = $exceptId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function activeOnly(): array
    {
        return self::where(['active' => 1], 'sort_order ASC, tier ASC');
    }

    public static function portalVisible(): array
    {
        return self::where(
            ['active' => 1, 'portal_visible' => 1],
            'sort_order ASC, tier ASC'
        );
    }

    /* =============================================================
     |  ANALYTICS
     ============================================================= */

    /**
     * Every program plus:
     *   active_enrollments, pending_enrollments, expired_enrollments,
     *   cancelled_enrollments, total_enrollments, revenue
     *
     * Backs:
     *   - Active enrollments per tier
     *   - Revenue contribution per program card
     *   - Cancellation / expiration trends
     *   - Most / least popular callout
     */
    public static function allWithStats(): array
    {
        $pdo = Database::pdo();

        $sql = "
            SELECT
                p.*,
                COALESCE(e.active_cnt,    0) AS active_enrollments,
                COALESCE(e.pending_cnt,   0) AS pending_enrollments,
                COALESCE(e.expired_cnt,   0) AS expired_enrollments,
                COALESCE(e.cancelled_cnt, 0) AS cancelled_enrollments,
                COALESCE(e.total_cnt,     0) AS total_enrollments,
                COALESCE(pay.revenue,     0) AS revenue
            FROM `programs` p
            LEFT JOIN (
                SELECT
                    `program_id`,
                    SUM(`status` = 'active')    AS active_cnt,
                    SUM(`status` = 'pending')   AS pending_cnt,
                    SUM(`status` = 'expired')   AS expired_cnt,
                    SUM(`status` = 'cancelled') AS cancelled_cnt,
                    COUNT(*)                    AS total_cnt
                FROM `enrollments`
                GROUP BY `program_id`
            ) e ON e.`program_id` = p.`id`
            LEFT JOIN (
                SELECT `tier`, SUM(`amount`) AS revenue
                FROM `payments`
                WHERE `status` = 'paid'
                GROUP BY `tier`
            ) pay ON pay.`tier` = p.`tier`
            ORDER BY p.`sort_order` ASC, p.`tier` ASC
        ";

        return $pdo->query($sql)->fetchAll();
    }

    /**
     * Returns ['most_popular' => row|null, 'least_popular' => row|null]
     * ranked by active enrollment count.
     */
    public static function popularity(): array
    {
        $rows = self::allWithStats();
        if (empty($rows)) {
            return ['most_popular' => null, 'least_popular' => null];
        }

        usort(
            $rows,
            fn($a, $b) => (int)$b['active_enrollments'] <=> (int)$a['active_enrollments']
        );

        return [
            'most_popular'  => $rows[0],
            'least_popular' => end($rows),
        ];
    }

    /**
     * Rolled-up counts for the KPI strip on the Programs tab.
     */
    public static function statsSummary(): array
    {
        $pdo = Database::pdo();

        $summary = [
            'total_programs'    => 0,
            'active_programs'   => 0,
            'portal_visible'    => 0,
            'total_revenue'     => 0.0,
            'active_enrolls'    => 0,
            'pending_enrolls'   => 0,
            'expired_enrolls'   => 0,
            'cancelled_enrolls' => 0,
        ];

        $summary['total_programs'] = (int)$pdo
            ->query('SELECT COUNT(*) FROM `programs`')
            ->fetchColumn();

        $summary['active_programs'] = (int)$pdo
            ->query('SELECT COUNT(*) FROM `programs` WHERE `active` = 1')
            ->fetchColumn();

        $summary['portal_visible'] = (int)$pdo
            ->query('SELECT COUNT(*) FROM `programs` WHERE `active` = 1 AND `portal_visible` = 1')
            ->fetchColumn();

        $summary['total_revenue'] = (float)$pdo
            ->query("SELECT COALESCE(SUM(`amount`),0) FROM `payments` WHERE `status` = 'paid'")
            ->fetchColumn();

        $rows = $pdo->query("
            SELECT `status`, COUNT(*) AS c
            FROM `enrollments`
            GROUP BY `status`
        ")->fetchAll();

        foreach ($rows as $r) {
            $key = $r['status'] . '_enrolls';
            if (array_key_exists($key, $summary)) {
                $summary[$key] = (int)$r['c'];
            }
        }

        return $summary;
    }

    /* =============================================================
     |  VALIDATION HELPERS  (spec 1.1)
     ============================================================= */

    /**
     * Floor price from system_settings.operational_cost_per_member.
     * Returns 0 if not configured (no warning shown).
     */
    public static function operationalCostFloor(): float
    {
        try {
            $val = Database::pdo()->query(
                "SELECT `setting_value` FROM `system_settings`
                 WHERE `setting_key` = 'operational_cost_per_member' LIMIT 1"
            )->fetchColumn();
        } catch (\Throwable) {
            return 0.0;
        }
        return (float)($val ?: 0);
    }

    public static function isBelowCost(float $price): bool
    {
        $floor = self::operationalCostFloor();
        return $floor > 0 && $price < $floor;
    }

    /* =============================================================
     |  FEATURE-LIST HELPERS  (JSON encode / decode)
     ============================================================= */

    /**
     * Decode `features` JSON into a clean array of strings.
     * Always returns an array (never null).
     */
    public static function featuresList(?string $json): array
    {
        if (!$json) return [];
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) return [];

        return array_values(array_filter(
            array_map(fn($s) => is_string($s) ? trim($s) : '', $decoded),
            fn($s) => $s !== ''
        ));
    }

    /**
     * Encode features from either an array or a newline/comma string
     * into a JSON string suitable for the `features` column.
     */
    public static function encodeFeatures(array|string|null $features): string
    {
        if (is_string($features)) {
            $features = preg_split('/\r\n|\r|\n|,/', $features) ?: [];
        }
        $clean = array_values(array_filter(
            array_map(fn($s) => is_string($s) ? trim($s) : '', $features ?? []),
            fn($s) => $s !== ''
        ));
        return json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /* =============================================================
     |  DISPLAY HELPERS
     ============================================================= */

    /**
     * Default icon fallback per tier when a program has none set.
     */
    public static function tierFallbackIcon(string $tier): string
    {
        return [
            'digital'  => '📱',
            'standard' => '⚡',
            'premium'  => '👑',
            'b2b'      => '🤝',
        ][$tier] ?? '🏋️';
    }

    /**
     * Human-friendly tier label.
     */
    public static function tierLabel(string $tier): string
    {
        return [
            'digital'  => 'Digital',
            'standard' => 'Standard',
            'premium'  => 'Premium',
            'b2b'      => 'B2B',
        ][$tier] ?? ucfirst($tier);
    }

    /**
     * Max sort_order in use (for "append to end" on create).
     */
    public static function nextSortOrder(): int
    {
        $max = Database::pdo()
            ->query('SELECT COALESCE(MAX(`sort_order`), 0) FROM `programs`')
            ->fetchColumn();
        return (int)$max + 1;
    }
}
