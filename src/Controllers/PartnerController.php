<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PartnerProfile;
use App\Models\PartnerSale;
use App\Models\PayoutRequest;
use App\Models\B2BClient;
use App\Models\Attendance;
use App\Models\User;
use App\Config\Database;

class PartnerController extends Controller
{
    /* =============================================================
     |  HELPERS — commission math & balance
     ============================================================= */

    /**
     * Return the partner's commission for a single sale.
     * Prefers the pre-calculated `commission_amount`, falls back to
     * computing it from the sale's own rate, the partner's rate, or
     * the historical default of 10%.
     */
    private function commissionOf(array $sale): float
    {
        return PartnerSale::commissionFor($sale);
    }

    /**
     * Compute a partner's lifetime earnings, committed payouts, and
     * available balance — all in one pass.
     *
     * @return array{lifetime:float, committed:float, available:float}
     */
    private function balanceBreakdown(string $partnerUserId): array
    {
        return PayoutRequest::balanceFor($partnerUserId);
    }

    /* =============================================================
     |  ENSURE PROFILE
     ============================================================= */

    private function ensureProfile(): array
    {
        $user = current_user();
        $p = PartnerProfile::first(['user_id' => $user['id']]);
        if ($p) return $p;

        return PartnerProfile::create([
            'user_id'       => $user['id'],
            'business_name' => $user['full_name'] ?: $user['email'],
            'partner_type'  => 'coach',
            'referral_code' => 'PMZ-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6)),
        ]);
    }

    /* =============================================================
     |  DASHBOARD
     ============================================================= */

    public function dashboard(): void
    {
        $user    = current_user();
        $profile = $this->ensureProfile();

        $clients = B2BClient::where(['partner_user_id' => $user['id']], 'created_date DESC');
        $sales   = PartnerSale::where(['partner_user_id' => $user['id']], 'sale_date DESC');

        // FIX: Sum the COMMISSION, not the gross sale amount.
        $approved = array_filter($sales, fn($s) => ($s['status'] ?? '') === 'approved');

        $totalEarnings = 0.0;
        foreach ($approved as $s) {
            $totalEarnings += $this->commissionOf($s);
        }

        $month = date('Y-m');
        $monthEarnings = 0.0;
        foreach ($approved as $s) {
            if (substr($s['sale_date'] ?? '', 0, 7) === $month) {
                $monthEarnings += $this->commissionOf($s);
            }
        }

        $this->view('partner/dashboard', [
            'pageTitle'     => 'Partner Dashboard',
            'profile'       => $profile,
            'clients'       => $clients,
            'sales'         => $sales,
            'totalEarnings' => round($totalEarnings, 2),
            'monthEarnings' => round($monthEarnings, 2),
        ], 'portal');
    }

    /* =============================================================
     |  CLIENTS
     ============================================================= */

    public function clients(): void
    {
        $user    = current_user();
        $clients = B2BClient::where(['partner_user_id' => $user['id']], 'created_date DESC');

        foreach ($clients as &$c) {
            if (!empty($c['client_user_id'])) {
                $att = Attendance::where(['user_id' => $c['client_user_id']], 'date DESC', 1);
                $c['last_check_in'] = $att[0]['date'] ?? null;
            }
        }
        unset($c);

        $this->view('partner/clients', [
            'pageTitle' => 'My Clients',
            'clients'   => $clients,
        ], 'portal');
    }

    public function addClient(): void
    {
        csrf_verify();
        $user  = current_user();
        $name  = trim((string)$this->input('client_name'));
        $email = trim((string)$this->input('client_email'));

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Please enter a valid name and email address.');
            $this->redirect('/partner-dashboard/clients');
        }

        B2BClient::create([
            'partner_user_id'   => $user['id'],
            'client_name'       => $name,
            'client_email'      => $email,
            'membership_status' => 'pending',
        ]);

        // Notify the member if that email already has an account.
        if ($member = User::findByEmail($email)) {
            try {
                Database::pdo()->prepare("
                    INSERT INTO `notifications` (`user_id`, `tone`, `title`, `description`, `link`)
                    VALUES (?, 'info', ?, ?, '/home')
                ")->execute([
                    $member['id'],
                    'A coach added you as a client',
                    ($user['full_name'] ?: 'A PowerMagz partner') . ' linked your account as one of their clients.',
                ]);
            } catch (\Throwable $e) {
                error_log('[addClient notification] ' . $e->getMessage());
            }
        }

        flash('success', 'Client link request sent.');
        $this->redirect('/partner-dashboard/clients');
    }

    public function messageClient(): void
    {
        csrf_verify();

        $user = current_user();
        $email = trim((string)$this->input('client_email'));
        $name = trim((string)$this->input('client_name'));
        $message = trim((string)$this->input('message'));

        if ($email === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'A valid email and message are required.');
            $this->redirect('/partner-dashboard/clients');
        }

        // 🔐 SECURITY: Verify this partner actually owns this client
        $client = B2BClient::first([
            'partner_user_id' => $user['id'],
            'client_email'    => $email
        ]);

        if (!$client) {
            flash('error', 'Client not found or not linked to your account.');
            $this->redirect('/partner-dashboard/clients');
        }

        // Send the message (using the mailer helper)
        if (function_exists('send_mail')) {
            $subject = "Message from your coach at PowerMagz Gym";
            $body = "<p>Hi " . e($name) . ",</p><p>" . nl2br(e($message)) . "</p><p>— " . e($user['full_name']) . "</p>";
            send_mail($email, $subject, $body);
        } else {
            // Fallback for local dev
            error_log("[Partner Message] To: {$email} | Msg: {$message}");
        }

        // Log the action
        if (function_exists('audit_log')) {
            audit_log(
                'partner.message_sent',
                'b2b_client',
                $client['id'],
                null,
                null,
                'Sent message to ' . $email
            );
        }

        flash('success', 'Message sent successfully to ' . e($name) . '.');
        $this->redirect('/partner-dashboard/clients');
    }

    /* =============================================================
     |  SALES
     ============================================================= */

    public function sales(): void
    {
        $user    = current_user();
        $profile = $this->ensureProfile();
        $sales   = PartnerSale::where(['partner_user_id' => $user['id']], 'sale_date DESC');

        $this->view('partner/sales', [
            'pageTitle' => 'Sales & Referrals',
            'profile'   => $profile,
            'sales'     => $sales,
        ], 'portal');
    }

    public function logSale(): void
    {
        csrf_verify();
        $user        = current_user();
        $description = trim((string)$this->input('description'));
        $amount      = (float)$this->input('amount');
        $type        = (string)$this->input('type', 'client_payment');

        if ($description === '' || $amount <= 0) {
            flash('error', 'Please enter a description and a valid amount.');
            $this->redirect('/partner-dashboard/sales');
        }

        if (!in_array($type, ['client_payment', 'session'], true)) {
            $type = 'client_payment';
        }

        PartnerSale::create([
            'partner_user_id' => $user['id'],
            'type'            => $type,
            'description'     => $description,
            'amount'          => $amount,
            'sale_date'       => date('Y-m-d'),
            'status'          => 'pending',
        ]);

        flash('success', 'Session logged — pending approval.');
        $this->redirect('/partner-dashboard/sales');
    }

    /* =============================================================
     |  WALLET
     ============================================================= */

    public function wallet(): void
    {
        $user    = current_user();
        $sales   = PartnerSale::where(['partner_user_id' => $user['id']], 'sale_date DESC');
        $payouts = PayoutRequest::where(['partner_user_id' => $user['id']], 'requested_date DESC');

        // FIX: use the commission-based breakdown, not the gross sale sum.
        $breakdown = $this->balanceBreakdown($user['id']);

        $this->view('partner/wallet', [
            'pageTitle' => 'Wallet',
            'sales'     => $sales,
            'payouts'   => $payouts,
            'lifetime'  => $breakdown['lifetime'],
            'available' => $breakdown['available'],
        ], 'portal');
    }

    public function requestPayout(): void
    {
        csrf_verify();
        $user   = current_user();
        $amount = (float)$this->input('amount');
        $method = (string)$this->input('method', 'gcash');
        $details = trim((string)$this->input('method_details', ''));
        $note    = trim((string)$this->input('note', ''));

        if (!in_array($method, ['gcash', 'bank_transfer', 'cash'], true)) {
            flash('error', 'Choose a valid payout method.');
            $this->redirect('/partner-dashboard/wallet');
        }

        if ($method !== 'cash' && (strlen($details) < 6 || strlen($details) > 120)) {
            flash('error', 'Enter the GCash number or bank account for your payout.');
            $this->redirect('/partner-dashboard/wallet');
        }

        if ($amount <= 0) {
            flash('error', 'Enter a valid amount.');
            $this->redirect('/partner-dashboard/wallet');
        }

        // FIX: enforce the available-balance limit server-side, not just in the modal.
        $breakdown = $this->balanceBreakdown($user['id']);
        if ($amount > $breakdown['available'] + 0.01) {
            flash(
                'error',
                'Amount exceeds your available balance of ' . peso($breakdown['available']) . '.'
            );
            $this->redirect('/partner-dashboard/wallet');
        }

        try {
            PayoutRequest::requestPayout(
                (string)$user['id'],
                round($amount, 2),
                $method,
                $details !== '' ? $details : null,
                $note !== '' ? $note : null
            );
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            $this->redirect('/partner-dashboard/wallet');
        }

        flash('success', 'Payout requested.');
        $this->redirect('/partner-dashboard/wallet');
    }

    /* =============================================================
     |  PROFILE
     ============================================================= */

    public function profile(): void
    {
        $profile = $this->ensureProfile();

        $this->view('partner/profile', [
            'pageTitle' => 'My Profile',
            'profile'   => $profile,
        ], 'portal');
    }

    public function saveProfile(): void
    {
        csrf_verify();
        $profile = $this->ensureProfile();

        $businessName = trim((string)$this->input('business_name'));

        if ($businessName === '') {
            flash('error', 'Business name is required.');
            $this->redirect('/partner-dashboard/profile');
        }

        PartnerProfile::update($profile['id'], [
            'business_name' => $businessName,
            'partner_type'  => 'coach',
            'phone'         => $this->input('phone'),
        ]);

        flash('success', 'Profile saved.');
        $this->redirect('/partner-dashboard/profile');
    }

    /* =============================================================
     |  JSON API (partner)
     ============================================================= */

    public function apiClients(): void
    {
        $user    = current_user();
        $clients = B2BClient::where(['partner_user_id' => $user['id']], 'created_date DESC');

        $this->json([
            'ok'   => true,
            'data' => [
                'count'   => count($clients),
                'clients' => array_map(fn($c) => [
                    'id'                => $c['id'],
                    'name'              => $c['client_name'] ?? null,
                    'email'             => $c['client_email'] ?? null,
                    'membership_status' => $c['membership_status'] ?? null,
                ], $clients),
            ],
        ]);
    }

    public function apiWallet(): void
    {
        $user      = current_user();
        $breakdown = $this->balanceBreakdown((string)$user['id']);

        $this->json(['ok' => true, 'data' => $breakdown]);
    }

    public function apiPayout(): void
    {
        csrf_verify();
        $user   = current_user();
        $amount = (float)$this->input('amount');
        $method = (string)$this->input('method', 'gcash');
        $details = trim((string)$this->input('method_details', ''));

        if (!in_array($method, ['gcash', 'bank_transfer', 'cash'], true)) {
            $this->json(['ok' => false, 'error' => 'Choose a valid payout method.'], 422);
        }
        if ($method !== 'cash' && (strlen($details) < 6 || strlen($details) > 120)) {
            $this->json(['ok' => false, 'error' => 'Enter the GCash number or bank account.'], 422);
        }
        if ($amount <= 0) {
            $this->json(['ok' => false, 'error' => 'Enter a valid amount.'], 422);
        }

        $breakdown = $this->balanceBreakdown((string)$user['id']);
        if ($amount > $breakdown['available'] + 0.01) {
            $this->json(['ok' => false, 'error' => 'Amount exceeds your available balance.'], 422);
        }

        try {
            $payout = PayoutRequest::requestPayout(
                (string)$user['id'],
                round($amount, 2),
                $method,
                $details !== '' ? $details : null,
                trim((string)$this->input('note', '')) ?: null
            );
        } catch (\RuntimeException $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        $this->json(['ok' => true, 'data' => $payout], 201);
    }
}
