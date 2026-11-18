<?php

/**
 * Deterministic training guidance. No external API: the same profile always
 * gets the same plan, which keeps the feature honest and testable.
 */

function ai_goal_bucket(string $goal): string
{
    $g = strtolower(trim($goal));
    if ($g === '') return 'general';
    if (str_contains($g, 'fat') || str_contains($g, 'weight') || str_contains($g, 'lose') || str_contains($g, 'slim')) {
        return 'fat_loss';
    }
    if (str_contains($g, 'muscle') || str_contains($g, 'gain') || str_contains($g, 'strong') || str_contains($g, 'bulk')) {
        return 'muscle_gain';
    }
    if (str_contains($g, 'endur') || str_contains($g, 'run') || str_contains($g, 'stamina') || str_contains($g, 'cardio')) {
        return 'endurance';
    }
    return 'general';
}

/**
 * @param array $context tier, goal, visits (attendance rows last 30 days), name
 * @return array{title:string,summary:string,days:array,why:string}
 */
function workout_recommendation(array $context): array
{
    $tier   = (string)($context['tier'] ?? '');
    $goal   = ai_goal_bucket((string)($context['goal'] ?? ''));
    $visits = max(0, (int)($context['visits'] ?? 0));
    $name   = trim((string)($context['name'] ?? ''));
    $gym    = tier_can($tier, 'gym_access');

    $daysPerWeek = $visits >= 12 ? 5 : ($visits >= 6 ? 4 : 3);

    $goalLabel = [
        'fat_loss'    => 'fat loss',
        'muscle_gain' => 'muscle gain',
        'endurance'   => 'endurance',
        'general'     => 'general fitness',
    ][$goal];

    $equipment = $gym ? 'gym equipment' : 'bodyweight only';
    $title = $daysPerWeek . '-Day ' . ucfirst($goalLabel) . ' Plan (' . ($gym ? 'Gym' : 'Digital') . ')';

    $bodyweight = [
        'Squats', 'Push-ups', 'Reverse lunges', 'Plank', 'Glute bridges', 'Mountain climbers',
    ];
    $gym = [
        'Goblet squats', 'Dumbbell bench press', 'Lat pulldown', 'Romanian deadlift',
        'Seated row', 'Cable crunch',
    ];

    $rotation = $goal === 'fat_loss'
        ? ['Full body + finisher', 'Cardio intervals', 'Strength circuit', 'Mobility + walk', 'Upper body', 'Lower body', 'Active recovery']
        : ($goal === 'muscle_gain'
            ? ['Upper push', 'Lower body', 'Upper pull', 'Legs + core', 'Full body', 'Arms + shoulders', 'Rest']
            : ($goal === 'endurance'
                ? ['Steady cardio', 'Full body circuit', 'Intervals', 'Mobility', 'Long walk', 'Strength', 'Rest']
                : ['Full body', 'Cardio', 'Strength', 'Mobility', 'Upper body', 'Lower body', 'Rest']));

    $days = [];
    for ($i = 0; $i < $daysPerWeek; $i++) {
        $focus = $rotation[$i % count($rotation)];
        $exercises = str_contains(strtolower($focus), 'rest') || str_contains(strtolower($focus), 'mobility')
            ? ['Easy walk 20-30 min', 'Stretch hips, chest, and hamstrings']
            : array_slice($equipment === 'gym equipment' ? $gym : $bodyweight, 0, 4);

        $days[] = [
            'day'       => 'Day ' . ($i + 1),
            'focus'     => $focus,
            'exercises' => $exercises,
        ];
    }

    $why = $visits > 0
        ? "Built from your last 30 days: {$visits} visit" . ($visits === 1 ? '' : 's') . " and a {$goalLabel} goal."
        : "Starts at {$daysPerWeek} days a week for a {$goalLabel} goal. Check in with the QR code so the plan adapts.";
    if (!$gym) {
        $why .= ' Digital members train with bodyweight only.';
    }
    if ($name !== '') {
        $why .= ' Keep the same slots each week, ' . $name . '.';
    }

    return [
        'title'   => $title,
        'summary' => "{$daysPerWeek} training days a week, {$equipment}, built around {$goalLabel}.",
        'days'    => $days,
        'why'     => $why,
    ];
}

/**
 * Deterministic coach answer for Premium. Keyword routing, not an LLM.
 */
function ai_coach_reply(string $question, array $context): string
{
    $q = strtolower(trim($question));
    $name = trim((string)($context['name'] ?? ''));
    $who = $name !== '' ? $name : 'there';
    $classes = (int)($context['upcoming_classes'] ?? 0);

    $disclaimer = "\n\nThis is general fitness guidance, not medical advice. Stop if you feel pain and check with a doctor.";

    if ($q === '') {
        return 'Ask me about your schedule, training plan, nutrition, recovery, or classes.' . $disclaimer;
    }

    if (str_contains($q, 'schedule') || str_contains($q, 'class') || str_contains($q, 'book') || str_contains($q, 'time')) {
        return "Hi {$who}. The gym is open 6:00 AM to 7:00 PM daily, and you have {$classes} upcoming class booking" . ($classes === 1 ? '' : 's') . ". Open Group Classes to book or cancel from the schedule." . $disclaimer;
    }

    if (str_contains($q, 'eat') || str_contains($q, 'diet') || str_contains($q, 'protein') || str_contains($q, 'meal') || str_contains($q, 'nutrition')) {
        return "Aim for a palm-sized protein portion at each meal, vegetables at two meals, and water through the day. Around training, eat a normal meal 1-2 hours before and protein within a couple of hours after. Your Premium plan includes nutrition guidance from a coach." . $disclaimer;
    }

    if (str_contains($q, 'sore') || str_contains($q, 'recover') || str_contains($q, 'rest') || str_contains($q, 'sleep')) {
        return "Soreness is normal 24-48 hours after a new session. Keep moving lightly, sleep 7-9 hours, and take a full rest day when a muscle still hurts at rest. Sharp or joint pain is different: stop and get it checked." . $disclaimer;
    }

    if (str_contains($q, 'pay') || str_contains($q, 'price') || str_contains($q, 'gcash') || str_contains($q, 'receipt')) {
        return "Open Payments to see your enrollment and receipts, or Programs to renew. GCash, cash, bank transfer, and card are shown at checkout based on what the gym accepts." . $disclaimer;
    }

    if (str_contains($q, 'coach') || str_contains($q, 'trainer') || str_contains($q, 'session')) {
        return "Personal coaching is part of Premium. Message your coach from your profile, or ask the front desk to match you with one." . $disclaimer;
    }

    if (str_contains($q, 'workout') || str_contains($q, 'plan') || str_contains($q, 'train') || str_contains($q, 'exercise')) {
        $plan = workout_recommendation($context);
        $day = $plan['days'][0] ?? null;
        $first = $day ? $day['focus'] . ': ' . implode(', ', $day['exercises']) : 'Start with a full-body session.';
        return "Your current plan is {$plan['title']}. First session — {$first}. Open Workout Plan for the full week." . $disclaimer;
    }

    return "I can help with your class schedule, training plan, nutrition basics, recovery, and payments. Ask one of those and I will point you to the right screen." . $disclaimer;
}
