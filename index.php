<?php

declare(strict_types=1);
require_once __DIR__ . '/src/bootstrap.php';

use App\Core\Router;

$router = new Router();

/* ==================================================================
 |  LEGEND
 |  ──────────────────────────────────────────────────────────────
 |  #1.1    Membership Tier & Program Management
 |  #1.2    Member Enrollment & Lifecycle
 |  #1.3    Payment & Revenue
 |  #1.4    Attendance & Check-In
 |  #1.5    Partner & B2B
 |  #1.6    Inclusive Form & Data Entry
 |  #2.1    RBAC & Auth (MFA, idle, re-auth, audit)
 |  #2.2    Infrastructure / Settings
 | ================================================================== */


/* ====================== PUBLIC ====================== */
$router->get('/', 'HomeController@index');


/* ====================== AUTH ====================== */
$router->get('/login',            'AuthController@showLogin');
$router->post('/login',           'AuthController@login');
$router->get('/register',         'AuthController@showRegister');
$router->post('/register',        'AuthController@register');
$router->get('/register/verify',  'AuthController@showVerify');
$router->post('/register/verify', 'AuthController@verifyOtp');
$router->post('/register/resend', 'AuthController@resendOtp');
$router->get('/forgot-password',  'AuthController@showForgot');
$router->post('/forgot-password', 'AuthController@sendReset');
$router->get('/reset-password',   'AuthController@showReset');
$router->post('/reset-password',  'AuthController@resetPassword');
$router->post('/logout',          'AuthController@logout');


/* ====================== §2.1 — MFA ====================== */
/* Challenge routes are intentionally UNauthenticated — the user is only
   partially logged in (password OK, TOTP pending). The controller
   validates $_SESSION['mfa_pending_user_id'] internally. */

$router->get('/mfa/challenge',          'MfaController@showChallenge');
$router->post('/mfa/challenge',         'MfaController@submitChallenge');
$router->post('/mfa/recovery',          'MfaController@useRecoveryCode');

/* Enrollment / management routes require a full login */
$router->get('/mfa/setup',              'MfaController@showSetup',              ['auth', 'role:admin,staff']);
$router->post('/mfa/setup',             'MfaController@beginSetup',             ['auth', 'role:admin,staff']);
$router->post('/mfa/verify',            'MfaController@verify',                 ['auth', 'role:admin,staff']);
$router->post('/mfa/disable',           'MfaController@disable',                ['auth', 'role:admin,staff']);
$router->post('/mfa/regenerate-codes',  'MfaController@regenerateRecoveryCodes', ['auth', 'role:admin,staff']);


/* ====================== §2.1 — RE-AUTH & SESSION ====================== */
$router->get('/reauth',           'AuthController@showReauth', ['auth']);
$router->post('/reauth',          'AuthController@reauth',     ['auth']);
$router->post('/session/ping',    'AuthController@ping',       ['auth']);


/* ====================== MEMBER ====================== */
$router->get('/home',             'MemberController@home',        ['auth']);
$router->get('/profile',          'MemberController@profile',     ['auth']);
$router->post('/profile',         'MemberController@saveProfile', ['auth']);
$router->get('/programs',         'MemberController@programs',    ['auth']);
$router->post('/enroll',          'MemberController@enroll',      ['auth']);
$router->get('/payments',         'MemberController@payments',    ['auth']);
$router->get('/time-in',          'MemberController@timeIn',      ['auth']);
$router->post('/time-in/scan',    'MemberController@scanTimeIn',  ['auth']);
$router->post('/time-in/out',     'MemberController@timeOut',     ['auth']);
$router->get('/attendance',       'MemberController@attendance',  ['auth']);


/* ====================== PARTNER ====================== */
$router->get('/partner-dashboard',          'PartnerController@dashboard',       ['auth', 'role:partner,admin']);
$router->get('/partner-dashboard/clients',  'PartnerController@clients',         ['auth', 'role:partner,admin']);
$router->post('/partner-dashboard/clients', 'PartnerController@addClient',       ['auth', 'role:partner,admin']);
$router->post('/partner-dashboard/message-client', 'PartnerController@messageClient', ['auth', 'role:partner,admin']);
$router->get('/partner-dashboard/sales',    'PartnerController@sales',           ['auth', 'role:partner,admin']);
$router->post('/partner-dashboard/sales',   'PartnerController@logSale',         ['auth', 'role:partner,admin']);
$router->get('/partner-dashboard/wallet',   'PartnerController@wallet',          ['auth', 'role:partner,admin']);
$router->post('/partner-dashboard/payout',  'PartnerController@requestPayout',   ['auth', 'role:partner,admin']);
$router->get('/partner-dashboard/profile',  'PartnerController@profile',         ['auth', 'role:partner,admin']);
$router->post('/partner-dashboard/profile', 'PartnerController@saveProfile',     ['auth', 'role:partner,admin']);


/* ====================== ADMIN — MAIN ====================== */
$router->get('/admin-dashboard', 'AdminController@dashboard', ['auth', 'role:admin,staff']);

/* ====================== ADMIN — PROGRAMS (§1.1) ====================== */
$router->post('/admin-dashboard/programs',           'AdminController@saveProgram',           ['auth', 'role:admin']);
$router->post('/admin-dashboard/programs/delete',    'AdminController@deleteProgram',         ['auth', 'role:admin']);
$router->post('/admin-dashboard/programs/toggle',    'AdminController@toggleProgram',         ['auth', 'role:admin']);
$router->post('/admin-dashboard/programs/preview',   'AdminController@toggleProgramPreview',  ['auth', 'role:admin']);
$router->post('/admin-dashboard/programs/duplicate', 'AdminController@duplicateProgram',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/programs/reorder',   'AdminController@reorderPrograms',       ['auth', 'role:admin']);

/* ====================== ADMIN — MEMBERS (§1.2) ====================== */
$router->post('/admin-dashboard/members/update',         'AdminController@updateMember',        ['auth', 'role:admin']);
$router->post('/admin-dashboard/members/delete',         'AdminController@deleteMember',        ['auth', 'role:admin']);
$router->post('/admin-dashboard/members/role',           'AdminController@changeMemberRole',    ['auth', 'role:admin']);
$router->post('/admin-dashboard/members/reset-password', 'AdminController@resetMemberPassword', ['auth', 'role:admin']);
$router->get('/admin-dashboard/members/export',          'AdminController@exportMembers',       ['auth', 'role:admin,staff']);

/* ====================== ADMIN — ENROLLMENTS (§1.2) ====================== */
$router->post('/admin-dashboard/enrollments/approve',     'AdminController@approveEnrollment',     ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/update',      'AdminController@updateEnrollment',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/extend',      'AdminController@extendEnrollment',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/pause',       'AdminController@pauseEnrollment',       ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/resume',      'AdminController@resumeEnrollment',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/cancel',      'AdminController@cancelEnrollment',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/delete',      'AdminController@deleteEnrollment',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/upgrade',     'AdminController@upgradeEnrollment',     ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/downgrade',   'AdminController@downgradeEnrollment',   ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/expire',      'AdminController@expireEnrollment',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/enrollments/batch-renew', 'AdminController@batchRenewEnrollments', ['auth', 'role:admin']);
$router->get('/admin-dashboard/enrollments/export',       'AdminController@exportEnrollments',     ['auth', 'role:admin,staff']);

/* ====================== ADMIN — PAYMENTS (§1.3) ====================== */
$router->post('/admin-dashboard/payments/confirm',        'AdminController@confirmPayment',      ['auth', 'role:admin,staff']);
$router->post('/admin-dashboard/payments/bulk-confirm',   'AdminController@bulkConfirmPayments', ['auth', 'role:admin,staff']);
$router->post('/admin-dashboard/payments/refund',         'AdminController@refundPayment',       ['auth', 'role:admin']);
$router->post('/admin-dashboard/payments/mark-failed',    'AdminController@markPaymentFailed',   ['auth', 'role:admin,staff']);
$router->get('/admin-dashboard/payments/receipt',         'AdminController@paymentReceipt',      ['auth', 'role:admin,staff']);
$router->get('/admin-dashboard/payments/export',          'AdminController@exportPayments',      ['auth', 'role:admin,staff']);
$router->get('/admin-dashboard/payments/aging',           'AdminController@paymentAgingReport',  ['auth', 'role:admin,staff']);
$router->post('/admin-dashboard/payments/send-reminders', 'AdminController@sendPaymentReminders', ['auth', 'role:admin']);

/* ====================== ADMIN — ATTENDANCE (§1.4) ====================== */
$router->post('/admin-dashboard/attendance/out',       'AdminController@clockOut',         ['auth', 'role:admin,staff']);
$router->post('/admin-dashboard/attendance/manual-in', 'AdminController@manualTimeIn',     ['auth', 'role:admin,staff']);
$router->post('/admin-dashboard/attendance/update',    'AdminController@updateAttendance', ['auth', 'role:admin']);
$router->post('/admin-dashboard/attendance/delete',    'AdminController@deleteAttendance', ['auth', 'role:admin']);
$router->get('/admin-dashboard/attendance/export',     'AdminController@exportAttendance', ['auth', 'role:admin,staff']);

/* ====================== ADMIN — PARTNER SALES (§1.5) ====================== */
$router->post('/admin-dashboard/sales/approve', 'AdminController@approveSale', ['auth', 'role:admin']);
$router->post('/admin-dashboard/sales/reject',  'AdminController@rejectSale',  ['auth', 'role:admin']);
$router->post('/admin-dashboard/sales/log',     'AdminController@logSale',     ['auth', 'role:admin,staff']);

/* ====================== ADMIN — PAYOUTS (§1.5) ====================== */
$router->post('/admin-dashboard/payouts/set',           'AdminController@setPayoutStatus',   ['auth', 'role:admin']);
$router->post('/admin-dashboard/payouts/mark-paid',     'AdminController@markPayoutPaid',    ['auth', 'role:admin']);
$router->post('/admin-dashboard/payouts/batch',         'AdminController@createPayoutBatch', ['auth', 'role:admin']);
$router->post('/admin-dashboard/payouts/batch/process', 'AdminController@processPayoutBatch', ['auth', 'role:admin']);
$router->get('/admin-dashboard/payouts/export',         'AdminController@exportPayouts',     ['auth', 'role:admin,staff']);

/* ====================== ADMIN — PARTNERS (§1.5) ====================== */
$router->post('/admin-dashboard/partners/update', 'AdminController@updatePartner',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/partners/toggle', 'AdminController@togglePartnerStatus', ['auth', 'role:admin']);
$router->post('/admin-dashboard/partners/delete', 'AdminController@deletePartner',      ['auth', 'role:admin']);
$router->get('/admin-dashboard/partners/export',  'AdminController@exportPartners',      ['auth', 'role:admin,staff']);

/* ====================== ADMIN — B2B CLIENTS (§1.5) ====================== */
$router->post('/admin-dashboard/b2b-clients/update', 'AdminController@updateB2bClient', ['auth', 'role:admin']);
$router->post('/admin-dashboard/b2b-clients/delete', 'AdminController@deleteB2bClient', ['auth', 'role:admin']);
$router->get('/admin-dashboard/b2b-clients/export',  'AdminController@exportB2bClients', ['auth', 'role:admin,staff']);

/* ====================== ADMIN — SETTINGS (§2.2) ====================== */
$router->post('/admin-dashboard/settings/payment-methods',  'AdminController@savePaymentMethods',      ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings/collection',       'AdminController@saveCollectionFrequency', ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings/grace-period',     'AdminController@saveGracePeriod',         ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings/operational-cost', 'AdminController@saveOperationalCost',     ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings/commission',       'AdminController@saveDefaultCommission',   ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings/gym-capacity',     'AdminController@saveGymCapacity',         ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings/idle-timeout',     'AdminController@saveIdleTimeout',         ['auth', 'role:admin']);
$router->post('/admin-dashboard/settings',                  'AdminController@saveSetting',             ['auth', 'role:admin']);

/* ====================== ADMIN — AUDIT (§2.1) ====================== */
$router->get('/admin-dashboard/audit/export', 'AdminController@exportAuditLogs', ['auth', 'role:admin']);
$router->post('/admin-dashboard/audit/purge', 'AdminController@purgeAuditLogs',  ['auth', 'role:admin']);

/* ====================== ADMIN — NOTIFICATIONS ====================== */
$router->post('/admin-dashboard/notifications/read',     'NotificationController@markRead',    ['auth']);
$router->post('/admin-dashboard/notifications/read-all', 'NotificationController@markAllRead', ['auth']);


/* ====================== API ====================== */
$router->get('/api/occupancy',       'MemberController@occupancy');
$router->get('/api/notifications',   'NotificationController@index',   ['auth']);
$router->get('/api/admin/occupancy', 'AdminController@liveOccupancy',  ['auth', 'role:admin,staff']);
$router->get('/api/admin/kpis',      'AdminController@kpis',           ['auth', 'role:admin,staff']);


/* ====================== DISPATCH ====================== */
$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
