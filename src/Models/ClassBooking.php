<?php

namespace App\Models;

use App\Core\Model;
use App\Config\Database;

class ClassBooking extends Model
{
    protected static string $table = 'class_bookings';

    protected static array $fillable = [
        'id',
        'class_id',
        'user_id',
        'booking_date',
        'status',
    ];

    public static function seatsLeft(string $classId, string $date): int
    {
        $pdo = Database::pdo();

        $stmt = $pdo->prepare('SELECT `capacity` FROM `classes` WHERE `id` = ? LIMIT 1');
        $stmt->execute([$classId]);
        $capacity = (int)($stmt->fetchColumn() ?: 0);

        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM `class_bookings`
             WHERE `class_id` = ? AND `booking_date` = ? AND `status` = ?'
        );
        $stmt->execute([$classId, $date, 'booked']);

        return max(0, $capacity - (int)$stmt->fetchColumn());
    }

    /** Bookings the member still holds on or after $fromDate, keyed class_id|date. */
    public static function forUser(string $userId, string $fromDate): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT * FROM `class_bookings`
             WHERE `user_id` = ? AND `booking_date` >= ? AND `status` = ?
             ORDER BY `booking_date` ASC'
        );
        $stmt->execute([$userId, $fromDate, 'booked']);

        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[$row['class_id'] . '|' . $row['booking_date']] = $row;
        }
        return $map;
    }

    /**
     * Books one seat. Throws \RuntimeException with a user-facing message when
     * the slot is full, already booked, outside the window, or tier-locked.
     */
    public static function book(string $classId, string $userId, string $tier, string $date, int $horizonDays): array
    {
        $pdo = Database::pdo();
        $today = date('Y-m-d');

        if ($date < $today) {
            throw new \RuntimeException('That class date has already passed.');
        }
        if ($date > date('Y-m-d', strtotime("+{$horizonDays} days"))) {
            throw new \RuntimeException('That date is outside your booking window.');
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('SELECT * FROM `classes` WHERE `id` = ? FOR UPDATE');
            $stmt->execute([$classId]);
            $class = $stmt->fetch();

            if (!$class || $class['status'] !== 'active') {
                throw new \RuntimeException('That class is not available.');
            }
            if ((int)$class['day_of_week'] !== (int)date('w', strtotime($date))) {
                throw new \RuntimeException('That class does not run on the selected date.');
            }
            if (!GymClass::canTierBook($tier, (string)$class['tier_min'])) {
                throw new \RuntimeException('Your membership tier cannot book this class.');
            }
            if (self::seatsLeft($classId, $date) < 1) {
                throw new \RuntimeException('This class is full.');
            }

            $stmt = $pdo->prepare(
                'SELECT `id`, `status` FROM `class_bookings`
                 WHERE `class_id` = ? AND `user_id` = ? AND `booking_date` = ? LIMIT 1'
            );
            $stmt->execute([$classId, $userId, $date]);
            $existing = $stmt->fetch();

            if ($existing && $existing['status'] === 'booked') {
                throw new \RuntimeException('You already booked this class.');
            }

            if ($existing) {
                $stmt = $pdo->prepare('UPDATE `class_bookings` SET `status` = ? WHERE `id` = ?');
                $stmt->execute(['booked', $existing['id']]);
                $booking = self::find($existing['id']);
            } else {
                $booking = self::create([
                    'class_id'     => $classId,
                    'user_id'      => $userId,
                    'booking_date' => $date,
                    'status'       => 'booked',
                ]);
            }

            $pdo->commit();
            return ['booking' => $booking, 'class' => $class];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Booked seats per class from $fromDate onward, keyed class_id. */
    public static function upcomingCounts(string $fromDate): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT `class_id`, COUNT(*) AS c FROM `class_bookings`
             WHERE `booking_date` >= ? AND `status` = ?
             GROUP BY `class_id`'
        );
        $stmt->execute([$fromDate, 'booked']);

        $counts = [];
        foreach ($stmt->fetchAll() as $row) {
            $counts[$row['class_id']] = (int)$row['c'];
        }
        return $counts;
    }

    /** Cancels a booking the member owns. Returns false when not found/owned. */
    public static function cancel(string $bookingId, string $userId): bool
    {
        $booking = self::find($bookingId);
        if (!$booking || $booking['user_id'] !== $userId || $booking['status'] !== 'booked') {
            return false;
        }
        if (strtotime($booking['booking_date'] . ' 23:59:59') < time()) {
            return false;
        }

        self::update($bookingId, ['status' => 'cancelled']);
        return true;
    }
}
