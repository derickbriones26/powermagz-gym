<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\PartnerProfile;
use App\Models\PartnerSale;
use App\Models\PayoutRequest;
use App\Models\B2BClient;
use App\Models\Attendance;

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
        $commission = (float)($sale['commission_amount'] ?? 0);

        if ($commission <= 0) {
            $rate = (float)($sale['commission_rate']
                ?? $sale['partner_commission_rate']
                ?? 10);
            if ($rate <= 0) $rate = 10;
            $commission = (float)($sale['amount'] ?? 0) * ($rate / 100);
        }

        return round($commission, 2);
    }

    /**
     * Compute a partner's lifetime earnings, committed payouts, and
     * available balance — all in one pass.
     *
     * @return array{lifetime:float, committed:float, available:float}
     */
    private function balanceBreakdown(string $partnerUserId): array
    {
        $sales   = PartnerSale::where(['partner_user_id' => $partnerUserId]);
        $payouts = PayoutRequest::where(['partner_user_id' => $partnerUserId]);

        $lifetime = 0.0;
        foreach ($sales as $s) {
            if (($s['status'] ?? '') !== 'approved') continue;
            $lifetime += $this->commissionOf($s);
        }

        $committed = 0.0;
        foreach ($payouts as $p) {
            // Pending and approved payouts reserve funds. Rejected ones don't.
            if (($p['status'] ?? '') !== 'rejected') {
                $committed += (float)($p['amount'] ?? 0);
            }
        }

        return [
            'lifetime'  => round($lifetime, 2),
            'committed' => round($committed, 2),
            'available' => max(0, round($lifetime - $committed, 2)),
        ];
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

        if ($name === '' || $email === '') {
            flash('error', 'Please enter name and email.');
            $this->redirect('/partner-dashboard/clients');
        }

        B2BClient::create([
            'partner_user_id'   => $user['id'],
            'client_name'       => $name,
            'client_email'      => $email,
            'membership_status' => 'pending',
        ]);

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

        if ($email === '' || $message === '') {
            flash('error', 'Email and message are required.');
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
        $type        = (string)$this->input('type', 'product');

        if ($description === '' || $amount <= 0) {
            flash('error', 'Please enter a description and a valid amount.');
            $this->redirect('/partner-dashboard/sales');
        }

        if (!in_array($type, ['product', 'client_payment'], true)) {
            $type = 'product';
        }

        PartnerSale::create([
            'partner_user_id' => $user['id'],
            'type'            => $type,
            'description'     => $description,
            'amount'          => $amount,
            'sale_date'       => date('Y-m-d'),
            'status'          => 'pending',
        ]);

        flash('success', 'Sale logged — pending approval.');
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

        PayoutRequest::create([
            'partner_user_id' => $user['id'],
            'amount'          => round($amount, 2),
            'requested_date'  => date('Y-m-d'),
            'status'          => 'pending',
            'note'            => $this->input('note'),
        ]);

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
        $partnerType  = (string)$this->input('partner_type', 'coach');

        if ($businessName === '') {
            flash('error', 'Business name is required.');
            $this->redirect('/partner-dashboard/profile');
        }
        if (!in_array($partnerType, ['coach', 'supplier'], true)) {
            $partnerType = 'coach';
        }

        PartnerProfile::update($profile['id'], [
            'business_name' => $businessName,
            'partner_type'  => $partnerType,
            'phone'         => $this->input('phone'),
        ]);

        flash('success', 'Profile saved.');
        $this->redirect('/partner-dashboard/profile');
    }
}
