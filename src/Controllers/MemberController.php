<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\MemberProfile;
use App\Models\Enrollment;
use App\Models\Program;
use App\Models\Payment;
use App\Models\Attendance;
use App\Config\Database;

class MemberController extends Controller
{
    public function home(): void
    {
        $user = current_user();
        $profile = MemberProfile::first(['user_id' => $user['id']])
            ?? MemberProfile::create(['user_id' => $user['id'], 'sessions_left' => 12]);

        // Enrollments are already sorted by created_date DESC in the Model.
        $enrollments = Enrollment::where(['user_id' => $user['id']], 'created_date DESC');

        $active = null;
        $pending = null;

        foreach ($enrollments as $e) {
            // Grab the first active enrollment found (which is the newest due to the sort)
            if ($e['status'] === 'active' && !$active) {
                $active = $e;
            }
            // Grab the newest pending enrollment if it exists
            if ($e['status'] === 'pending' && !$pending) {
                $pending = $e;
            }
        }

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
            'pageTitle' => 'Programs & Tiers',
            'programs'  => $programs,
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
                'method'      => 'gcash', // Default, can be changed by member
                'status'      => 'pending',
                'paid_date'   => $today,
                'description' => $program['name'] . ' enrollment',
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
        $payments = Payment::where(['user_id' => $user['id']], 'paid_date DESC');
        $this->view('member/payments', [
            'pageTitle' => 'Payment History',
            'payments'  => $payments,
        ], 'portal');
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
        $record = Attendance::find($recordId);

        if (!$record) {
            $this->json(['error' => 'Not found'], 404);
        }

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
}
