<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Config\Database;
use App\Models\Payment;
use App\Models\Enrollment;
use App\Models\PartnerSale;
use App\Models\PayoutRequest;

class NotificationController extends Controller
{
    /* ============================================================
       GET /api/notifications
       Returns only UNREAD notifications for the bell dropdown.
       ============================================================ */
    public function index(): void
    {
        $user = current_user();
        if (!$user) {
            $this->json(['items' => [], 'unread_count' => 0]);
            return;
        }

        $items = [];
        $unreadCount = 0;

        // 🔐 Fetch unread notifications from the database
        try {
            $stmt = Database::pdo()->prepare("
                SELECT * FROM `notifications`
                WHERE `user_id` = ? AND `read_at` IS NULL
                ORDER BY `created_date` DESC
                LIMIT 20
            ");
            $stmt->execute([$user['id']]);
            $items = $stmt->fetchAll();

            // Count all unread (for the badge)
            $stmtCount = Database::pdo()->prepare("
                SELECT COUNT(*) FROM `notifications`
                WHERE `user_id` = ? AND `read_at` IS NULL
            ");
            $stmtCount->execute([$user['id']]);
            $unreadCount = (int)$stmtCount->fetchColumn();
        } catch (\Throwable $e) {
            error_log('[NotificationController::index] ' . $e->getMessage());
        }

        // If no database notifications exist, fall back to dynamic alerts
        // (e.g., pending payments that haven't been saved as notifications yet)
        if (empty($items)) {
            $dynamicAlerts = $this->getDynamicAlerts($user);
            $items = $dynamicAlerts;
            $unreadCount = count($dynamicAlerts);
        }

        $this->json([
            'items' => $items,
            'unread_count' => $unreadCount
        ]);
    }

    /* ============================================================
       POST /api/notifications/read
       Marks all notifications for the current user as read.
       ============================================================ */
    public function markAllRead(): void
    {
        csrf_verify();
        $user = current_user();

        if (!$user) {
            $this->json(['status' => 'error'], 401);
            return;
        }

        try {
            $stmt = Database::pdo()->prepare("
                UPDATE `notifications`
                SET `read_at` = NOW()
                WHERE `user_id` = ? AND `read_at` IS NULL
            ");
            $stmt->execute([$user['id']]);

            $this->json(['status' => 'success']);
        } catch (\Throwable $e) {
            error_log('[NotificationController::markAllRead] ' . $e->getMessage());
            $this->json(['status' => 'error'], 500);
        }
    }

    /* ============================================================
       Helper: Generate dynamic alerts (not stored in DB)
       ============================================================ */
    private function getDynamicAlerts(array $user): array
    {
        $items = [];

        if ($user['role'] === 'admin') {
            $payments = Payment::where(['status' => 'pending']);
            if (count($payments)) {
                $items[] = [
                    'id' => 'dyn_payment_' . uniqid(),
                    'tone' => 'warn',
                    'title' => count($payments) . ' payment' . (count($payments) > 1 ? 's' : '') . ' awaiting confirmation',
                    'description' => 'Open the Payments tab to confirm member GCash payments.',
                    'link' => '/admin-dashboard?tab=payments'
                ];
            }
            $sales = PartnerSale::where(['status' => 'pending']);
            if (count($sales)) {
                $items[] = [
                    'id' => 'dyn_sale_' . uniqid(),
                    'tone' => 'warn',
                    'title' => count($sales) . ' partner sale' . (count($sales) > 1 ? 's' : '') . ' to review',
                    'description' => 'Open the Payouts tab to approve logged sales.',
                    'link' => '/admin-dashboard?tab=payouts'
                ];
            }
        } elseif ($user['role'] === 'partner') {
            $sales = PartnerSale::where(['partner_user_id' => $user['id'], 'status' => 'pending']);
            foreach ($sales as $s) {
                $items[] = [
                    'id' => 'dyn_sale_' . $s['id'],
                    'tone' => 'info',
                    'title' => 'Sale awaiting approval',
                    'description' => ($s['description'] ?: 'Sale') . ' — ' . peso($s['amount']) . ' awaiting gym confirmation.',
                    'link' => '/partner-dashboard/sales'
                ];
            }
        } else {
            // Member
            $payments = Payment::where(['user_id' => $user['id'], 'status' => 'pending']);
            foreach ($payments as $p) {
                $label = $p['description'] ?: ($p['tier'] ?: 'Membership');
                $items[] = [
                    'id' => 'dyn_pay_' . $p['id'],
                    'tone' => 'info',
                    'title' => 'Payment awaiting confirmation',
                    'description' => $label . ' — ' . peso($p['amount']) . ". We'll activate your enrollment once confirmed.",
                    'link' => '/payments'
                ];
            }
        }

        return $items;
    }
}
