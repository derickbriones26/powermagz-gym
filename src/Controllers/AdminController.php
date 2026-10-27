<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\User;
use App\Models\Program;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\PartnerProfile;
use App\Models\PartnerSale;
use App\Models\PayoutRequest;
use App\Models\B2BClient;

class AdminController extends Controller
{
    /* =============================================================
     |  DASHBOARD
     ============================================================= */

    public function dashboard(): void
    {
        Enrollment::syncExpirations();

        $tab = $_GET['tab'] ?? 'overview';

        /* ---- Members tab filters ---- */
        $memberFilters = [
            'role'        => $_GET['role']        ?? 'all',
            'status'      => $_GET['status']      ?? 'all',
            'tier'        => $_GET['tier']        ?? 'all',
            'joined_from' => $_GET['joined_from'] ?? '',
            'joined_to'   => $_GET['joined_to']   ?? '',
            'search'      => $_GET['q']           ?? '',
        ];

        /* ---- Payments tab filters ---- */
        $paymentFilters = [
            'status' => $_GET['pay_status'] ?? 'all',
            'method' => $_GET['pay_method'] ?? 'all',
            'tier'   => $_GET['pay_tier']   ?? 'all',
            'source' => $_GET['pay_source'] ?? '',
            'from'   => $_GET['pay_from']   ?? '',
            'to'     => $_GET['pay_to']     ?? '',
            'search' => $_GET['pay_q']      ?? '',
            'limit'  => 500,
        ];

        /* ---- Attendance filters ---- */
        $attendanceFilters = [
            'from'      => $_GET['att_from']   ?? date('Y-m-d'),
            'to'        => $_GET['att_to']     ?? date('Y-m-d'),
            'member'    => $_GET['att_member'] ?? '',
            'user_id'   => $_GET['att_user']   ?? '',
            'tier'      => $_GET['att_tier']   ?? 'all',
            'source'    => $_GET['att_source'] ?? 'all',
            'open_only' => !empty($_GET['att_open']),
            'limit'     => 500,
        ];

        /* ---- Partner filters (§1.5) ---- */
        $partnerFilters = [
            'status' => $_GET['p_status'] ?? 'all',
            'type'   => $_GET['p_type']   ?? 'all',
            'search' => $_GET['p_q']      ?? '',
            'limit'  => 500,
        ];

        /* ---- Sales filters ---- */
        $salesFilters = [
            'status'           => $_GET['s_status']  ?? 'all',
            'partner_user_id'  => $_GET['s_partner'] ?? '',
            'type'             => $_GET['s_type']    ?? 'all',
            'from'             => $_GET['s_from']    ?? '',
            'to'               => $_GET['s_to']      ?? '',
            'search'           => $_GET['s_q']       ?? '',
            'limit'            => 500,
        ];

        /* ---- Payout filters ---- */
        $payoutFilters = [
            'status'           => $_GET['po_status'] ?? 'all',
            'method'           => $_GET['po_method'] ?? 'all',
            'partner_user_id'  => $_GET['po_partner'] ?? '',
            'batch_id'         => $_GET['po_batch']  ?? '',
            'from'             => $_GET['po_from']   ?? '',
            'to'               => $_GET['po_to']     ?? '',
            'search'           => $_GET['po_q']      ?? '',
            'limit'            => 500,
        ];

        /* ---- B2B client filters ---- */
        $clientFilters = [
            'partner_user_id' => $_GET['c_partner'] ?? '',
            'status'          => $_GET['c_status']  ?? 'all',
            'search'          => $_GET['c_q']       ?? '',
            'limit'           => 500,
        ];

        $enrollments = Enrollment::allWithStats();
        $enrollEvents = Enrollment::eventsForMany(array_column($enrollments, 'id'), 50);

        $data = [
            /* Members + enrollments */
            'users'            => User::listWithStats($memberFilters),
            'userRoleCounts'   => User::roleCounts(),
            'enrollments'      => $enrollments,
            'enrollmentEvents' => $enrollEvents,
            'enrollSummary'    => Enrollment::statusSummary(),
            'enrollTierCounts' => Enrollment::tierCounts(),
            'enrollTrends'     => Enrollment::trends12m(),

            /* Programs */
            'programs'         => Program::allWithStats(),
            'programStats'     => Program::statsSummary(),
            'programPopular'   => Program::popularity(),

            /* Payments */
            'payments'         => Payment::listWithFilters($paymentFilters),
            'paymentSummary'   => Payment::statusSummary(),
            'paymentAging'     => Payment::agingReport(),
            'paymentMethods'   => Payment::methodConfig(),
            'paymentRevenue7d' => Payment::revenueDaily(7),

            /* Attendance */
            'attendance'          => Attendance::listWithFilters($attendanceFilters),
            'attendanceStats'     => Attendance::todayStats(),
            'attendanceToday'     => Attendance::todayAll(),
            'attendanceCurrently' => Attendance::currentlyIn(),
            'attendanceHeatmap'   => Attendance::heatmap(30),
            'attendanceStreaks'   => Attendance::topStreaks(10),
            'attendanceInactive'  => Attendance::inactiveMembers(7, 20),
            'attendanceSeasonal'  => Attendance::seasonalTrends(12),
            'attendanceRetention' => Attendance::retentionCorrelation(),

            /* Partners (§1.5) */
            'partners'            => PartnerProfile::listWithMetrics($partnerFilters),
            'partnerSummary'      => PartnerProfile::summary(),
            'partnerAcquisition'  => PartnerProfile::acquisitionTrend(12),
            'partnerTypeBreak'    => PartnerProfile::typeBreakdown(),
            'partnerTop'          => PartnerProfile::topPerformers(8),
            'partnerB2bClients'   => B2BClient::listWithFilters($clientFilters),
            'b2bSummary'          => B2BClient::summary(),
            'b2bByPartner'        => B2BClient::countsByPartner(),

            /* Sales (§1.5) */
            'partnerSales'        => PartnerSale::listWithFilters($salesFilters),
            'saleSummary'         => PartnerSale::summary(),

            /* Payouts (§1.5) */
            'payouts'             => PayoutRequest::listWithFilters($payoutFilters),
            'payoutSummary'       => PayoutRequest::summary(),
            'payoutBatches'       => PayoutRequest::listBatches(50),
            'payoutReconciliation' => PayoutRequest::monthlyReconciliation(12),

            /* Pass-through filter state */
            'memberFilters'       => $memberFilters,
            'paymentFilters'      => $paymentFilters,
            'attendanceFilters'   => $attendanceFilters,
            'partnerFilters'      => $partnerFilters,
            'salesFilters'        => $salesFilters,
            'payoutFilters'       => $payoutFilters,
            'clientFilters'       => $clientFilters,
        ];

        $this->view(
            'admin/dashboard',
            ['pageTitle' => 'Admin Dashboard', 'tab' => $tab, 'data' => $data],
            'portal'
        );
    }

    /* =============================================================
     |  AUDIT LOGGING
     ============================================================= */

    private function audit(
        string $action,
        ?string $entityType = null,
        ?string $entityId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $notes = null
    ): void {
        // 🔐 SECURITY: Automatically strip sensitive PII/secrets
        // from user arrays before they touch the audit log.
        if ($entityType === 'user') {
            $before = $this->sanitizeUserForAudit($before);
            $after  = $this->sanitizeUserForAudit($after);
        }

        audit_log($action, $entityType, $entityId, $before, $after, $notes);
    }

    /**
     * Strips secrets from a user array so they never leak into
     * the audit log viewer or database.
     */
    private function sanitizeUserForAudit(?array $user): ?array
    {
        if ($user === null) return null;

        unset(
            $user['password_hash'],
            $user['mfa_secret'],
            $user['mfa_recovery_codes'],
            $user['otp_code'],
            $user['otp_expires_at'],
            $user['remember_token']
        );

        return $user;
    }

    /* =============================================================
     |  PROGRAMS  (spec §1.1)
     ============================================================= */

    public function saveProgram(): void
    {
        csrf_verify();

        $id   = trim((string)($this->input('id') ?? ''));
        $name = trim((string)$this->input('name'));
        $isEdit = $id !== '';
        $before = $isEdit ? Program::find($id) : null;

        $errors = [];

        if ($name === '') {
            $errors[] = 'Program name is required.';
        } elseif (mb_strlen($name) > 190) {
            $errors[] = 'Program name must be 190 characters or fewer.';
        } elseif (Program::nameExists($name, $isEdit ? $id : null)) {
            $errors[] = 'A program named "' . $name . '" already exists.';
        }

        $tier = (string)$this->input('tier', 'standard');
        if (!in_array($tier, ['digital', 'standard', 'premium', 'b2b'], true)) {
            $errors[] = 'Please choose a valid tier.';
        }

        $priceRaw = $this->input('price');
        $price    = (float)$priceRaw;
        if ($priceRaw === null || $priceRaw === '' || $price <= 0) {
            $errors[] = 'Price must be a positive number greater than zero.';
        }

        $duration = (int)($this->input('duration_days') ?: 30);
        if ($duration < 1 || $duration > 3650) {
            $errors[] = 'Duration must be between 1 and 3650 days.';
        }

        $grace = (int)($this->input('grace_period_days') ?? 3);
        if ($grace < 0 || $grace > 90) {
            $errors[] = 'Grace period must be between 0 and 90 days.';
        }

        $opCost = (float)($this->input('operational_cost') ?? 0);
        if ($opCost < 0) {
            $errors[] = 'Operational cost cannot be negative.';
        }

        $icon = trim((string)$this->input('icon'));
        if ($icon === '') $icon = Program::tierFallbackIcon($tier);
        if (mb_strlen($icon) > 8) $icon = mb_substr($icon, 0, 8);

        $featuresInput = $this->input('features');
        if ($featuresInput === null) {
            $featuresInput = $this->input('features_text', '');
        }
        $featuresJson = Program::encodeFeatures(
            is_array($featuresInput) ? $featuresInput : (string)$featuresInput
        );

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=programs');
        }

        $payload = [
            'name'              => $name,
            'tier'              => $tier,
            'price'             => $price,
            'duration_days'     => $duration,
            'description'       => (string)$this->input('description', ''),
            'active'            => $this->input('active') !== null ? 1 : 0,
            'icon'              => $icon,
            'features'          => $featuresJson,
            'operational_cost'  => $opCost,
            'portal_visible'    => $this->input('portal_visible') !== null ? 1 : 0,
            'auto_renew'        => $this->input('auto_renew') !== null ? 1 : 0,
            'grace_period_days' => $grace,
        ];

        if ($isEdit) {
            Program::update($id, $payload);
            $after = Program::find($id);
            $this->audit(
                'program.updated',
                'program',
                $id,
                $before,
                $after,
                'Updated program "' . $name . '"'
            );
        } else {
            $payload['sort_order'] = Program::nextSortOrder();
            $created = Program::create($payload);
            $this->audit(
                'program.created',
                'program',
                $created['id'] ?? null,
                null,
                $created,
                'Created program "' . $name . '"'
            );
        }

        $floor   = Program::operationalCostFloor();
        $message = $isEdit ? 'Program updated.' : 'Program created.';
        if ($floor > 0 && $price < $floor) {
            $message .= ' ⚠️ Note: this price (' . peso($price) . ') is below the '
                . 'configured operational cost (' . peso($floor) . ').';
        }

        flash('success', $message);
        $this->redirect('/admin-dashboard?tab=programs');
    }

    public function deleteProgram(): void
    {
        csrf_verify();
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/admin-dashboard?tab=programs');
        }

        $id = (string)$this->input('id');
        $program = $id !== '' ? Program::find($id) : null;
        if (!$program) {
            flash('error', 'Program not found.');
            $this->redirect('/admin-dashboard?tab=programs');
        }
        Program::delete($id);
        $this->audit(
            'program.deleted',
            'program',
            $id,
            $program,
            null,
            'Deleted program "' . ($program['name'] ?? '') . '"'
        );
        flash('success', 'Program deleted. Existing enrollments were preserved.');
        $this->redirect('/admin-dashboard?tab=programs');
    }

    public function toggleProgram(): void
    {
        csrf_verify();
        $id     = (string)$this->input('id');
        $active = (int)$this->input('active');
        $program = $id !== '' ? Program::find($id) : null;
        if (!$program) {
            flash('error', 'Program not found.');
            $this->redirect('/admin-dashboard?tab=programs');
        }
        Program::update($id, ['active' => $active ? 1 : 0]);
        $this->audit(
            'program.' . ($active ? 'activated' : 'deactivated'),
            'program',
            $id,
            ['active' => (int)$program['active']],
            ['active' => $active ? 1 : 0],
            'Set program "' . ($program['name'] ?? '') . '" to '
                . ($active ? 'active' : 'inactive')
        );
        flash('success', 'Program ' . ($active ? 'activated' : 'deactivated') . '.');
        $this->redirect('/admin-dashboard?tab=programs');
    }

    public function toggleProgramPreview(): void
    {
        csrf_verify();
        $id      = (string)$this->input('id');
        $visible = (int)$this->input('portal_visible');
        $program = $id !== '' ? Program::find($id) : null;
        if (!$program) {
            flash('error', 'Program not found.');
            $this->redirect('/admin-dashboard?tab=programs');
        }
        Program::update($id, ['portal_visible' => $visible ? 1 : 0]);
        $this->audit(
            'program.preview.' . ($visible ? 'shown' : 'hidden'),
            'program',
            $id,
            ['portal_visible' => (int)$program['portal_visible']],
            ['portal_visible' => $visible ? 1 : 0],
            ($visible ? 'Shown' : 'Hidden') . ' in member portal: "'
                . ($program['name'] ?? '') . '"'
        );
        flash('success', $visible
            ? 'Program is now visible in the member portal.'
            : 'Program hidden from the member portal.');
        $this->redirect('/admin-dashboard?tab=programs');
    }

    public function duplicateProgram(): void
    {
        csrf_verify();
        $id  = (string)$this->input('id');
        $src = $id !== '' ? Program::find($id) : null;
        if (!$src) {
            flash('error', 'Program not found.');
            $this->redirect('/admin-dashboard?tab=programs');
        }

        $base = $src['name'] . ' (Copy)';
        $name = $base;
        $n    = 2;
        while (Program::nameExists($name)) {
            $name = $base . ' ' . $n++;
            if ($n > 50) {
                $name = $base . ' ' . bin2hex(random_bytes(3));
                break;
            }
        }

        $created = Program::create([
            'name'              => $name,
            'tier'              => $src['tier'],
            'price'             => $src['price'],
            'duration_days'     => $src['duration_days'],
            'description'       => $src['description'],
            'active'            => 0,
            'icon'              => $src['icon'] ?? Program::tierFallbackIcon($src['tier']),
            'features'          => $src['features'],
            'operational_cost'  => $src['operational_cost'] ?? 0,
            'portal_visible'    => 0,
            'auto_renew'        => $src['auto_renew'] ?? 0,
            'grace_period_days' => $src['grace_period_days'] ?? 3,
            'sort_order'        => Program::nextSortOrder(),
        ]);

        $this->audit(
            'program.duplicated',
            'program',
            $created['id'] ?? null,
            $src,
            $created,
            'Duplicated "' . $src['name'] . '" as "' . $name . '"'
        );

        flash('success', 'Program duplicated. Edit the copy and activate when ready.');
        $this->redirect('/admin-dashboard?tab=programs');
    }

    public function reorderPrograms(): void
    {
        csrf_verify();
        $order = $this->input('order', []);
        if (!is_array($order) || $order === []) {
            $this->json(['status' => 'error', 'message' => 'No order provided.'], 400);
        }

        $snapshot = [];
        $i = 0;
        foreach ($order as $programId) {
            $programId = trim((string)$programId);
            if ($programId === '') {
                $i++;
                continue;
            }
            $program = Program::find($programId);
            if (!$program) {
                $i++;
                continue;
            }
            $snapshot[$programId] = (int)($program['sort_order'] ?? 0);
            Program::update($programId, ['sort_order' => $i]);
            $i++;
        }

        $this->audit(
            'program.reordered',
            'program',
            null,
            $snapshot,
            ['count' => $i],
            'Reordered ' . $i . ' program(s)'
        );

        if (
            str_contains(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'json')
            || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        ) {
            $this->json(['status' => 'success']);
        }

        flash('success', 'Program order saved.');
        $this->redirect('/admin-dashboard?tab=programs');
    }

    /* =============================================================
     |  MEMBERS  (spec §1.2)
     ============================================================= */

    public function updateMember(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $existing = User::find($id);
        if (!$existing) {
            flash('error', 'Member not found.');
            $this->redirect('/admin-dashboard?tab=members');
        }

        $errors = [];

        $name  = trim((string)$this->input('full_name'));
        $email = trim((string)$this->input('email'));
        $role  = (string)$this->input('role', $existing['role']);

        if ($name === '') {
            $errors[] = 'Full name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        } elseif (User::emailExists($email, $id)) {
            $errors[] = 'Email already exists.';
        }
        if (!in_array($role, ['admin', 'staff', 'partner', 'user'], true)) {
            $errors[] = 'Invalid role.';
        }

        if ($existing['role'] === 'admin' && $role !== 'admin') {
            $adminCount = (int)\App\Config\Database::pdo()
                ->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")
                ->fetchColumn();
            if ($adminCount <= 1) {
                $errors[] = 'Cannot demote the last remaining admin.';
            }
        }

        $newPassword = (string)$this->input('new_password');
        if ($newPassword !== '' && strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=members');
        }

        $data = [
            'full_name'      => $name,
            'email'          => $email,
            'role'           => $role,
            'email_verified' => $this->input('email_verified') ? 1 : 0,
            'title'          => (string)$this->input('title', $existing['title'] ?? 'none'),
            'gender'         => (string)$this->input('gender', $existing['gender'] ?? 'prefer_not_to_say'),
            'gender_self_describe' => $this->input('gender_self_describe'),
            'phone'          => $this->input('phone'),
        ];
        if ($newPassword !== '') $data['password'] = $newPassword;

        User::updateProfile($id, $data);

        $this->audit(
            'member.updated',
            'user',
            $id,
            $this->sanitizeUserForAudit($existing),
            $this->sanitizeUserForAudit(User::find($id)),
            'Updated ' . ($existing['full_name'] ?: $existing['email'])
        );

        flash('success', 'Member updated.');
        $this->redirect('/admin-dashboard?tab=members');
    }

    public function deleteMember(): void
    {
        csrf_verify();
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/admin-dashboard?tab=members');
        }

        $id = (string)$this->input('id');
        $existing = User::find($id);
        if (!$existing) {
            flash('error', 'Member not found.');
            $this->redirect('/admin-dashboard?tab=members');
        }

        $ok = User::deleteUser($id);
        if (!$ok) {
            flash('error', 'Cannot delete — you must keep at least one admin account.');
            $this->redirect('/admin-dashboard?tab=members');
        }

        $this->audit(
            'member.deleted',
            'user',
            $id,
            $this->sanitizeUserForAudit($existing),
            null,
            'Deleted ' . ($existing['full_name'] ?: $existing['email'])
        );

        flash('success', 'Member deleted.');
        $this->redirect('/admin-dashboard?tab=members');
    }

    public function changeMemberRole(): void
    {
        csrf_verify();

        $id   = (string)$this->input('id');
        $role = (string)$this->input('role');

        $user = User::find($id);
        if (!$user) {
            flash('error', 'Member not found.');
            $this->redirect('/admin-dashboard?tab=members');
        }
        if (!in_array($role, ['admin', 'staff', 'partner', 'user'], true)) {
            flash('error', 'Invalid role.');
            $this->redirect('/admin-dashboard?tab=members');
        }

        if ($user['role'] === 'admin' && $role !== 'admin') {
            $adminCount = (int)\App\Config\Database::pdo()
                ->query("SELECT COUNT(*) FROM `users` WHERE `role` = 'admin'")
                ->fetchColumn();
            if ($adminCount <= 1) {
                flash('error', 'Cannot demote the last remaining admin.');
                $this->redirect('/admin-dashboard?tab=members');
            }
        }

        User::update($id, ['role' => $role]);

        $this->audit(
            'member.role_changed',
            'user',
            $id,
            ['role' => $user['role']],
            ['role' => $role],
            'Role changed: ' . $user['role'] . ' → ' . $role
        );

        flash('success', 'Role updated.');
        $this->redirect('/admin-dashboard?tab=members');
    }

    public function resetMemberPassword(): void
    {
        csrf_verify();

        $id  = (string)$this->input('id');
        $pwd = (string)$this->input('password');

        $user = User::find($id);
        if (!$user) {
            flash('error', 'Member not found.');
            $this->redirect('/admin-dashboard?tab=members');
        }
        if (strlen($pwd) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            $this->redirect('/admin-dashboard?tab=members');
        }

        User::update($id, [
            'password_hash' => password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        $this->audit(
            'member.password_reset',
            'user',
            $id,
            null,
            null,
            'Admin reset password for ' . ($user['full_name'] ?: $user['email'])
        );

        flash('success', 'Password reset.');
        $this->redirect('/admin-dashboard?tab=members');
    }

    public function exportMembers(): void
    {
        $filters = [
            'role'        => $_GET['role']        ?? 'all',
            'status'      => $_GET['status']      ?? 'all',
            'tier'        => $_GET['tier']        ?? 'all',
            'joined_from' => $_GET['joined_from'] ?? '',
            'joined_to'   => $_GET['joined_to']   ?? '',
            'search'      => $_GET['q']           ?? '',
            'limit'       => 10000,
        ];

        $rows = User::listWithStats($filters);

        $this->audit(
            'member.exported',
            'user',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' member row(s)'
        );

        $filename = 'members-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'ID',
            'Full Name',
            'Email',
            'Role',
            'Email Verified',
            'Joined',
            'Last Login',
            'Enrollment Tier',
            'Enrollment Status',
            'Program',
            'Start Date',
            'End Date',
            'Days Left',
            'Balance',
            'Check-ins (30d)',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['full_name'],
                $r['email'],
                User::roleLabel($r['role']),
                $r['email_verified'] ? 'Yes' : 'No',
                $r['created_date'],
                $r['last_login_at'] ?? '',
                $r['enrollment_tier'] ? strtoupper($r['enrollment_tier']) : '',
                $r['enrollment_status'] ? Enrollment::statusLabel($r['enrollment_status']) : '',
                $r['enrollment_program'] ?? '',
                $r['enrollment_start'] ?? '',
                $r['enrollment_end'] ?? '',
                $r['enrollment_days_left'] ?? '',
                number_format((float)($r['enrollment_balance'] ?? 0), 2, '.', ''),
                (int)($r['attendance_30d'] ?? 0),
            ]);
        }
        fclose($out);
        exit;
    }

    /* =============================================================
     |  ENROLLMENTS  (spec §1.2)
     ============================================================= */

    public function approveEnrollment(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $e  = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        $before = $e;
        Enrollment::approve($id, current_user()['id'] ?? null, 'Approved by admin');
        $after  = Enrollment::find($id);

        $this->audit(
            'enrollment.approved',
            'enrollment',
            $id,
            ['status' => $before['status'] ?? 'pending'],
            ['status' => $after['status'] ?? 'active'],
            'Approved enrollment for ' . ($e['user_name'] ?? '')
        );

        flash('success', 'Enrollment activated.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function updateEnrollment(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $e  = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        $errors = [];

        $status = (string)$this->input('status', $e['status']);
        if (!in_array($status, ['active', 'pending', 'expired', 'paused', 'cancelled'], true)) {
            $errors[] = 'Invalid status.';
        }

        $start = (string)$this->input('start_date', $e['start_date'] ?? '');
        $end   = (string)$this->input('end_date',   $e['end_date']   ?? '');

        if ($start !== '' && $end !== '' && strtotime($end) < strtotime($start)) {
            $errors[] = 'Enrollment end date must be after start date.';
        }

        $balance = (float)($this->input('balance') ?? $e['balance'] ?? 0);
        if ($balance < 0) {
            $errors[] = 'Balance cannot be negative.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::update($id, [
            'status'     => $status,
            'start_date' => $start ?: null,
            'end_date'   => $end   ?: null,
            'balance'    => $balance,
        ]);

        $this->audit(
            'enrollment.updated',
            'enrollment',
            $id,
            $e,
            Enrollment::find($id),
            'Updated enrollment ' . $id
        );

        flash('success', 'Enrollment updated.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function extendEnrollment(): void
    {
        csrf_verify();

        $id   = (string)$this->input('id');
        $days = (int)$this->input('days');

        if ($days < 1 || $days > 3650) {
            flash('error', 'Extension must be between 1 and 3650 days.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        $e = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::extend($id, $days, current_user()['id'] ?? null, 'Extended by ' . $days . ' days');

        $this->audit(
            'enrollment.extended',
            'enrollment',
            $id,
            ['end_date' => $e['end_date'] ?? null],
            ['end_date' => Enrollment::find($id)['end_date'] ?? null],
            'Extended by ' . $days . ' day(s)'
        );

        flash('success', 'Membership extended by ' . $days . ' days.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function pauseEnrollment(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $e  = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }
        if ($e['status'] !== 'active') {
            flash('error', 'Only active enrollments can be paused.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::pause($id, current_user()['id'] ?? null, 'Paused by admin');

        $this->audit(
            'enrollment.paused',
            'enrollment',
            $id,
            ['status' => 'active'],
            ['status' => 'paused'],
            'Paused enrollment for ' . ($e['user_name'] ?? '')
        );

        flash('success', 'Enrollment paused. The clock is frozen until resumed.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function resumeEnrollment(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $e  = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }
        if ($e['status'] !== 'paused') {
            flash('error', 'Only paused enrollments can be resumed.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::resume($id, current_user()['id'] ?? null, 'Resumed by admin');

        $this->audit(
            'enrollment.resumed',
            'enrollment',
            $id,
            ['status' => 'paused'],
            ['status' => 'active'],
            'Resumed enrollment for ' . ($e['user_name'] ?? '')
        );

        flash('success', 'Enrollment resumed. Paused days were added back to the end date.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function cancelEnrollment(): void
    {
        csrf_verify();

        $id     = (string)$this->input('id');
        $reason = (string)$this->input('reason', 'other');

        $e = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::cancel($id, $reason, current_user()['id'] ?? null, 'Cancelled by admin');

        $this->audit(
            'enrollment.cancelled',
            'enrollment',
            $id,
            ['status' => $e['status'] ?? ''],
            ['status' => 'cancelled', 'reason' => $reason],
            'Cancelled enrollment — reason: ' . Enrollment::cancelReasonLabel($reason)
        );

        flash('success', 'Enrollment cancelled.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function deleteEnrollment(): void
    {
        csrf_verify();
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/admin-dashboard?tab=enrollments');
        }

        $id = (string)$this->input('id');
        $e  = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::delete($id);

        $this->audit(
            'enrollment.deleted',
            'enrollment',
            $id,
            $e,
            null,
            'Deleted enrollment for ' . ($e['user_name'] ?? '')
        );

        flash('success', 'Enrollment deleted.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function upgradeEnrollment(): void
    {
        $this->changeEnrollmentTier('upgrade');
    }

    public function downgradeEnrollment(): void
    {
        $this->changeEnrollmentTier('downgrade');
    }

    private function changeEnrollmentTier(string $direction): void
    {
        csrf_verify();

        $id      = (string)$this->input('id');
        $newTier = (string)$this->input('tier');

        if (!in_array($newTier, ['digital', 'standard', 'premium'], true)) {
            flash('error', 'Invalid tier.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        $e = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        $newProgram = null;
        if (!empty($e['program_id'])) {
            $sameTier = Program::where(['tier' => $newTier, 'active' => 1], 'sort_order ASC', 1);
            $newProgram = $sameTier[0] ?? null;
        }
        if (!$newProgram) {
            $fallback = Program::where(['tier' => $newTier], 'sort_order ASC', 1);
            $newProgram = $fallback[0] ?? null;
        }

        $newPrice = (float)($newProgram['price'] ?? 0);

        Enrollment::changeTier(
            $id,
            $newTier,
            $newPrice,
            current_user()['id'] ?? null,
            ucfirst($direction) . ' by admin'
        );

        $this->audit(
            'enrollment.' . $direction,
            'enrollment',
            $id,
            ['tier' => $e['tier'] ?? ''],
            ['tier' => $newTier],
            ucfirst($direction) . ' ' . ($e['user_name'] ?? '') . ' from '
                . $e['tier'] . ' to ' . $newTier
        );

        flash('success', 'Enrollment ' . $direction . 'd. Pro-rata balance was adjusted.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function expireEnrollment(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $e  = Enrollment::find($id);
        if (!$e) {
            flash('error', 'Enrollment not found.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        Enrollment::expire($id, current_user()['id'] ?? null, 'Manually expired by admin');

        $this->audit(
            'enrollment.expired',
            'enrollment',
            $id,
            ['status' => $e['status'] ?? ''],
            ['status' => 'expired'],
            'Manually expired enrollment for ' . ($e['user_name'] ?? '')
        );

        flash('success', 'Enrollment marked as expired.');
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function batchRenewEnrollments(): void
    {
        csrf_verify();

        $ids = $this->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            flash('error', 'No enrollments selected.');
            $this->redirect('/admin-dashboard?tab=enrollments');
        }

        $extraDays = (int)($this->input('extra_days') ?? 0);
        if ($extraDays < 0)  $extraDays = 0;
        if ($extraDays > 90) $extraDays = 90;

        $actor  = current_user()['id'] ?? null;
        $count  = 0;
        $failed = [];

        foreach ($ids as $id) {
            $id = trim((string)$id);
            if ($id === '') continue;

            $e = Enrollment::find($id);
            if (!$e) {
                $failed[] = $id;
                continue;
            }

            Enrollment::renew($id, $extraDays, $actor);
            $count++;
        }

        $this->audit(
            'enrollment.batch_renewed',
            'enrollment',
            null,
            null,
            ['count' => $count, 'extra_days' => $extraDays],
            'Batch-renewed ' . $count . ' enrollment(s)'
        );

        $message = 'Renewed ' . $count . ' enrollment' . ($count !== 1 ? 's' : '') . '.';
        if ($failed) {
            $message .= ' ' . count($failed) . ' could not be found.';
        }
        flash($count > 0 ? 'success' : 'error', $message);
        $this->redirect('/admin-dashboard?tab=enrollments');
    }

    public function exportEnrollments(): void
    {
        $rows = Enrollment::allWithStats(10000);

        $this->audit(
            'enrollment.exported',
            'enrollment',
            null,
            null,
            ['count' => count($rows)],
            'Exported ' . count($rows) . ' enrollment row(s)'
        );

        $filename = 'enrollments-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Enrollment ID',
            'Member',
            'Member ID',
            'Program',
            'Tier',
            'Status',
            'Start Date',
            'End Date',
            'Days Left',
            'Balance',
            'Renewed From',
            'Auto-Renew',
            'Cancel Reason',
            'Cancelled Date',
            'Check-ins (30d)',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['user_name'],
                $r['user_id'],
                $r['program_name'],
                strtoupper($r['tier']),
                Enrollment::statusLabel($r['status']),
                $r['start_date'] ?? '',
                $r['end_date'] ?? '',
                $r['days_remaining'] ?? '',
                number_format((float)($r['balance'] ?? 0), 2, '.', ''),
                $r['renewed_from_id'] ?? '',
                (int)($r['auto_renew'] ?? 0) ? 'Yes' : 'No',
                $r['cancel_reason'] ? Enrollment::cancelReasonLabel($r['cancel_reason']) : '',
                $r['cancelled_date'] ?? '',
                (int)($r['attendance_30d'] ?? 0),
            ]);
        }
        fclose($out);
        exit;
    }

    /* =============================================================
     |  PAYMENTS  (spec §1.3)
     ============================================================= */

    public function confirmPayment(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $p  = Payment::find($id);
        if (!$p) {
            flash('error', 'Payment not found.');
            $this->redirect('/admin-dashboard?tab=payments');
        }

        Payment::markPaid($id, current_user()['id'] ?? null);

        if (!empty($p['user_id'])) {
            $pending = Enrollment::where(['user_id' => $p['user_id'], 'status' => 'pending']);
            foreach ($pending as $e) {
                if ($e['tier'] === $p['tier']) {
                    Enrollment::approve(
                        $e['id'],
                        current_user()['id'] ?? null,
                        'Auto-approved on payment confirmation'
                    );
                    break;
                }
            }
        }

        $this->audit(
            'payment.confirmed',
            'payment',
            $id,
            ['status' => $p['status'] ?? 'pending'],
            ['status' => 'paid'],
            'Confirmed payment ' . $id
        );

        flash('success', 'Payment confirmed — enrollment activated.');
        $this->redirect('/admin-dashboard?tab=payments');
    }

    public function bulkConfirmPayments(): void
    {
        csrf_verify();

        $ids = $this->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            flash('error', 'No payments selected.');
            $this->redirect('/admin-dashboard?tab=payments');
        }

        $actor = current_user()['id'] ?? null;
        $result = Payment::bulkConfirm($ids, $actor);

        foreach ($result['ids'] as $pid) {
            $p = Payment::find($pid);
            if (!$p || empty($p['user_id'])) continue;

            $pending = Enrollment::where(['user_id' => $p['user_id'], 'status' => 'pending']);
            foreach ($pending as $e) {
                if ($e['tier'] === $p['tier']) {
                    Enrollment::approve($e['id'], $actor, 'Auto-approved on bulk confirm');
                    break;
                }
            }
        }

        $this->audit(
            'payment.bulk_confirmed',
            'payment',
            null,
            null,
            ['count' => $result['confirmed'], 'skipped' => $result['skipped']],
            'Bulk-confirmed ' . $result['confirmed'] . ' payment(s)'
        );

        $msg = 'Confirmed ' . $result['confirmed'] . ' payment' . ($result['confirmed'] !== 1 ? 's' : '') . '.';
        if ($result['skipped'] > 0) {
            $msg .= ' Skipped ' . $result['skipped'] . ' (already paid or not found).';
        }
        flash($result['confirmed'] > 0 ? 'success' : 'error', $msg);
        $this->redirect('/admin-dashboard?tab=payments');
    }

    public function refundPayment(): void
    {
        csrf_verify();

        $id     = (string)$this->input('id');
        $amount = (float)$this->input('refund_amount');
        $reason = trim((string)$this->input('refund_reason'));

        $p = Payment::find($id);
        if (!$p) {
            flash('error', 'Payment not found.');
            $this->redirect('/admin-dashboard?tab=payments');
        }

        $errors = [];
        if ($amount <= 0) $errors[] = 'Refund amount must be greater than zero.';

        $original = (float)$p['amount'];
        $alreadyRefunded = (float)($p['refund_amount'] ?? 0);
        $remaining = $original - $alreadyRefunded;
        if ($amount > $remaining + 0.01) {
            $errors[] = 'Refund exceeds the remaining refundable amount ('
                . peso($remaining) . ').';
        }
        if ($reason === '') $errors[] = 'Please provide a refund reason.';
        if (!in_array($p['status'], ['paid', 'partial_refund'], true)) {
            $errors[] = 'Only paid or partially-refunded payments can be refunded.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=payments');
        }

        $before = $p;
        Payment::refund($id, $amount, $reason, current_user()['id'] ?? null);
        $after = Payment::find($id);

        $this->audit(
            'payment.refunded',
            'payment',
            $id,
            $before,
            $after,
            'Refunded ' . peso($amount) . ' — reason: ' . $reason
        );

        flash('success', 'Refund of ' . peso($amount) . ' recorded.');
        $this->redirect('/admin-dashboard?tab=payments');
    }

    public function markPaymentFailed(): void
    {
        csrf_verify();

        $id     = (string)$this->input('id');
        $reason = trim((string)$this->input('reason'));

        $p = Payment::find($id);
        if (!$p) {
            flash('error', 'Payment not found.');
            $this->redirect('/admin-dashboard?tab=payments');
        }
        if ($p['status'] === 'paid') {
            flash('error', 'Cannot mark a paid payment as failed. Refund it first.');
            $this->redirect('/admin-dashboard?tab=payments');
        }

        Payment::markFailed($id, $reason ?: 'Marked by admin', current_user()['id'] ?? null);

        $this->audit(
            'payment.failed',
            'payment',
            $id,
            ['status' => $p['status'] ?? ''],
            ['status' => 'failed', 'reason' => $reason],
            'Marked payment as failed: ' . $reason
        );

        flash('success', 'Payment marked as failed.');
        $this->redirect('/admin-dashboard?tab=payments');
    }

    public function paymentReceipt(): void
    {
        $id = (string)($_GET['id'] ?? '');
        $p  = Payment::find($id);

        if (!$p) {
            http_response_code(404);
            exit('Receipt not found.');
        }

        $user = !empty($p['user_id']) ? User::find($p['user_id']) : null;

        if (empty($p['receipt_no'])) {
            $p['receipt_no'] = Payment::nextReceiptNo();
            Payment::update($id, ['receipt_no' => $p['receipt_no']]);
        }

        $this->audit(
            'payment.receipt_viewed',
            'payment',
            $id,
            null,
            null,
            'Viewed receipt ' . $p['receipt_no']
        );

        header('Content-Type: text/html; charset=utf-8');
?>
        <!doctype html>
        <html lang="en">

        <head>
            <meta charset="utf-8">
            <title>Receipt <?= htmlspecialchars($p['receipt_no']) ?></title>
            <style>
                * {
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: #f3f4f6;
                    margin: 0;
                    padding: 40px 20px;
                    color: #111827;
                }

                .receipt {
                    max-width: 640px;
                    margin: 0 auto;
                    background: #fff;
                    border-radius: 12px;
                    padding: 40px;
                    box-shadow: 0 10px 30px -10px rgba(0, 0, 0, 0.15);
                }

                .brand {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    margin-bottom: 32px;
                }

                .brand-mark {
                    width: 48px;
                    height: 48px;
                    border-radius: 999px;
                    background: #00A6A6;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    font-size: 24px;
                }

                .brand-name {
                    font-size: 22px;
                    font-weight: 800;
                    letter-spacing: 1px;
                }

                .brand-sub {
                    font-size: 10px;
                    letter-spacing: 4px;
                    color: #00A6A6;
                    font-weight: 700;
                }

                .head {
                    display: flex;
                    justify-content: space-between;
                    padding-bottom: 24px;
                    border-bottom: 2px solid #00A6A6;
                    margin-bottom: 24px;
                }

                .head h1 {
                    margin: 0;
                    font-size: 32px;
                    letter-spacing: 2px;
                }

                .head .meta {
                    text-align: right;
                    font-size: 13px;
                    color: #6b7280;
                }

                .head .meta strong {
                    color: #111827;
                }

                dl {
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 16px 24px;
                    margin: 0;
                }

                dt {
                    font-size: 10px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    color: #9ca3af;
                }

                dd {
                    margin: 4px 0 0;
                    font-size: 15px;
                    font-weight: 600;
                    color: #111827;
                }

                .amount-box {
                    margin-top: 32px;
                    padding: 24px;
                    border-radius: 12px;
                    background: linear-gradient(135deg, #00A6A6 0%, #008c8c 100%);
                    color: #fff;
                    text-align: center;
                }

                .amount-box .lbl {
                    font-size: 11px;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 2px;
                    opacity: 0.85;
                }

                .amount-box .amt {
                    font-size: 42px;
                    font-weight: 800;
                    margin-top: 6px;
                }

                .status {
                    display: inline-block;
                    margin-top: 12px;
                    padding: 4px 12px;
                    border-radius: 999px;
                    font-size: 11px;
                    font-weight: 700;
                    letter-spacing: 1px;
                    text-transform: uppercase;
                    background: rgba(255, 255, 255, 0.2);
                }

                .foot {
                    margin-top: 40px;
                    padding-top: 24px;
                    border-top: 1px solid #e5e7eb;
                    font-size: 12px;
                    color: #6b7280;
                    text-align: center;
                }

                .print-btn {
                    display: block;
                    margin: 24px auto 0;
                    padding: 12px 32px;
                    background: #111827;
                    color: #fff;
                    border: 0;
                    border-radius: 999px;
                    font-size: 14px;
                    font-weight: 700;
                    cursor: pointer;
                }

                .print-btn:hover {
                    background: #00A6A6;
                }

                @media print {
                    body {
                        background: #fff;
                        padding: 0;
                    }

                    .receipt {
                        box-shadow: none;
                        padding: 20px;
                    }

                    .print-btn {
                        display: none;
                    }
                }
            </style>
        </head>

        <body>
            <div class="receipt">
                <div class="brand">
                    <div class="brand-mark">💪</div>
                    <div>
                        <div class="brand-name">POWERMAGZ</div>
                        <div class="brand-sub">GYM</div>
                    </div>
                </div>

                <div class="head">
                    <h1>RECEIPT</h1>
                    <div class="meta">
                        <div><strong><?= htmlspecialchars($p['receipt_no']) ?></strong></div>
                        <div>Issued <?= htmlspecialchars(date('M j, Y')) ?></div>
                    </div>
                </div>

                <dl>
                    <div>
                        <dt>Member</dt>
                        <dd><?= htmlspecialchars($p['user_name']) ?></dd>
                    </div>
                    <div>
                        <dt>Email</dt>
                        <dd><?= htmlspecialchars($user['email'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt>Description</dt>
                        <dd><?= htmlspecialchars($p['description'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt>Tier</dt>
                        <dd><?= htmlspecialchars(strtoupper($p['tier'] ?? '—')) ?></dd>
                    </div>
                    <div>
                        <dt>Payment Method</dt>
                        <dd><?= htmlspecialchars(Payment::methodLabel($p['method'])) ?></dd>
                    </div>
                    <div>
                        <dt>Reference No.</dt>
                        <dd><?= htmlspecialchars($p['reference_no'] ?? '—') ?></dd>
                    </div>
                    <div>
                        <dt>Paid Date</dt>
                        <dd><?= htmlspecialchars($p['paid_date']) ?></dd>
                    </div>
                    <div>
                        <dt>Confirmed</dt>
                        <dd><?= htmlspecialchars($p['confirmed_date'] ?? '—') ?></dd>
                    </div>
                </dl>

                <div class="amount-box">
                    <div class="lbl">Amount</div>
                    <div class="amt">₱<?= number_format((float)$p['amount'], 2) ?></div>
                    <div class="status"><?= htmlspecialchars(strtoupper($p['status'])) ?></div>
                </div>

                <?php if (!empty($p['refund_amount']) && (float)$p['refund_amount'] > 0): ?>
                    <div style="margin-top:24px;padding:16px;border-radius:8px;background:#fef3c7;border:1px solid #fde68a;">
                        <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#92400e;">
                            Refund Applied
                        </div>
                        <div style="margin-top:6px;font-size:14px;color:#78350f;">
                            ₱<?= number_format((float)$p['refund_amount'], 2) ?> —
                            <?= htmlspecialchars($p['refund_reason'] ?? '') ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="foot">
                    Zone 4, Matagbac, Tabaco City, Albay · 0961 804 1523<br>
                    Thank you for choosing PowerMagz Gym.
                </div>

                <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>
            </div>
        </body>

        </html>
<?php
        exit;
    }

    public function exportPayments(): void
    {
        $filters = [
            'status' => $_GET['pay_status'] ?? 'all',
            'method' => $_GET['pay_method'] ?? 'all',
            'tier'   => $_GET['pay_tier']   ?? 'all',
            'source' => $_GET['pay_source'] ?? '',
            'from'   => $_GET['pay_from']   ?? '',
            'to'     => $_GET['pay_to']     ?? '',
            'search' => $_GET['pay_q']      ?? '',
            'limit'  => 10000,
        ];

        $rows = Payment::listWithFilters($filters);

        $this->audit(
            'payment.exported',
            'payment',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' payment row(s)'
        );

        $filename = 'payments-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Payment ID',
            'Receipt No',
            'Member',
            'Source',
            'Tier',
            'Amount',
            'Refund Amount',
            'Method',
            'Status',
            'Paid Date',
            'Due Date',
            'Reference No',
            'Description',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['receipt_no'] ?? '',
                $r['user_name'],
                strtoupper($r['source'] ?? ''),
                strtoupper($r['tier'] ?? ''),
                number_format((float)$r['amount'], 2, '.', ''),
                number_format((float)($r['refund_amount'] ?? 0), 2, '.', ''),
                Payment::methodLabel($r['method'] ?? 'gcash'),
                Payment::statusLabel($r['status'] ?? 'pending'),
                $r['paid_date'] ?? '',
                $r['due_date'] ?? '',
                $r['reference_no'] ?? '',
                $r['description'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function paymentAgingReport(): void
    {
        $report = Payment::agingReport(1000);

        $this->audit(
            'payment.aging_exported',
            'payment',
            null,
            null,
            ['rows' => count($report['rows'])],
            'Exported aging report'
        );

        $filename = 'aging-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Payment ID',
            'Receipt No',
            'Member',
            'Amount',
            'Due Date',
            'Days Overdue',
            'Aging Bucket',
            'Method',
            'Status',
            'Description',
        ]);

        $bucketLabels = [
            'current' => 'Current (0 days)',
            '1_7'     => '1-7 days',
            '8_14'    => '8-14 days',
            '15_30'   => '15-30 days',
            '30_plus' => '30+ days',
        ];

        foreach ($report['rows'] as $r) {
            fputcsv($out, [
                $r['id'],
                $r['receipt_no'] ?? '',
                $r['user_name'],
                number_format((float)$r['amount'], 2, '.', ''),
                $r['due_date'] ?? $r['paid_date'] ?? '',
                (int)($r['aging_days'] ?? 0),
                $bucketLabels[$r['aging_bucket'] ?? 'current'] ?? '—',
                Payment::methodLabel($r['method'] ?? 'gcash'),
                Payment::statusLabel($r['status'] ?? 'pending'),
                $r['description'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function sendPaymentReminders(): void
    {
        csrf_verify();

        $pdo = \App\Config\Database::pdo();

        $rows = $pdo->query("
            SELECT `id`, `user_id`, `user_name`, `amount`, `description`
            FROM `payments`
            WHERE `status` = 'pending' AND `user_id` IS NOT NULL
        ")->fetchAll();

        $sent = 0;
        foreach ($rows as $p) {
            try {
                $pdo->prepare("
                    INSERT INTO `notifications`
                      (`user_id`, `tone`, `title`, `description`, `link`)
                    VALUES (?, 'warn', ?, ?, ?)
                ")->execute([
                    $p['user_id'],
                    'Payment reminder',
                    'You have an outstanding balance of ' . peso($p['amount'])
                        . ($p['description'] ? ' — ' . $p['description'] : ''),
                    '/payments',
                ]);
                $sent++;
            } catch (\Throwable $e) {
                error_log('[reminder] ' . $e->getMessage());
            }
        }

        $this->audit(
            'payment.reminders_sent',
            'payment',
            null,
            null,
            ['count' => $sent],
            'Sent ' . $sent . ' payment reminder(s)'
        );

        flash(
            $sent > 0 ? 'success' : 'error',
            $sent > 0
                ? 'Sent ' . $sent . ' reminder' . ($sent !== 1 ? 's' : '') . '.'
                : 'No pending payments to remind.'
        );

        $this->redirect('/admin-dashboard?tab=payments');
    }

    /* =============================================================
     |  ATTENDANCE  (spec §1.4)
     ============================================================= */

    public function manualTimeIn(): void
    {
        csrf_verify();

        $userId = trim((string)$this->input('user_id'));
        $timeIn = trim((string)$this->input('time_in'));
        $notes  = trim((string)$this->input('notes'));

        $errors = [];

        if ($userId === '' || !User::find($userId)) {
            $errors[] = 'Please select a valid member.';
        }

        if ($timeIn !== '') {
            $ts = strtotime($timeIn);
            if ($ts === false) {
                $errors[] = 'Invalid time-in value.';
            } elseif ($ts > time() + 60) {
                $errors[] = 'Time-in cannot be in the future.';
            }
        } else {
            $timeIn = date('Y-m-d H:i:s');
        }

        if (!$errors) {
            $existing = Attendance::openToday($userId);
            if ($existing) {
                $errors[] = 'This member already has an open session today.';
            }
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=live');
        }

        $created = Attendance::manualTimeIn(
            $userId,
            $timeIn,
            current_user()['id'] ?? null,
            $notes
        );

        if (!$created) {
            flash('error', 'Could not create the attendance record.');
            $this->redirect('/admin-dashboard?tab=live');
        }

        $this->audit(
            'attendance.manual_time_in',
            'attendance',
            $created['id'],
            null,
            $created,
            'Manual time-in for ' . ($created['user_name'] ?? $userId)
        );

        flash('success', 'Member clocked in manually.');
        $this->redirect('/admin-dashboard?tab=live');
    }

    public function updateAttendance(): void
    {
        csrf_verify();

        $id  = (string)$this->input('id');
        $rec = Attendance::find($id);
        if (!$rec) {
            flash('error', 'Attendance record not found.');
            $this->redirect('/admin-dashboard?tab=live');
        }

        $timeIn  = trim((string)$this->input('time_in'));
        $timeOut = trim((string)$this->input('time_out'));
        $notes   = trim((string)$this->input('notes'));

        $errors = [];

        if ($timeIn === '' || strtotime($timeIn) === false) {
            $errors[] = 'A valid time-in is required.';
        }
        if ($timeOut !== '' && strtotime($timeOut) === false) {
            $errors[] = 'Invalid time-out value.';
        }
        if (
            !$errors && $timeIn !== '' && $timeOut !== ''
            && strtotime($timeOut) <= strtotime($timeIn)
        ) {
            $errors[] = 'Time-out must be after time-in.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=live');
        }

        Attendance::updateRecord($id, [
            'time_in'  => $timeIn,
            'time_out' => $timeOut ?: null,
            'notes'    => $notes,
        ]);

        $this->audit(
            'attendance.updated',
            'attendance',
            $id,
            $rec,
            Attendance::find($id),
            'Updated attendance for ' . ($rec['user_name'] ?? '')
        );

        flash('success', 'Attendance updated.');
        $this->redirect('/admin-dashboard?tab=live');
    }

    public function deleteAttendance(): void
    {
        csrf_verify();

        $id  = (string)$this->input('id');
        $rec = Attendance::find($id);
        if (!$rec) {
            flash('error', 'Attendance record not found.');
            $this->redirect('/admin-dashboard?tab=live');
        }

        Attendance::delete($id);

        $this->audit(
            'attendance.deleted',
            'attendance',
            $id,
            $rec,
            null,
            'Deleted attendance for ' . ($rec['user_name'] ?? '')
        );

        flash('success', 'Attendance record deleted.');
        $this->redirect('/admin-dashboard?tab=live');
    }

    public function exportAttendance(): void
    {
        $filters = [
            'from'      => $_GET['att_from']   ?? date('Y-m-d'),
            'to'        => $_GET['att_to']     ?? date('Y-m-d'),
            'member'    => $_GET['att_member'] ?? '',
            'tier'      => $_GET['att_tier']   ?? 'all',
            'source'    => $_GET['att_source'] ?? 'all',
            'open_only' => !empty($_GET['att_open']),
            'limit'     => 10000,
        ];

        $rows = Attendance::listWithFilters($filters);

        $this->audit(
            'attendance.exported',
            'attendance',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' attendance row(s)'
        );

        $filename = 'attendance-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Record ID',
            'Member',
            'Member ID',
            'Date',
            'Time In',
            'Time Out',
            'Hours',
            'Tier',
            'Source',
            'Notes',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['user_name'],
                $r['user_id'] ?? '',
                $r['date'],
                $r['time_in'],
                $r['time_out'] ?? '',
                number_format((float)($r['hours'] ?? 0), 1, '.', ''),
                strtoupper($r['current_tier'] ?? $r['tier'] ?? ''),
                Attendance::sourceLabel($r['source'] ?? 'qr'),
                $r['notes'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function clockOut(): void
    {
        csrf_verify();

        $rec = Attendance::find((string)$this->input('id'));
        if (!$rec) {
            $this->redirect('/admin-dashboard?tab=live');
        }

        if (!empty($rec['time_out'])) {
            flash('error', 'This member is already clocked out.');
            $this->redirect('/admin-dashboard?tab=live');
        }

        $hours = max(0, (time() - strtotime($rec['time_in'])) / 3600);
        Attendance::update($rec['id'], [
            'time_out'           => date('Y-m-d H:i:s'),
            'hours'              => round($hours, 1),
            'source'             => 'manual',
            'manual_override_by' => current_user()['id'] ?? null,
        ]);

        $this->audit(
            'attendance.clock_out',
            'attendance',
            $rec['id'],
            ['time_out' => $rec['time_out'] ?? null],
            ['time_out' => date('Y-m-d H:i:s')],
            'Manually clocked out ' . ($rec['user_name'] ?? 'member')
        );

        flash('success', 'Member clocked out.');
        $this->redirect('/admin-dashboard?tab=live');
    }

    public function liveOccupancy(): void
    {
        $stats = Attendance::todayStats();
        $in    = Attendance::currentlyIn();

        $list = array_map(function ($r) {
            return [
                'id'           => $r['id'],
                'user_name'    => $r['user_name'] ?? 'Member',
                'time_in'      => $r['time_in'],
                'live_minutes' => (int)($r['live_minutes'] ?? 0),
                'tier'         => $r['tier'] ?? null,
                'initials'     => initials($r['user_name'] ?? '?'),
            ];
        }, $in);

        $this->json([
            'in_gym'       => (int)($stats['in_gym'] ?? 0),
            'capacity'     => (int)($stats['capacity'] ?? 0),
            'utilization'  => (float)($stats['utilization'] ?? 0),
            'today_total'  => (int)($stats['total'] ?? 0),
            'hours'        => (float)($stats['hours'] ?? 0),
            'currently'    => $list,
            'generated_at' => date('c'),
        ]);
    }

    public function occupancy(): void
    {
        $today = Attendance::todayAll();
        $in = 0;
        foreach ($today as $r) {
            if (!$r['time_out']) $in++;
        }
        $this->json(['in' => $in, 'capacity' => GYM_CAPACITY]);
    }

    /* =============================================================
     |  PARTNERS  (spec §1.5)
     ============================================================= */

    public function updatePartner(): void
    {
        csrf_verify();

        $id  = (string)$this->input('id');
        $pp  = PartnerProfile::find($id);
        if (!$pp) {
            flash('error', 'Partner not found.');
            $this->redirect('/admin-dashboard?tab=partners');
        }

        $errors = [];

        $businessName = trim((string)$this->input('business_name'));
        if ($businessName === '') {
            $errors[] = 'Business name is required.';
        }

        $rate = (float)$this->input('commission_rate');
        if ($rate < 0 || $rate > 50) {
            $errors[] = 'Commission rate must be between 0 and 50%.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=partners');
        }

        PartnerProfile::updateProfile($id, [
            'business_name'   => $businessName,
            'partner_type'    => (string)$this->input('partner_type', $pp['partner_type']),
            'phone'           => $this->input('phone'),
            'contact_email'   => $this->input('contact_email'),
            'address'         => $this->input('address'),
            'notes'           => $this->input('notes'),
            'status'          => (string)$this->input('status', $pp['status']),
            'commission_rate' => $rate,
        ]);

        $this->audit(
            'partner.updated',
            'partner_profile',
            $id,
            $pp,
            PartnerProfile::find($id),
            'Updated partner "' . $businessName . '"'
        );

        flash('success', 'Partner updated.');
        $this->redirect('/admin-dashboard?tab=partners');
    }

    public function togglePartnerStatus(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $pp = PartnerProfile::find($id);
        if (!$pp) {
            flash('error', 'Partner not found.');
            $this->redirect('/admin-dashboard?tab=partners');
        }

        $before = $pp['status'];
        PartnerProfile::toggleStatus($id);
        $after = PartnerProfile::find($id)['status'] ?? $before;

        $this->audit(
            'partner.status_changed',
            'partner_profile',
            $id,
            ['status' => $before],
            ['status' => $after],
            'Partner "' . ($pp['business_name'] ?? '') . '" set to ' . $after
        );

        flash('success', 'Partner ' . ($after === 'active' ? 'activated' : 'deactivated') . '.');
        $this->redirect('/admin-dashboard?tab=partners');
    }

    public function deletePartner(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $pp = PartnerProfile::find($id);
        if (!$pp) {
            flash('error', 'Partner not found.');
            $this->redirect('/admin-dashboard?tab=partners');
        }

        PartnerProfile::update($id, ['status' => 'inactive']);

        $this->audit(
            'partner.deactivated',
            'partner_profile',
            $id,
            ['status' => $pp['status'] ?? 'active'],
            ['status' => 'inactive'],
            'Deactivated partner "' . ($pp['business_name'] ?? '') . '"'
        );

        flash('success', 'Partner deactivated. Historical data preserved.');
        $this->redirect('/admin-dashboard?tab=partners');
    }

    public function exportPartners(): void
    {
        $filters = [
            'status' => $_GET['p_status'] ?? 'all',
            'type'   => $_GET['p_type']   ?? 'all',
            'search' => $_GET['p_q']      ?? '',
            'limit'  => 10000,
        ];

        $rows = PartnerProfile::listWithMetrics($filters);

        $this->audit(
            'partner.exported',
            'partner_profile',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' partner row(s)'
        );

        $filename = 'partners-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Partner ID',
            'Business Name',
            'Type',
            'Status',
            'Referral Code',
            'Commission %',
            'Email',
            'Contact',
            'Clients',
            'Linked',
            'Sales',
            'Revenue',
            'Commission Earned',
            'Paid Out',
            'Pending',
            'Available',
            'Last Sale',
            'Churned',
            'Joined',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['business_name'],
                ucfirst($r['partner_type']),
                ucfirst($r['status']),
                $r['referral_code'],
                number_format((float)($r['commission_rate'] ?? 10), 2, '.', ''),
                $r['contact_email'] ?? $r['user_email'] ?? '',
                $r['phone'] ?? '',
                (int)($r['total_clients'] ?? 0),
                (int)($r['linked_clients'] ?? 0),
                (int)($r['approved_sales_count'] ?? 0),
                number_format((float)($r['approved_amount'] ?? 0), 2, '.', ''),
                number_format((float)($r['commission_earned'] ?? 0), 2, '.', ''),
                number_format((float)($r['payouts_paid'] ?? 0), 2, '.', ''),
                number_format((float)($r['payouts_pending'] ?? 0), 2, '.', ''),
                number_format((float)($r['available_balance'] ?? 0), 2, '.', ''),
                $r['last_sale_date'] ?? '—',
                !empty($r['is_churned']) ? 'Yes' : 'No',
                $r['created_date'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    /* =============================================================
     |  PARTNER SALES  (spec §1.5)
     ============================================================= */

    public function approveSale(): void
    {
        csrf_verify();

        $id   = (string)$this->input('id');
        $sale = PartnerSale::find($id);
        if (!$sale) {
            flash('error', 'Sale not found.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }
        if ($sale['status'] === 'approved') {
            flash('error', 'Sale is already approved.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $before = $sale;
        PartnerSale::approve($id, current_user()['id'] ?? null);
        $after = PartnerSale::find($id);

        $this->audit(
            'sale.approved',
            'partner_sale',
            $id,
            $before,
            $after,
            'Approved sale of ' . peso($sale['amount'])
                . ' — commission: ' . peso($after['commission_amount'] ?? 0)
        );

        flash('success', 'Sale credited. Commission: ' . peso($after['commission_amount'] ?? 0));
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    public function rejectSale(): void
    {
        csrf_verify();

        $id     = (string)$this->input('id');
        $reason = trim((string)$this->input('reason'));

        $sale = PartnerSale::find($id);
        if (!$sale) {
            flash('error', 'Sale not found.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }
        if ($sale['status'] !== 'pending') {
            flash('error', 'Only pending sales can be rejected.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }
        if ($reason === '') {
            flash('error', 'Please provide a rejection reason.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $before = $sale;
        PartnerSale::reject($id, $reason, current_user()['id'] ?? null);
        $after = PartnerSale::find($id);

        $this->audit(
            'sale.rejected',
            'partner_sale',
            $id,
            $before,
            $after,
            'Rejected sale of ' . peso($sale['amount']) . ' — reason: ' . $reason
        );

        flash('success', 'Sale rejected.');
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    public function logSale(): void
    {
        csrf_verify();

        $partnerId   = trim((string)$this->input('partner_user_id'));
        $description = trim((string)$this->input('description'));
        $amount      = (float)$this->input('amount');
        $type        = (string)$this->input('type', 'product');
        $clientId    = (string)$this->input('client_id', '');

        $errors = [];

        if ($partnerId === '' || !PartnerProfile::forUser($partnerId)) {
            $errors[] = 'Please select a valid partner.';
        }
        if ($description === '') {
            $errors[] = 'Description is required.';
        }
        if ($amount <= 0) {
            $errors[] = 'Amount must be greater than zero.';
        }
        if (!in_array($type, ['product', 'client_payment'], true)) {
            $type = 'product';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $created = PartnerSale::logSale(
            $partnerId,
            $description,
            $amount,
            $type,
            $clientId !== '' ? $clientId : null,
            current_user()['id'] ?? null
        );

        if (!$created) {
            flash('error', 'Could not log the sale.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $this->audit(
            'sale.logged_by_admin',
            'partner_sale',
            $created['id'],
            null,
            $created,
            'Admin logged sale of ' . peso($amount) . ' for partner ' . $partnerId
        );

        flash('success', 'Sale logged. It will appear as pending until approved.');
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    /* =============================================================
     |  PAYOUTS  (spec §1.5)
     ============================================================= */

    public function setPayoutStatus(): void
    {
        csrf_verify();

        $id     = (string)$this->input('id');
        $status = (string)$this->input('status');
        $note   = trim((string)$this->input('note', ''));

        if ($status === 'approved') {
            $this->markPayoutPaid();
            return;
        }

        if ($status === 'rejected') {
            $_POST['reason'] = $note ?: 'Rejected by admin';
            $this->rejectPayout();
            return;
        }

        flash('error', 'Invalid payout status.');
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    public function markPayoutPaid(): void
    {
        csrf_verify();

        $id  = (string)$this->input('id');
        $ref = trim((string)$this->input('reference_no'));

        $p = PayoutRequest::find($id);
        if (!$p) {
            flash('error', 'Payout not found.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }
        if ($p['status'] !== 'pending') {
            flash('error', 'Only pending payouts can be approved.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $before = $p;
        PayoutRequest::markApproved($id, $ref ?: null, current_user()['id'] ?? null);
        $after = PayoutRequest::find($id);

        $this->audit(
            'payout.approved',
            'payout_request',
            $id,
            $before,
            $after,
            'Approved payout of ' . peso($p['amount'])
                . ($ref ? ' — ref: ' . $ref : '')
        );

        flash('success', 'Payout approved.');
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    public function rejectPayout(): void
    {
        csrf_verify();

        $id     = (string)$this->input('id');
        $reason = trim((string)$this->input('reason'));

        $p = PayoutRequest::find($id);
        if (!$p) {
            flash('error', 'Payout not found.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }
        if ($p['status'] !== 'pending') {
            flash('error', 'Only pending payouts can be rejected.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }
        if ($reason === '') {
            flash('error', 'Please provide a rejection reason.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $before = $p;
        PayoutRequest::reject($id, $reason, current_user()['id'] ?? null);
        $after = PayoutRequest::find($id);

        $this->audit(
            'payout.rejected',
            'payout_request',
            $id,
            $before,
            $after,
            'Rejected payout of ' . peso($p['amount']) . ' — reason: ' . $reason
        );

        flash('success', 'Payout rejected. Amount released back to the partner.');
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    public function createPayoutBatch(): void
    {
        csrf_verify();
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/admin-dashboard?tab=payouts');
        }

        $ids = $this->input('ids', []);
        if (!is_array($ids) || empty($ids)) {
            flash('error', 'No payouts selected.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $notes = trim((string)$this->input('notes', ''));
        $actor = current_user()['id'] ?? null;

        $batch = PayoutRequest::createBatch($ids, $actor, $notes);

        if (!$batch) {
            flash('error', 'No eligible payouts could be batched.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $this->audit(
            'payout.batch_created',
            'payout_batch',
            $batch['id'],
            null,
            $batch,
            'Created batch of ' . $batch['request_count'] . ' payout(s) — total '
                . peso($batch['total_amount'])
        );

        flash(
            'success',
            'Batch created: ' . $batch['request_count'] . ' payout(s), '
                . peso($batch['total_amount']) . '.'
        );
        $this->redirect('/admin-dashboard?tab=payouts&po_batch=' . urlencode($batch['id']));
    }

    public function processPayoutBatch(): void
    {
        csrf_verify();
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/admin-dashboard?tab=payouts');
        }

        $batchId = (string)$this->input('batch_id');
        $ref     = trim((string)$this->input('reference_no'));

        $batch = PayoutRequest::findBatch($batchId);
        if (!$batch) {
            flash('error', 'Batch not found.');
            $this->redirect('/admin-dashboard?tab=payouts');
        }

        $before = $batch;

        if ($ref !== '') {
            $pdo = \App\Config\Database::pdo();
            $pdo->prepare("
                UPDATE `payout_requests`
                SET    `reference_no` = ?
                WHERE  `batch_id` = ? AND `status` = 'pending'
            ")->execute([mb_substr($ref, 0, 80), $batchId]);
        }

        $after = PayoutRequest::processBatch($batchId, current_user()['id'] ?? null);

        $this->audit(
            'payout.batch_processed',
            'payout_batch',
            $batchId,
            $before,
            $after,
            'Processed batch — ' . ($after['request_count'] ?? 0) . ' payout(s)'
        );

        flash('success', 'Batch processed. All payouts approved.');
        $this->redirect('/admin-dashboard?tab=payouts');
    }

    public function exportPayouts(): void
    {
        $filters = [
            'status'          => $_GET['po_status']  ?? 'all',
            'method'          => $_GET['po_method']  ?? 'all',
            'partner_user_id' => $_GET['po_partner'] ?? '',
            'batch_id'        => $_GET['po_batch']   ?? '',
            'from'            => $_GET['po_from']    ?? '',
            'to'              => $_GET['po_to']      ?? '',
            'search'          => $_GET['po_q']       ?? '',
            'limit'           => 10000,
        ];

        $rows = PayoutRequest::listWithFilters($filters);

        $this->audit(
            'payout.exported',
            'payout_request',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' payout row(s)'
        );

        $filename = 'payouts-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Payout ID',
            'Partner',
            'Amount',
            'Status',
            'Method',
            'Method Details',
            'Reference No',
            'Batch ID',
            'Requested',
            'Processed',
            'Note',
            'Reject Reason',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['business_name'] ?? $r['partner_name'] ?? '',
                number_format((float)$r['amount'], 2, '.', ''),
                ucfirst($r['status']),
                ucfirst(str_replace('_', ' ', $r['method'] ?? 'gcash')),
                $r['method_details'] ?? '',
                $r['reference_no'] ?? '',
                $r['batch_id'] ?? '',
                $r['requested_date'] ?? '',
                $r['processed_date'] ?? '',
                $r['note'] ?? '',
                $r['reject_reason'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    /* =============================================================
     |  B2B CLIENTS  (spec §1.5)
     ============================================================= */

    public function updateB2bClient(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $c  = B2BClient::find($id);
        if (!$c) {
            flash('error', 'Client not found.');
            $this->redirect('/admin-dashboard?tab=partners');
        }

        $errors = [];
        $name  = trim((string)$this->input('client_name'));
        $email = trim((string)$this->input('client_email'));

        if ($name === '')  $errors[] = 'Client name is required.';
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid client email is required.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
            $this->redirect('/admin-dashboard?tab=partners');
        }

        B2BClient::updateClient($id, [
            'client_name'       => $name,
            'client_email'      => $email,
            'contact_phone'     => $this->input('contact_phone'),
            'notes'             => $this->input('notes'),
            'membership_status' => (string)$this->input('membership_status', $c['membership_status']),
        ]);

        $this->audit(
            'b2b_client.updated',
            'b2b_client',
            $id,
            $c,
            B2BClient::find($id),
            'Updated client "' . $name . '"'
        );

        flash('success', 'Client updated.');
        $this->redirect('/admin-dashboard?tab=partners');
    }

    public function deleteB2bClient(): void
    {
        csrf_verify();

        $id = (string)$this->input('id');
        $c  = B2BClient::find($id);
        if (!$c) {
            flash('error', 'Client not found.');
            $this->redirect('/admin-dashboard?tab=partners');
        }

        B2BClient::delete($id);

        $this->audit(
            'b2b_client.deleted',
            'b2b_client',
            $id,
            $c,
            null,
            'Deleted client "' . ($c['client_name'] ?? '') . '"'
        );

        flash('success', 'Client record deleted.');
        $this->redirect('/admin-dashboard?tab=partners');
    }

    public function exportB2bClients(): void
    {
        $filters = [
            'partner_user_id' => $_GET['c_partner'] ?? '',
            'status'          => $_GET['c_status']  ?? 'all',
            'search'          => $_GET['c_q']       ?? '',
            'limit'           => 10000,
        ];

        $rows = B2BClient::listWithFilters($filters);

        $this->audit(
            'b2b_client.exported',
            'b2b_client',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' client row(s)'
        );

        $filename = 'b2b-clients-' . date('Y-m-d-His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Client ID',
            'Name',
            'Email',
            'Phone',
            'Partner',
            'Referral Code',
            'Status',
            'Linked User ID',
            'Linked',
            'Last Check-in',
            'Created',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['client_name'],
                $r['client_email'],
                $r['contact_phone'] ?? '',
                $r['business_name'] ?? '',
                $r['referral_code'] ?? '',
                ucfirst($r['membership_status']),
                $r['client_user_id'] ?? '',
                $r['linked_at'] ?? '',
                $r['recent_checkin'] ?? $r['last_check_in'] ?? '',
                $r['created_date'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    /* =============================================================
     |  AUDIT  (spec §2.1)
     ============================================================= */

    public function exportAuditLogs(): void
    {
        $filters = [
            'from'   => trim((string)($_GET['a_from']   ?? '')),
            'to'     => trim((string)($_GET['a_to']     ?? '')),
            'user'   => trim((string)($_GET['a_user']   ?? '')),
            'role'   => trim((string)($_GET['a_role']   ?? '')),
            'action' => trim((string)($_GET['a_action'] ?? '')),
            'entity' => trim((string)($_GET['a_entity'] ?? '')),
            'q'      => trim((string)($_GET['a_q']      ?? '')),
        ];

        $where  = [];
        $params = [];

        if ($filters['from'] !== '') {
            $where[]  = '`created_date` >= ?';
            $params[] = $filters['from'] . ' 00:00:00';
        }
        if ($filters['to'] !== '') {
            $where[]  = '`created_date` <= ?';
            $params[] = $filters['to'] . ' 23:59:59';
        }
        if ($filters['user'] !== '') {
            $where[]  = '`user_id` = ?';
            $params[] = $filters['user'];
        }
        if ($filters['role'] !== '') {
            $where[]  = '`user_role` = ?';
            $params[] = $filters['role'];
        }
        if ($filters['action'] !== '') {
            $where[]  = '`action` LIKE ?';
            $params[] = $filters['action'] . '%';
        }
        if ($filters['entity'] !== '') {
            $where[]  = '`entity_type` = ?';
            $params[] = $filters['entity'];
        }
        if ($filters['q'] !== '') {
            $like     = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $filters['q']) . '%';
            $where[]  = '(`action` LIKE ? OR `entity_id` LIKE ? OR `notes` LIKE ? OR `ip_address` LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        try {
            $stmt = \App\Config\Database::pdo()->prepare(
                "SELECT * FROM `audit_logs` {$whereSql}
                 ORDER BY `created_date` DESC
                 LIMIT 50000"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('[exportAuditLogs] ' . $e->getMessage());
            $rows = [];
        }

        $this->audit(
            'audit.exported',
            'system',
            null,
            null,
            ['count' => count($rows), 'filters' => $filters],
            'Exported ' . count($rows) . ' audit row(s)'
        );

        $filename = 'audit-logs-' . date('Y-m-d-His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Timestamp',
            'Actor ID',
            'Actor Role',
            'Action',
            'Entity Type',
            'Entity ID',
            'IP Address',
            'User Agent',
            'Notes',
            'Before',
            'After',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['created_date']  ?? '',
                $r['user_id']       ?? '',
                $r['user_role']     ?? '',
                $r['action']        ?? '',
                $r['entity_type']   ?? '',
                $r['entity_id']     ?? '',
                $r['ip_address']    ?? '',
                $r['user_agent']    ?? '',
                $r['notes']         ?? '',
                $r['before_data']   ?? '',
                $r['after_data']    ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    public function purgeAuditLogs(): void
    {
        csrf_verify();
        if (function_exists('session_require_reauth')) {
            session_require_reauth('/admin-dashboard?tab=audit');
        }

        $days = (int)$this->input('days', 365);

        if ($days < 365) {
            flash('error', 'Audit logs must be retained for at least 365 days (spec §2.1).');
            $this->redirect('/admin-dashboard?tab=audit');
        }
        if ($days > 3650) {
            $days = 3650;
        }

        $deleted = 0;
        try {
            $stmt = \App\Config\Database::pdo()->prepare(
                'DELETE FROM `audit_logs` WHERE `created_date` < (NOW() - INTERVAL ? DAY)'
            );
            $stmt->execute([$days]);
            $deleted = $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log('[purgeAuditLogs] ' . $e->getMessage());
        }

        $this->audit(
            'audit.purged',
            'system',
            null,
            null,
            ['days' => $days, 'deleted' => $deleted],
            'Purged ' . $deleted . ' audit row(s) older than ' . $days . ' days'
        );

        flash(
            'success',
            'Purged ' . number_format($deleted) . ' audit entr'
                . ($deleted === 1 ? 'y' : 'ies') . ' older than ' . $days . ' days.'
        );
        $this->redirect('/admin-dashboard?tab=audit');
    }

    /* =============================================================
     |  KPI JSON endpoint
     ============================================================= */

    public function kpis(): void
    {
        $stats = Attendance::todayStats();
        $paymentSummary = \App\Models\Payment::statusSummary();

        $memberCount = 0;
        try {
            $memberCount = (int)\App\Config\Database::pdo()
                ->query("SELECT COUNT(*) FROM `users` WHERE `role` != 'admin'")
                ->fetchColumn();
        } catch (\Throwable $e) { /* silent */
        }

        $this->json([
            'in_gym'          => (int)($stats['in_gym']      ?? 0),
            'capacity'        => (int)($stats['capacity']    ?? 0),
            'utilization'     => (float)($stats['utilization'] ?? 0),
            'today_checkins'  => (int)($stats['total']       ?? 0),
            'today_hours'     => (float)($stats['hours']     ?? 0),
            'member_count'    => $memberCount,
            'revenue_paid'    => (float)($paymentSummary['revenue_paid'] ?? 0),
            'pending_count'   => (int)($paymentSummary['pending']        ?? 0),
            'pending_amount'  => (float)($paymentSummary['revenue_pending'] ?? 0),
            'generated_at'    => date('c'),
        ]);
    }

    /* =============================================================
     |  SETTINGS  (spec §2.2)
     ============================================================= */

    private function setSetting(
        string $key,
        mixed $value,
        string $type = 'string',
        string $description = ''
    ): void {
        $pdo = \App\Config\Database::pdo();

        $encoded = match ($type) {
            'json' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'int'  => (string)(int)$value,
            'bool' => $value ? '1' : '0',
            default => (string)$value,
        };

        $exists = false;
        try {
            $stmt = $pdo->prepare('SELECT `id` FROM `system_settings` WHERE `setting_key` = ? LIMIT 1');
            $stmt->execute([$key]);
            $exists = (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            error_log('[setSetting:lookup] ' . $e->getMessage());
            return;
        }

        if ($exists) {
            try {
                $pdo->prepare(
                    'UPDATE `system_settings`
                     SET `setting_value` = ?,
                         `setting_type`  = ?,
                         `description`   = COALESCE(NULLIF(?, ""), `description`),
                         `updated_by`    = ?,
                         `updated_date`  = NOW()
                     WHERE `setting_key` = ?'
                )->execute([
                    $encoded,
                    $type,
                    $description,
                    current_user()['id'] ?? null,
                    $key,
                ]);
            } catch (\Throwable $e) {
                error_log('[setSetting:update] ' . $e->getMessage());
            }
        } else {
            try {
                $pdo->prepare(
                    'INSERT INTO `system_settings`
                       (`id`, `setting_key`, `setting_value`, `setting_type`,
                        `description`, `updated_by`, `created_date`, `updated_date`)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())'
                )->execute([
                    \App\Core\Model::uuid(),
                    $key,
                    $encoded,
                    $type,
                    $description ?: null,
                    current_user()['id'] ?? null,
                ]);
            } catch (\Throwable $e) {
                error_log('[setSetting:insert] ' . $e->getMessage());
            }
        }
    }

    private function getSetting(string $key, mixed $default = null): mixed
    {
        try {
            $stmt = \App\Config\Database::pdo()->prepare(
                'SELECT `setting_value`, `setting_type`
                 FROM `system_settings`
                 WHERE `setting_key` = ? LIMIT 1'
            );
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            if (!$row) return $default;

            return match ($row['setting_type']) {
                'json' => json_decode((string)$row['setting_value'], true) ?? $default,
                'int'  => (int)$row['setting_value'],
                'bool' => (bool)$row['setting_value'],
                default => (string)$row['setting_value'],
            };
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public function savePaymentMethods(): void
    {
        csrf_verify();

        $raw = $this->input('methods', []);
        if (!is_array($raw)) $raw = [];

        $allowed = ['gcash', 'cash', 'credit_card', 'bank_transfer'];
        $clean = array_values(array_intersect($raw, $allowed));

        if (empty($clean)) {
            flash('error', 'At least one payment method must be enabled.');
            $this->redirect('/admin-dashboard?tab=settings');
        }

        $before = $this->getSetting('accepted_payment_methods', $allowed);

        $this->setSetting(
            'accepted_payment_methods',
            $clean,
            'json',
            'Payment methods shown to members at enrollment.'
        );

        $this->audit(
            'settings.payment_methods_updated',
            'system',
            null,
            ['accepted_payment_methods' => $before],
            ['accepted_payment_methods' => $clean],
            'Enabled: ' . implode(', ', $clean)
        );

        flash('success', 'Payment methods updated.');
        $this->redirect('/admin-dashboard?tab=settings');
    }

    public function saveCollectionFrequency(): void
    {
        csrf_verify();

        $freq = (string)$this->input('frequency', 'monthly');
        $allowed = ['daily', 'weekly', 'monthly', 'annual'];

        if (!in_array($freq, $allowed, true)) {
            flash('error', 'Invalid collection frequency.');
            $this->redirect('/admin-dashboard?tab=settings');
        }

        $before = $this->getSetting('payment_collection_frequency', 'monthly');

        $this->setSetting(
            'payment_collection_frequency',
            $freq,
            'string',
            'How often recurring payments are collected.'
        );

        $this->audit(
            'settings.collection_frequency_updated',
            'system',
            null,
            ['payment_collection_frequency' => $before],
            ['payment_collection_frequency' => $freq],
            'Collection frequency set to ' . $freq
        );

        flash('success', 'Collection frequency updated.');
        $this->redirect('/admin-dashboard?tab=settings');
    }

    public function saveGracePeriod(): void
    {
        csrf_verify();

        $type = (string)$this->input('type', '');
        $days = (int)$this->input('days');

        $errors = [];

        if ($type === 'late_payment') {
            if ($days < 0 || $days > 60) {
                $errors[] = 'Late payment grace must be between 0 and 60 days.';
            }
            if (!$errors) {
                $before = $this->getSetting('late_payment_grace_days', 7);

                $this->setSetting(
                    'late_payment_grace_days',
                    $days,
                    'int',
                    'Days after due date before a payment is considered late.'
                );

                $this->audit(
                    'settings.grace_period_updated',
                    'system',
                    null,
                    ['late_payment_grace_days' => $before],
                    ['late_payment_grace_days' => $days],
                    'Late payment grace: ' . $days . ' days'
                );

                flash('success', 'Late payment grace period updated.');
            }
        } elseif ($type === 'enrollment') {
            if ($days < 0 || $days > 30) {
                $errors[] = 'Enrollment grace must be between 0 and 30 days.';
            }
            if (!$errors) {
                $before = $this->getSetting('enrollment_grace_period_days', 3);

                $this->setSetting(
                    'enrollment_grace_period_days',
                    $days,
                    'int',
                    'Days after enrollment end date before account lockout.'
                );

                $this->audit(
                    'settings.grace_period_updated',
                    'system',
                    null,
                    ['enrollment_grace_period_days' => $before],
                    ['enrollment_grace_period_days' => $days],
                    'Enrollment grace: ' . $days . ' days'
                );

                flash('success', 'Enrollment grace period updated.');
            }
        } else {
            $errors[] = 'Invalid grace period type.';
        }

        if ($errors) {
            flash('error', implode(' ', $errors));
        }

        $this->redirect('/admin-dashboard?tab=settings');
    }

    public function saveOperationalCost(): void
    {
        csrf_verify();

        $amount = (float)$this->input('amount', 0);

        if ($amount < 0 || $amount > 1000000) {
            flash('error', 'Operational cost must be between ₱0 and ₱1,000,000.');
            $this->redirect('/admin-dashboard?tab=settings');
        }

        $before = (float)$this->getSetting('operational_cost_per_member', 300);

        $this->setSetting(
            'operational_cost_per_member',
            (int)round($amount),
            'int',
            'Floor price: programs below this trigger a warning badge.'
        );

        $this->audit(
            'settings.operational_cost_updated',
            'system',
            null,
            ['operational_cost_per_member' => $before],
            ['operational_cost_per_member' => (int)round($amount)],
            'Cost floor set to ₱' . number_format($amount, 0)
        );

        flash('success', 'Operational cost floor updated.');
        $this->redirect('/admin-dashboard?tab=settings');
    }

    public function saveDefaultCommission(): void
    {
        csrf_verify();

        $rate = (float)$this->input('rate', 10);

        if ($rate < 0 || $rate > 50) {
            flash('error', 'Commission rate must be between 0% and 50%.');
            $this->redirect('/admin-dashboard?tab=settings');
        }

        $before = (float)$this->getSetting('default_commission_rate', 10);

        $this->setSetting(
            'default_commission_rate',
            (int)round($rate),
            'int',
            'Default B2B partner commission percentage (spec: 10–15%).'
        );

        $this->audit(
            'settings.commission_rate_updated',
            'system',
            null,
            ['default_commission_rate' => $before],
            ['default_commission_rate' => (int)round($rate)],
            'Default commission set to ' . $rate . '%'
        );

        flash('success', 'Default commission rate updated.');
        $this->redirect('/admin-dashboard?tab=settings');
    }

    public function saveGymCapacity(): void
    {
        csrf_verify();

        $capacity = (int)$this->input('capacity', 50);

        if ($capacity < 1 || $capacity > 9999) {
            flash('error', 'Gym capacity must be between 1 and 9,999.');
            $this->redirect('/admin-dashboard?tab=settings');
        }

        $before = (int)$this->getSetting('gym_capacity', 50);

        $this->setSetting(
            'gym_capacity',
            $capacity,
            'int',
            'Maximum simultaneous occupancy for utilization calculations.'
        );

        $this->audit(
            'settings.gym_capacity_updated',
            'system',
            null,
            ['gym_capacity' => $before],
            ['gym_capacity' => $capacity],
            'Gym capacity set to ' . $capacity
        );

        flash('success', 'Gym capacity updated.');
        $this->redirect('/admin-dashboard?tab=settings');
    }

    public function saveIdleTimeout(): void
    {
        csrf_verify();

        $minutes = (int)$this->input('minutes', 30);

        if ($minutes < 5 || $minutes > 480) {
            flash('error', 'Idle timeout must be between 5 and 480 minutes.');
            $this->redirect('/admin-dashboard?tab=settings');
        }

        $before = (int)$this->getSetting('idle_timeout_minutes', 30);

        $this->setSetting(
            'idle_timeout_minutes',
            $minutes,
            'int',
            'Minutes of inactivity before session is invalidated (spec §2.1: 30).'
        );

        $affected = 0;
        try {
            $stmt = \App\Config\Database::pdo()->prepare(
                'UPDATE `users`
                 SET    `idle_timeout_minutes` = ?
                 WHERE  `idle_timeout_minutes` = ?
                   AND  `role` IN ("admin", "staff")'
            );
            $stmt->execute([$minutes, $before]);
            $affected = $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log('[saveIdleTimeout:bulk_update] ' . $e->getMessage());
        }

        $note = 'Idle timeout set to ' . $minutes . ' minutes';
        if ($affected > 0) {
            $note .= '; updated ' . $affected . ' admin/staff account(s)';
        }

        $this->audit(
            'settings.idle_timeout_updated',
            'system',
            null,
            ['idle_timeout_minutes' => $before],
            ['idle_timeout_minutes' => $minutes, 'users_updated' => $affected],
            $note
        );

        flash(
            'success',
            'Idle timeout set to ' . $minutes . ' minutes.'
                . ($affected > 0 ? ' Updated ' . $affected . ' account(s).' : '')
        );
        $this->redirect('/admin-dashboard?tab=settings');
    }
}
