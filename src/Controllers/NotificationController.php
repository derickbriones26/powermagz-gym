<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\PartnerSale;
use App\Models\PayoutRequest;

class NotificationController extends Controller
{
    public function index(): void
    {
        $user = current_user();
        if (!$user) {
            $this->json(['items' => []]);
            return;
        }

        $items = match ($user['role']) {
            'admin'   => $this->forAdmin(),
            'partner' => $this->forPartner($user),
            default   => $this->forMember($user),
        };

        $this->json(['items' => $items]);
    }

    /* ---------------- ADMIN ---------------- */
    private function forAdmin(): array
    {
        $items = [];

        $payments = Payment::where(['status' => 'pending']);
        if (count($payments)) {
            $n = count($payments);
            $items[] = [
                'tone'  => 'warn',
                'title' => "$n payment" . ($n > 1 ? 's' : '') . " awaiting confirmation",
                'desc'  => 'Open the Payments tab to confirm member GCash payments.',
            ];
        }

        $sales = PartnerSale::where(['status' => 'pending']);
        if (count($sales)) {
            $n = count($sales);
            $items[] = [
                'tone'  => 'warn',
                'title' => "$n partner sale" . ($n > 1 ? 's' : '') . " to review",
                'desc'  => 'Open the Payouts tab to approve logged sales.',
            ];
        }

        $payouts = PayoutRequest::where(['status' => 'pending']);
        if (count($payouts)) {
            $n = count($payouts);
            $items[] = [
                'tone'  => 'warn',
                'title' => "$n payout request" . ($n > 1 ? 's' : '') . " pending",
                'desc'  => 'Partners are waiting for their payouts.',
            ];
        }

        return $items;
    }

    /* ---------------- MEMBER ---------------- */
    private function forMember(array $user): array
    {
        $items = [];

        $payments = Payment::where(['user_id' => $user['id'], 'status' => 'pending']);
        foreach ($payments as $p) {
            $label = $p['description'] ?: ($p['tier'] ?: 'Membership');
            $items[] = [
                'tone'  => 'info',
                'title' => 'Payment awaiting confirmation',
                'desc'  => $label . ' — ' . peso($p['amount']) . ". We'll activate your enrollment once confirmed.",
            ];
        }

        $active = Enrollment::where(['user_id' => $user['id'], 'status' => 'active']);
        foreach ($active as $e) {
            if (empty($e['end_date'])) continue;
            $days = (int)ceil((strtotime($e['end_date']) - time()) / 86400);
            if ($days >= 0 && $days <= 3) {
                $label = $e['program_name'] ?: 'Your membership';
                $when  = $days === 0 ? 'today' : "in {$days} day" . ($days > 1 ? 's' : '');
                $items[] = [
                    'tone'  => 'warn',
                    'title' => 'Membership expiring soon',
                    'desc'  => "{$label} expires {$when} — renew on the Programs page.",
                ];
            }
        }

        $balance = 0;
        foreach (Enrollment::where(['user_id' => $user['id']]) as $e) {
            if (in_array($e['status'], ['active', 'pending'], true)) {
                $balance += (float)$e['balance'];
            }
        }
        if ($balance > 0) {
            $items[] = [
                'tone'  => 'warn',
                'title' => 'Outstanding balance',
                'desc'  => 'You have ' . peso($balance) . ' in dues. Settle at the front desk or via GCash.',
            ];
        }

        return $items;
    }

    /* ---------------- PARTNER ---------------- */
    private function forPartner(array $user): array
    {
        $items = [];

        $sales = PartnerSale::where(['partner_user_id' => $user['id'], 'status' => 'pending']);
        foreach ($sales as $s) {
            $items[] = [
                'tone'  => 'info',
                'title' => 'Sale awaiting approval',
                'desc'  => ($s['description'] ?: 'Sale') . ' — ' . peso($s['amount']) . ' awaiting gym confirmation.',
            ];
        }

        $payouts = PayoutRequest::where(['partner_user_id' => $user['id']]);
        foreach ($payouts as $p) {
            if ($p['status'] === 'pending') {
                $items[] = [
                    'tone'  => 'info',
                    'title' => 'Payout pending',
                    'desc'  => 'Your ' . peso($p['amount']) . ' payout request is awaiting approval.',
                ];
            } elseif ($p['status'] === 'approved') {
                $items[] = [
                    'tone'  => 'success',
                    'title' => 'Payout approved',
                    'desc'  => peso($p['amount']) . ' has been approved and sent.',
                ];
            }
        }

        return $items;
    }
}
