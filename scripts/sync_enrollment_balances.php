<?php
declare(strict_types=1);

/**
 * One-off repair: recompute every enrollment's balance from the payments
 * on record. Use after upgrading if a payment was confirmed while the
 * enrollment was already active (balance used to stay stale).
 *
 * Run: php scripts/sync_enrollment_balances.php
 */

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\Enrollment;
use App\Config\Database;

$rows = Database::pdo()
    ->query("SELECT DISTINCT `user_id`, `tier` FROM `enrollments`")
    ->fetchAll(\PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    Enrollment::syncBalance((string)$r['user_id'], (string)$r['tier']);
}

echo 'Synced ' . count($rows) . " enrollment group(s).\n";
