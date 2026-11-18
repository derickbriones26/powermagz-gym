<?php

declare(strict_types=1);

/**
 * Canonical tier capability map. Every portal reads this — no duplicated
 * conditionals. Values are booleans keyed by capability id.
 */
function tier_capabilities(string $tier): array
{
    $caps = [
        'qr_checkin'             => false,
        'attendance_calendar'    => false,
        'digital_workouts'       => false,
        'ai_recommender'         => false,
        'gym_access'             => false,
        'group_classes'          => false,
        'class_priority_booking' => false,
        'personal_coaching'      => false,
        'nutrition_guidance'     => false,
        'ai_coach'               => false,
    ];

    if ($tier === 'digital') {
        $caps['qr_checkin']          = true;
        $caps['attendance_calendar'] = true;
        $caps['digital_workouts']    = true;
        $caps['ai_recommender']      = true;
    }

    if ($tier === 'standard' || $tier === 'premium') {
        $caps['qr_checkin']          = true;
        $caps['attendance_calendar'] = true;
        $caps['digital_workouts']    = true;
        $caps['ai_recommender']      = true;
        $caps['gym_access']          = true;
        $caps['group_classes']       = true;
    }

    if ($tier === 'premium') {
        $caps['class_priority_booking'] = true;
        $caps['personal_coaching']      = true;
        $caps['nutrition_guidance']     = true;
        $caps['ai_coach']               = true;
    }

    return $caps;
}

function tier_can(string $tier, string $capability): bool
{
    return (tier_capabilities($tier)[$capability] ?? false) === true;
}

/** Capabilities for the signed-in member, based on the active enrollment only. */
function member_capabilities(): array
{
    $user = function_exists('current_user') ? current_user() : null;
    if (!$user) {
        return tier_capabilities('');
    }

    $active = \App\Models\Enrollment::activeForUser((string)$user['id']);
    return tier_capabilities((string)($active['tier'] ?? ''));
}

function member_can(string $capability): bool
{
    return (member_capabilities()[$capability] ?? false) === true;
}

function capability_meta(): array
{
    return [
        'qr_checkin' => [
            'label'       => 'QR Time In/Out',
            'description' => 'Scan in and out at the gym.',
            'upgrade'     => '',
        ],
        'attendance_calendar' => [
            'label'       => 'Attendance Calendar',
            'description' => 'Track every visit in your calendar.',
            'upgrade'     => '',
        ],
        'digital_workouts' => [
            'label'       => 'Digital Workouts',
            'description' => 'Follow guided plans in the portal.',
            'upgrade'     => '',
        ],
        'ai_recommender' => [
            'label'       => 'Workout Recommender',
            'description' => 'A plan built from your goal and attendance.',
            'upgrade'     => '',
        ],
        'gym_access' => [
            'label'       => 'Full Gym Access',
            'description' => 'Use the gym floor and equipment.',
            'upgrade'     => 'Available on Standard and Premium.',
        ],
        'group_classes' => [
            'label'       => 'Group Classes',
            'description' => 'Book yoga, HIIT, strength, and boxing.',
            'upgrade'     => 'Available on Standard and Premium.',
        ],
        'class_priority_booking' => [
            'label'       => 'Priority Booking',
            'description' => 'Book classes before standard members.',
            'upgrade'     => 'Available on Premium.',
        ],
        'personal_coaching' => [
            'label'       => 'Personal Coaching',
            'description' => 'One-on-one sessions with a coach.',
            'upgrade'     => 'Available on Premium.',
        ],
        'nutrition_guidance' => [
            'label'       => 'Nutrition Guidance',
            'description' => 'Meal guidance aligned to your goal.',
            'upgrade'     => 'Available on Premium.',
        ],
        'ai_coach' => [
            'label'       => 'AI Coach 24/7',
            'description' => 'Ask about your plan, schedule, and classes.',
            'upgrade'     => 'Available on Premium.',
        ],
    ];
}
