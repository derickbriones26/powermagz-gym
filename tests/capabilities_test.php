<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers/capabilities.php';

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$digital  = tier_capabilities('digital');
$standard = tier_capabilities('standard');
$premium  = tier_capabilities('premium');
$b2b      = tier_capabilities('b2b');

check($digital['qr_checkin'] === true, 'Digital has QR check-in');
check($digital['ai_recommender'] === true, 'Digital has AI recommender');
check($digital['gym_access'] === false, 'Digital has no gym access');
check($digital['group_classes'] === false, 'Digital has no classes');

check($standard['gym_access'] === true, 'Standard has gym access');
check($standard['group_classes'] === true, 'Standard has classes');
check($standard['ai_coach'] === false, 'Standard has no AI coach');
check($standard['class_priority_booking'] === false, 'Standard has no priority booking');

check($premium['ai_coach'] === true, 'Premium has AI coach');
check($premium['personal_coaching'] === true, 'Premium has coaching');
check($premium['nutrition_guidance'] === true, 'Premium has nutrition');
check($premium['class_priority_booking'] === true, 'Premium has priority booking');

foreach ($b2b as $cap => $on) {
    check($on === false, "b2b capability {$cap} is off");
}

check(tier_can('digital', 'qr_checkin') === true, 'tier_can digital QR');
check(tier_can('digital', 'gym_access') === false, 'tier_can digital gym');
check(tier_can('unknown', 'qr_checkin') === false, 'unknown tier has nothing');

$meta = capability_meta();
check(isset($meta['qr_checkin']['label']), 'meta has QR label');
check(count($meta) === 10, 'meta covers all ten capabilities');

echo "OK capabilities\n";
