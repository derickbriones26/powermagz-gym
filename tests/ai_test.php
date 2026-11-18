<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/Helpers/capabilities.php';
require_once __DIR__ . '/../src/Helpers/ai.php';

function check(bool $cond, string $msg): void
{
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

$digital = workout_recommendation(['tier' => 'digital', 'goal' => 'lose weight', 'visits' => 3, 'name' => 'Ana']);
check(str_contains($digital['title'], 'Digital'), 'digital plan is labelled Digital');
check(str_contains($digital['summary'], 'bodyweight'), 'digital plan uses bodyweight only');
check(str_contains($digital['title'], 'Fat loss'), 'goal maps to fat loss');
check(count($digital['days']) === 3, 'low attendance starts at 3 days');

$premium = workout_recommendation(['tier' => 'premium', 'goal' => 'build muscle', 'visits' => 14, 'name' => 'Bo']);
check(str_contains($premium['title'], 'Gym'), 'premium plan uses the gym');
check(str_contains($premium['summary'], 'gym equipment'), 'premium summary mentions equipment');
check(count($premium['days']) === 5, 'high attendance reaches 5 days');
check(str_contains($premium['why'], '14 visits'), 'why cites attendance');

$schedule = ai_coach_reply('What is my class schedule?', ['name' => 'Ana', 'upcoming_classes' => 2]);
check(str_contains($schedule, '6:00 AM'), 'schedule mentions opening time');
check(str_contains($schedule, '2 upcoming class bookings'), 'schedule counts bookings');
check(str_contains($schedule, 'not medical advice'), 'disclaimer present');

$food = ai_coach_reply('How much protein should I eat?', []);
check(str_contains($food, 'protein'), 'nutrition question is answered');

$fallback = ai_coach_reply('hello', []);
check(str_contains($fallback, 'class schedule'), 'fallback points to supported topics');

echo "OK ai\n";
