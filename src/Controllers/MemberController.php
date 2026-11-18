<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MemberProfile;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\GymClass;
use App\Models\ClassBooking;
use App\Models\User;
use App\Config\Database;

class MemberController extends Controller
{
    /**
     * Self-heal the stored balances from the payments on record, so a member
     * never sees a stale amount on their own pages.
     */
    private function syncBalances(string $userId): void
    {
        foreach (Enrollment::where(['user_id' => $userId]) as $e) {
            Enrollment::syncBalance($userId, (string)$e['tier']);
        }
    }

    public function home(): void
    {
        $user = current_user();
        $this->syncBalances((string)$user['id']);
        $profile = MemberProfile::first(['user_id' => $user['id']])
            ?? MemberProfile::create(['user_id' => $user['id'], 'sessions_left' => 12]);

        // Enrollments are already sorted by created_date DESC in the Model.
        $enrollments = Enrollment::where(['user_id' => $user['id']], 'created_date DESC');

        // Date-aware: an "active" row whose end date has passed must not look active.
        $active = Enrollment::activeForUser((string)$user['id']);
        $pending = $active ? null : Enrollment::pendingForUser((string)$user['id']);

        // If no active, but there's a pending, use the pending one to show the "Pending" state on the dashboard
        $displayEnrollment = $active ?? $pending;

        $daysLeft = $active && $active['end_date']
            ? max(0, (int)ceil((strtotime($active['end_date']) - time()) / 86400))
            : null;

        $balance = 0;
        foreach ($enrollments as $e) {
            if (in_array($e['status'], ['active', 'pending'], true)) {
                $balance += (float)$e['balance'];
            }
        }

        $this->view('member/home', [
            'pageTitle' => 'Member Home',
            'profile'   => $profile,
            'active'    => $displayEnrollment, // Use the combined display variable
            'daysLeft'  => $daysLeft,
            'balance'   => $balance,
            'firstName' => explode(' ', $user['full_name'] ?: $user['email'])[0],
        ], 'portal');
    }

    public function programs(): void
    {
        // Only show portal-visible and active programs
        $programs = Program::where(['active' => 1, 'portal_visible' => 1], 'tier ASC');
        if (!$programs) {
            $programs = Program::all('tier ASC');
        }
        $this->view('member/programs', [
            'pageTitle'      => 'Programs & Tiers',
            'programs'       => $programs,
            'paymentMethods' => Payment::methodConfig()['accepted_methods'],
        ], 'portal');
    }

    public function enroll(): void
    {
        csrf_verify();
        $user = current_user();
        $program = Program::find((string)$this->input('program_id'));

        if (!$program) {
            $this->redirect('/programs');
        }

        // 🔐 FIX: Prevent duplicate active or pending enrollments
        if (Enrollment::activeForUser($user['id']) || Enrollment::pendingForUser($user['id'])) {
            flash('error', 'You already have an active or pending enrollment. Please manage it on your Payments page.');
            $this->redirect('/programs');
        }

        $method    = trim((string)$this->input('method', 'gcash'));
        $reference = trim((string)$this->input('reference_no', ''));

        if (!Payment::isAcceptedMethod($method)) {
            flash('error', 'Please choose an accepted payment method.');
            $this->redirect('/programs');
        }

        $referenceError = Payment::referenceError($method, $reference);
        if ($referenceError !== null) {
            flash('error', $referenceError);
            $this->redirect('/programs');
        }

        $today = date('Y-m-d');
        $end   = date('Y-m-d', strtotime("+{$program['duration_days']} days"));
        $pdo   = Database::pdo();

        try {
            $pdo->beginTransaction();

            // 1. Create the Enrollment
            $enrollment = Enrollment::create([
                'user_id'      => $user['id'],
                'user_name'    => $user['full_name'] ?: $user['email'],
                'program_id'   => $program['id'],
                'program_name' => $program['name'],
                'tier'         => $program['tier'],
                'status'       => 'pending',
                'start_date'   => $today,
                'end_date'     => $end,
                'balance'      => $program['price'],
            ]);

            // 2. Create the corresponding Pending Payment
            Payment::create([
                'user_id'     => $user['id'],
                'user_name'   => $user['full_name'] ?: $user['email'],
                'source'      => 'b2c',
                'tier'        => $program['tier'],
                'amount'      => $program['price'],
                'method'       => $method,
                'status'       => 'pending',
                'paid_date'    => $today,
                'description'  => $program['name'] . ' enrollment',
                'reference_no' => $reference !== '' ? $reference : null,
            ]);

            // Log the event
            Enrollment::logEvent(
                $enrollment['id'],
                'created',
                null,
                'pending',
                null,
                $program['tier'],
                null,
                $user['id'],
                'Member self-enrolled'
            );

            $pdo->commit();

            flash('success', 'Payment submitted! Your enrollment activates once confirmed.');
            $this->redirect('/payments');
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('[MemberController::enroll] ' . $e->getMessage());
            flash('error', 'Something went wrong while creating your enrollment. Please try again.');
            $this->redirect('/programs');
        }
    }

    public function payments(): void
    {
        $user = current_user();
        $this->syncBalances((string)$user['id']);
        $payments = Payment::where(['user_id' => $user['id']], 'paid_date DESC');
        $this->view('member/payments', [
            'pageTitle' => 'Payment History',
            'payments'  => $payments,
        ], 'portal');
    }

    public function receipt(): void
    {
        $p = Payment::find((string)($_GET['id'] ?? ''));
        $me = current_user();

        if (!$p || ($p['user_id'] ?? '') !== $me['id']) {
            http_response_code(404);
            exit('Receipt not found.');
        }

        header('Content-Type: text/html; charset=utf-8');

        $this->view('admin/payment_receipt', Payment::receiptContext($p, $me));
        exit;
    }

    public function timeIn(): void
    {
        $this->view('member/time_in', ['pageTitle' => 'Time In'], 'portal');
    }

    public function scanTimeIn(): void
    {
        csrf_verify();
        $user = current_user();
        $text = trim((string)$this->input('decodedText'));

        if ($text === '') {
            $this->json(['error' => 'No QR code was scanned.'], 400);
            return;
        }

        $expected = defined('GYM_QR_DATA') ? (string) GYM_QR_DATA : '';

        // Refuse to run with the insecure dev fallback or a short secret
        if ($expected === '' || strlen($expected) < 16 || str_starts_with($expected, 'INSECURE-')) {
            error_log(
                '[scanTimeIn] GYM_QR_DATA is not configured or too weak. '
                    . 'Set a 32+ character random value in .env.'
            );
            $this->json(['error' => 'Check-in is temporarily unavailable.'], 503);
            return;
        }

        // Constant-time comparison
        if (!hash_equals($expected, $text)) {
            if (function_exists('audit_log')) {
                audit_log(
                    'attendance.check_in_rejected',
                    'attendance',
                    null,
                    null,
                    ['submitted_length' => strlen($text)],
                    'Invalid gym QR code submitted'
                );
            }
            $this->json(
                ['error' => 'Invalid QR code. Please scan the poster at the gym entrance.'],
                400
            );
            return;
        }

        // Require an ACTIVE enrollment
        $activeEnrollment = Enrollment::activeForUser($user['id']);
        if (!$activeEnrollment) {
            $this->json([
                'error' => 'You need an active enrollment to check in. Please renew on the Programs page.'
            ], 403);
            return;
        }

        // Prevent duplicate open sessions
        $existing = Attendance::openToday($user['id']);
        if ($existing) {
            $this->json(['status' => 'already', 'record' => $existing]);
            return;
        }

        $record = Attendance::create([
            'user_id'   => $user['id'],
            'user_name' => $user['full_name'] ?: $user['email'],
            'date'      => date('Y-m-d'),
            'time_in'   => date('Y-m-d H:i:s'),
            'hours'     => 0,
            'tier'      => $activeEnrollment['tier'] ?? null,
            'source'    => 'qr',
        ]);

        $this->json(['status' => 'success', 'record' => $record]);
    }

    public function timeOut(): void
    {
        csrf_verify();
        $recordId = (string)$this->input('record_id');
        $user     = current_user();

        // Only the owner may close their own open session.
        if (!Attendance::belongsTo($recordId, (string)$user['id'])) {
            $this->json(['error' => 'Not found'], 404);
        }

        $record = Attendance::find($recordId);

        $now = time();
        $hours = max(0, ($now - strtotime($record['time_in'])) / 3600);

        Attendance::update($recordId, [
            'time_out' => date('Y-m-d H:i:s'),
            'hours'    => round($hours, 1),
        ]);

        $this->json(['status' => 'success']);
    }

    public function attendance(): void
    {
        $user = current_user();
        $records = Attendance::where(['user_id' => $user['id']], 'time_in DESC');
        $this->view('member/attendance', [
            'pageTitle' => 'Attendance History',
            'records'   => $records,
        ], 'portal');
    }

    /* =============================================================
     |  GROUP CLASSES
     ============================================================= */

    public function classes(): void
    {
        [$user, $slots, $caps, $tier, $horizon, $active] = $this->classScheduleData();

        $this->view('member/classes', [
            'pageTitle'    => 'Group Classes',
            'slots'        => $slots,
            'caps'         => $caps,
            'tier'         => $tier,
            'horizon'      => $horizon,
            'hasActive'    => (bool)$active,
        ], 'portal');
    }

    /** Shared schedule builder for the view and the JSON API. */
    private function classScheduleData(): array
    {
        $user   = current_user();
        $active = Enrollment::activeForUser((string)$user['id']);
        $tier   = (string)($active['tier'] ?? '');
        $caps   = member_capabilities();

        $horizon  = !empty($caps['class_priority_booking']) ? 14 : 7;
        $classes  = !empty($caps['group_classes']) ? GymClass::upcomingForTier($tier) : [];
        $bookings = !empty($caps['group_classes'])
            ? ClassBooking::forUser((string)$user['id'], date('Y-m-d'))
            : [];

        $slots = [];
        for ($i = 0; $i < $horizon; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} day"));
            $dow  = (int)date('w', strtotime($date));

            foreach ($classes as $class) {
                if ((int)$class['day_of_week'] !== $dow) {
                    continue;
                }
                $startTs = strtotime($date . ' ' . $class['start_time']);
                if ($startTs < time()) {
                    continue;
                }

                $key      = $class['id'] . '|' . $date;
                $booking  = $bookings[$key] ?? null;
                $slots[]  = [
                    'class'      => $class,
                    'date'       => $date,
                    'start_ts'   => $startTs,
                    'seats_left' => ClassBooking::seatsLeft((string)$class['id'], $date),
                    'booking_id' => $booking['id'] ?? null,
                ];
            }
        }

        usort($slots, fn($a, $b) => $a['start_ts'] <=> $b['start_ts']);

        return [$user, $slots, $caps, $tier, $horizon, $active];
    }

    public function bookClass(): void
    {
        csrf_verify();
        $user = current_user();

        if (!member_can('group_classes')) {
            flash('error', 'Group classes are available on Standard and Premium.');
            $this->redirect('/classes');
        }

        $classId = trim((string)$this->input('class_id'));
        $date    = trim((string)$this->input('booking_date'));

        $parsed = \DateTime::createFromFormat('Y-m-d', $date);
        if (!$classId || !$parsed || $parsed->format('Y-m-d') !== $date) {
            flash('error', 'Choose a valid class and date.');
            $this->redirect('/classes');
        }

        $active = Enrollment::activeForUser((string)$user['id']);
        $tier   = (string)($active['tier'] ?? '');
        $horizon = member_can('class_priority_booking') ? 14 : 7;

        try {
            ClassBooking::book($classId, (string)$user['id'], $tier, $date, $horizon);
            flash('success', 'Class booked. See you there!');
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[MemberController::bookClass] ' . $e->getMessage());
            flash('error', 'Could not book that class. Please try again.');
        }

        $this->redirect('/classes');
    }

    public function cancelClass(): void
    {
        csrf_verify();
        $user = current_user();

        $bookingId = trim((string)$this->input('booking_id'));
        if ($bookingId === '' || !ClassBooking::cancel($bookingId, (string)$user['id'])) {
            flash('error', 'That booking could not be cancelled.');
        } else {
            flash('success', 'Booking cancelled.');
        }

        $this->redirect('/classes');
    }

    /* =============================================================
     |  WORKOUT RECOMMENDER + AI COACH
     ============================================================= */

    public function recommender(): void
    {
        $user    = current_user();
        $profile = MemberProfile::first(['user_id' => $user['id']]) ?? [];
        $active  = Enrollment::activeForUser((string)$user['id']);
        $caps    = member_capabilities();

        $visits = 0;
        $since  = strtotime('-30 days');
        foreach (Attendance::where(['user_id' => $user['id']], 'time_in DESC', 500) as $row) {
            if (strtotime((string)$row['time_in']) >= $since) {
                $visits++;
            }
        }

        $context = [
            'tier'   => (string)($active['tier'] ?? ''),
            'goal'   => (string)($profile['fitness_goal'] ?? ''),
            'visits' => $visits,
            'name'   => explode(' ', (string)($user['full_name'] ?: $user['email']))[0],
        ];

        $this->view('member/recommender', [
            'pageTitle' => 'Workout Plan',
            'plan'      => workout_recommendation($context),
            'context'   => $context,
            'caps'      => $caps,
            'hasActive' => (bool)$active,
        ], 'portal');
    }

    public function aiCoach(): void
    {
        $this->renderAiCoach(null, null);
    }

    public function aiCoachAsk(): void
    {
        csrf_verify();
        $user = current_user();

        if (!member_can('ai_coach')) {
            flash('error', '24/7 AI Coach is a Premium feature.');
            $this->redirect('/ai-coach');
        }

        $question = trim((string)$this->input('question'));
        if (mb_strlen($question) < 3 || mb_strlen($question) > 500) {
            flash('error', 'Ask a question between 3 and 500 characters.');
            $this->redirect('/ai-coach');
        }

        $profile = MemberProfile::first(['user_id' => $user['id']]) ?? [];
        $active  = Enrollment::activeForUser((string)$user['id']);

        $bookings = ClassBooking::forUser((string)$user['id'], date('Y-m-d'));
        $context  = [
            'tier'             => (string)($active['tier'] ?? ''),
            'goal'             => (string)($profile['fitness_goal'] ?? ''),
            'visits'           => 0,
            'name'             => explode(' ', (string)($user['full_name'] ?: $user['email']))[0],
            'upcoming_classes' => count($bookings),
        ];

        $this->renderAiCoach($question, ai_coach_reply($question, $context));
    }

    private function renderAiCoach(?string $question, ?string $answer): void
    {
        $user    = current_user();
        $profile = MemberProfile::first(['user_id' => $user['id']]) ?? [];
        $active  = Enrollment::activeForUser((string)$user['id']);
        $caps    = member_capabilities();

        $context = [
            'tier'   => (string)($active['tier'] ?? ''),
            'goal'   => (string)($profile['fitness_goal'] ?? ''),
            'visits' => 0,
            'name'   => explode(' ', (string)($user['full_name'] ?: $user['email']))[0],
        ];

        $this->view('member/ai_coach', [
            'pageTitle' => 'AI Coach',
            'question'  => $question,
            'answer'    => $answer,
            'context'   => $context,
            'caps'      => $caps,
            'hasActive' => (bool)$active,
        ], 'portal');
    }

    public function profile(): void
    {
        $user = current_user();
        $profile = MemberProfile::first(['user_id' => $user['id']])
            ?? MemberProfile::create(['user_id' => $user['id'], 'sessions_left' => 12]);
        $enrollments = Enrollment::where(['user_id' => $user['id']], 'created_date DESC');
        $this->view('member/profile', [
            'pageTitle'   => 'My Profile',
            'profile'     => $profile,
            'enrollments' => $enrollments,
        ], 'portal');
    }

    public function saveProfile(): void
    {
        csrf_verify();
        $user = current_user();
        $profile = MemberProfile::first(['user_id' => $user['id']]);

        $name = trim((string)$this->input('full_name'));
        if ($name !== '' && mb_strlen($name) <= 100) {
            User::updateProfile((string)$user['id'], ['full_name' => $name]);
        }

        MemberProfile::update($profile['id'], [
            'phone'             => $this->input('phone'),
            'address'           => $this->input('address'),
            'birth_date'        => $this->input('birth_date') ?: null,
            'emergency_contact' => $this->input('emergency_contact'),
            'fitness_goal'      => $this->input('fitness_goal'),
        ]);

        flash('success', 'Details saved.');
        $this->redirect('/profile');
    }

    /**
     * Live occupancy endpoint
     */
    public function occupancy(): void
    {
        $today = Attendance::todayAll();

        $in = 0;
        $hourly = [];
        for ($i = 6; $i <= 20; $i++) {
            $hourly[$i] = 0;
        }

        foreach ($today as $r) {
            // Currently in = no time_out yet
            if (empty($r['time_out'])) $in++;

            // Bucket by hour
            if (!empty($r['time_in'])) {
                $h = (int)date('G', strtotime($r['time_in']));
                if (isset($hourly[$h])) $hourly[$h]++;
            }
        }

        $chart = [];
        for ($i = 6; $i <= 20; $i++) {
            $chart[] = [
                'hour'     => sprintf('%02d:00', $i),
                // 🔐 FIX: Removed the 'checksins' typo
                'checkins' => $hourly[$i],
            ];
        }

        $this->json([
            'in'       => $in,
            'capacity' => Attendance::capacity(), // 🔐 FIX: Now reads from system_settings
            'hourly'   => $chart,
        ]);
    }

    /* =============================================================
     |  JSON API (member)
     ============================================================= */

    public function apiCapabilities(): void
    {
        $user   = current_user();
        $active = Enrollment::activeForUser((string)$user['id']);

        $this->json([
            'ok'   => true,
            'data' => [
                'tier'         => (string)($active['tier'] ?? ''),
                'active'       => (bool)$active,
                'capabilities' => member_capabilities(),
                'meta'         => capability_meta(),
            ],
        ]);
    }

    public function apiPaymentMethods(): void
    {
        $config = Payment::methodConfig();
        $this->json([
            'ok'   => true,
            'data' => [
                'accepted' => $config['accepted_methods'],
                'labels'   => array_map(fn($m) => Payment::methodLabel($m), $config['accepted_methods']),
            ],
        ]);
    }

    public function apiClasses(): void
    {
        [, $slots, $caps, $tier, $horizon] = $this->classScheduleData();

        $this->json([
            'ok'   => true,
            'data' => [
                'tier'         => $tier,
                'horizon_days' => $horizon,
                'can_book'     => !empty($caps['group_classes']),
                'slots'        => array_map(fn($slot) => [
                    'class_id'   => $slot['class']['id'],
                    'name'       => $slot['class']['name'],
                    'coach'      => $slot['class']['coach_name'],
                    'date'       => $slot['date'],
                    'start_time' => date('H:i', $slot['start_ts']),
                    'duration'   => (int)$slot['class']['duration_min'],
                    'capacity'   => (int)$slot['class']['capacity'],
                    'seats_left' => $slot['seats_left'],
                    'booking_id' => $slot['booking_id'],
                ], $slots),
            ],
        ]);
    }

    public function apiBookClass(): void
    {
        csrf_verify();
        $user = current_user();

        if (!member_can('group_classes')) {
            $this->json(['ok' => false, 'error' => 'Group classes need a Standard or Premium membership.'], 403);
        }

        $classId = trim((string)$this->input('class_id'));
        $date    = trim((string)$this->input('booking_date'));
        $parsed  = \DateTime::createFromFormat('Y-m-d', $date);

        if (!$classId || !$parsed || $parsed->format('Y-m-d') !== $date) {
            $this->json(['ok' => false, 'error' => 'Choose a valid class and date.'], 422);
        }

        $active  = Enrollment::activeForUser((string)$user['id']);
        $horizon = member_can('class_priority_booking') ? 14 : 7;

        try {
            $result = ClassBooking::book($classId, (string)$user['id'], (string)($active['tier'] ?? ''), $date, $horizon);
            $this->json([
                'ok'   => true,
                'data' => [
                    'booking_id' => $result['booking']['id'] ?? null,
                    'seats_left' => ClassBooking::seatsLeft($classId, $date),
                ],
            ], 201);
        } catch (\RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 409);
        }
    }

    public function apiCancelClass(): void
    {
        csrf_verify();
        $user = current_user();

        $bookingId = trim((string)$this->input('booking_id'));
        if ($bookingId === '' || !ClassBooking::cancel($bookingId, (string)$user['id'])) {
            $this->json(['ok' => false, 'error' => 'That booking could not be cancelled.'], 404);
        }

        $this->json(['ok' => true, 'data' => ['booking_id' => $bookingId]]);
    }

    public function apiRecommender(): void
    {
        $user    = current_user();
        $profile = MemberProfile::first(['user_id' => $user['id']]) ?? [];
        $active  = Enrollment::activeForUser((string)$user['id']);
        $caps    = member_capabilities();

        if (empty($caps['ai_recommender'])) {
            $this->json(['ok' => false, 'error' => 'The workout recommender needs an active membership.'], 403);
        }

        $visits = 0;
        $since  = strtotime('-30 days');
        foreach (Attendance::where(['user_id' => $user['id']], 'time_in DESC', 500) as $row) {
            if (strtotime((string)$row['time_in']) >= $since) {
                $visits++;
            }
        }

        $context = [
            'tier'   => (string)($active['tier'] ?? ''),
            'goal'   => (string)($profile['fitness_goal'] ?? ''),
            'visits' => $visits,
            'name'   => explode(' ', (string)($user['full_name'] ?: $user['email']))[0],
        ];

        $this->json(['ok' => true, 'data' => workout_recommendation($context)]);
    }

    public function apiAiCoach(): void
    {
        csrf_verify();
        $user = current_user();

        if (!member_can('ai_coach')) {
            $this->json(['ok' => false, 'error' => '24/7 AI Coach is a Premium feature.'], 403);
        }

        $question = trim((string)$this->input('question'));
        if (mb_strlen($question) < 3 || mb_strlen($question) > 500) {
            $this->json(['ok' => false, 'error' => 'Ask a question between 3 and 500 characters.'], 422);
        }

        $active   = Enrollment::activeForUser((string)$user['id']);
        $bookings = ClassBooking::forUser((string)$user['id'], date('Y-m-d'));

        $answer = ai_coach_reply($question, [
            'tier'             => (string)($active['tier'] ?? ''),
            'name'             => explode(' ', (string)($user['full_name'] ?: $user['email']))[0],
            'upcoming_classes' => count($bookings),
        ]);

        $this->json(['ok' => true, 'data' => ['question' => $question, 'answer' => $answer]]);
    }
}
