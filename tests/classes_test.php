<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Models\ClassBooking;
use App\Config\Database;

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

function throws(callable $fn, string $needle): void
{
    try {
        $fn();
    } catch (\RuntimeException $e) {
        check(str_contains($e->getMessage(), $needle), "error says: {$needle}");
        return;
    }
    check(false, "expected failure containing: {$needle}");
}

$pdo = Database::pdo();

$class = $pdo->query("SELECT * FROM classes WHERE tier_min = 'standard' AND status = 'active' ORDER BY day_of_week LIMIT 1")->fetch();
check((bool)$class, 'a standard class exists');

$userId = $pdo->query("SELECT id FROM users WHERE role = 'user' ORDER BY created_date LIMIT 1")->fetchColumn();
check((bool)$userId, 'a member user exists');

// Next occurrence of the class weekday.
$date = date('Y-m-d', strtotime('+' . (((int)$class['day_of_week'] - (int)date('w') + 7) % 7 ?: 7) . ' days'));
$startSeats = ClassBooking::seatsLeft($class['id'], $date);
check($startSeats > 0, 'class starts with seats');

$result = ClassBooking::book($class['id'], $userId, 'standard', $date, 7);
check(isset($result['booking']['id']), 'booking created');
$bookingId = $result['booking']['id'];

check(ClassBooking::seatsLeft($class['id'], $date) === $startSeats - 1, 'seat count drops');

throws(fn() => ClassBooking::book($class['id'], $userId, 'standard', $date, 7), 'already booked');
throws(fn() => ClassBooking::book($class['id'], $userId, 'digital', $date, 7), 'tier');
throws(fn() => ClassBooking::book($class['id'], $userId, 'standard', date('Y-m-d', strtotime('+40 days')), 7), 'window');

check(ClassBooking::cancel($bookingId, $userId) === true, 'cancel works');
check(ClassBooking::seatsLeft($class['id'], $date) === $startSeats, 'seat restored');
check(ClassBooking::cancel($bookingId, $userId) === false, 'double cancel rejected');

// Clean the test row.
$pdo->prepare('DELETE FROM class_bookings WHERE id = ?')->execute([$bookingId]);

echo "OK classes\n";
