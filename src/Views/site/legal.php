<?php
/**
 * Public legal page — Privacy Policy and Terms & Conditions.
 * One template, content keyed by $doc so both pages share the same layout.
 */
$doc = $doc ?? 'privacy';

$docs = [
    'privacy' => [
        'sections' => [
            [
                'id'    => 'collect',
                'title' => 'Information we collect',
                'paras' => ['We only collect what the gym needs to run your membership.'],
                'items' => [
                    '<strong>Account details:</strong> your full name, email address and phone number. Passwords are stored hashed, never in plain text.',
                    '<strong>Membership:</strong> your plan, tier, start and end dates, and status.',
                    '<strong>Payments:</strong> amount, method (GCash, cash, bank transfer or card), reference number and payment date. We do not store full card numbers or CVVs.',
                    '<strong>Attendance:</strong> QR time-in and time-out records, visit dates, duration and group class bookings.',
                    '<strong>Coaching:</strong> fitness goal, workout preferences and the questions you submit to the AI coach.',
                    '<strong>Technical:</strong> a session cookie that keeps you logged in and basic server logs used to keep the service secure.',
                ],
            ],
            [
                'id'    => 'use',
                'title' => 'How we use your information',
                'paras' => ['Your information is used to:'],
                'items' => [
                    'activate and manage your membership and renewals;',
                    'confirm payments and issue receipts;',
                    'record attendance and class bookings;',
                    'deliver coaching, nutrition guidance and AI workout recommendations;',
                    'send account, payment and schedule notifications; and',
                    'protect the gym, staff and members from fraud or misuse.',
                ],
            ],
            [
                'id'    => 'sharing',
                'title' => 'When we share it',
                'paras' => [
                    'We do not sell your personal information. We share it only with payment providers and banks to process the method you chose, with your coach for the services you booked, and with service providers who host or secure the system. We may also disclose information when required by law.',
                ],
            ],
            [
                'id'    => 'retention',
                'title' => 'Retention and security',
                'paras' => [
                    'Membership, payment and attendance records are kept while your account is active and for as long as needed for accounting, legal and dispute purposes. Access is limited to authorised staff and partners. We use hashed passwords, session controls and access checks, but no system can be perfectly secure.',
                ],
            ],
            [
                'id'    => 'rights',
                'title' => 'Your choices and rights',
                'paras' => ['You may ask us to:'],
                'items' => [
                    'see the personal information we hold about you;',
                    'correct information that is wrong or out of date;',
                    'delete information we no longer need; or',
                    'stop non-essential communications.',
                ],
                'note'  => 'Some records, such as paid invoices and attendance required for tax or safety, must be kept for a set period even after an account is closed.',
            ],
            [
                'id'    => 'cookies',
                'title' => 'Cookies and sessions',
                'paras' => [
                    'We use a session cookie to keep you signed in and a small preference value so the intro animation does not replay on every visit. We do not use advertising cookies.',
                ],
            ],
            [
                'id'    => 'children',
                'title' => 'Children',
                'paras' => [
                    'Memberships are intended for people aged 18 and over. A minor may train only with the written consent of a parent or guardian, who accepts these terms on their behalf.',
                ],
            ],
            [
                'id'    => 'contact',
                'title' => 'Contact us',
                'paras' => [
                    'For any privacy question or request, email <a href="mailto:powermagzgym@gmail.com">powermagzgym@gmail.com</a>, call <a href="tel:+639618041523">0961 804 1523</a>, or visit the front desk at Zone 4, Matagbac, Tabaco City, Albay.',
                ],
            ],
        ],
    ],
    'terms' => [
        'sections' => [
            [
                'id'    => 'acceptance',
                'title' => 'Acceptance',
                'paras' => [
                    'By creating an account, enrolling in a plan, booking a class or entering the gym, you agree to these terms. If you do not agree, please do not use the service.',
                ],
            ],
            [
                'id'    => 'membership',
                'title' => 'Membership and tiers',
                'paras' => ['Memberships run for 30 days unless stated otherwise. Prices are in Philippine pesos.'],
                'items' => [
                    '<strong>Digital — ₱500 per 30 days:</strong> digital workouts, QR time in/out, attendance calendar and the AI workout recommender.',
                    '<strong>Standard — ₱1,000 to start (₱500 membership + ₱500 first month), then ₱500 monthly:</strong> full gym access, QR time in/out, attendance tracking, group classes and all accepted payment methods.',
                    '<strong>Premium — ₱2,000 per month:</strong> everything in Standard plus personal coaching, nutrition guidance, priority class booking and the 24/7 AI coach.',
                ],
                'note'  => 'We may change prices or plan contents with reasonable notice. A change never applies to a period you have already paid for.',
            ],
            [
                'id'    => 'payments',
                'title' => 'Payments and renewals',
                'paras' => [
                    'You choose from the payment methods the gym enables (GCash, cash, bank transfer or card). For cash and card payments made at the desk, staff confirm the payment before your membership or booking is marked paid. For GCash and bank transfers, enter the exact reference number so we can match the payment.',
                    'Your membership activates only after the gym confirms payment. Renew before the end date to avoid interruption. Unpaid balances may pause access until settled. Refunds are handled case by case and are not automatic once a period has started.',
                ],
            ],
            [
                'id'    => 'checkin',
                'title' => 'QR check-in and attendance',
                'paras' => [
                    'Your account has a personal QR code for time in and time out. Do not share it. Every entry is recorded, and staff may ask for identification. Only you may use your membership; lending an account or QR code can lead to suspension.',
                ],
            ],
            [
                'id'    => 'classes',
                'title' => 'Group classes',
                'paras' => [
                    'Group classes are available on Standard and Premium plans. Standard members may book up to 7 days ahead; Premium members up to 14 days ahead. Seats are limited and confirmed when you book. Please cancel a booking you cannot attend so the seat returns to the schedule. Repeated no-shows may limit future bookings.',
                ],
            ],
            [
                'id'    => 'coaching',
                'title' => 'Coaching, nutrition and AI',
                'paras' => [
                    'Coaching and nutrition guidance are general fitness support, not medical advice. The AI coach and workout recommender use fixed rules and the information you provide; they do not diagnose or treat any condition. Consult a doctor before starting a program, especially if you are pregnant, injured or managing an illness.',
                ],
            ],
            [
                'id'    => 'rules',
                'title' => 'Facility rules',
                'paras' => ['To keep the gym safe for everyone:'],
                'items' => [
                    'wear proper training clothes and footwear;',
                    're-rack weights and wipe down equipment after use;',
                    'do not bring food onto the training floor;',
                    'follow coach and staff instructions, and',
                    'treat other members and staff with respect.',
                ],
                'note'  => 'PowerMagz Gym is not responsible for lost or stolen personal belongings. Use the lockers and keep valuables with you.',
            ],
            [
                'id'    => 'account',
                'title' => 'Your account',
                'paras' => [
                    'Keep your login details private and tell us right away if you think someone else has used your account. You are responsible for activity made through your account until you report it.',
                ],
            ],
            [
                'id'    => 'suspension',
                'title' => 'Suspension and cancellation',
                'paras' => [
                    'We may suspend or end a membership for non-payment, sharing an account or QR code, damaging equipment, or behaviour that endangers others. You may stop using the service at any time; amounts already paid for a period that has started are generally not refunded.',
                ],
            ],
            [
                'id'    => 'liability',
                'title' => 'Liability',
                'paras' => [
                    'Training carries a risk of injury. You train at your own risk and should use equipment and coaching as instructed. To the extent allowed by law, PowerMagz Gym is not liable for injury, loss or damage caused by misuse of the facility or by ignoring staff and coach instructions.',
                ],
            ],
            [
                'id'    => 'changes',
                'title' => 'Changes to these terms',
                'paras' => [
                    'We may update these terms as the gym or the law changes. The date at the top shows the current version. Continuing to use the service after an update means you accept the new terms.',
                ],
            ],
            [
                'id'    => 'contact',
                'title' => 'Contact',
                'paras' => [
                    'Questions about these terms? Email <a href="mailto:powermagzgym@gmail.com">powermagzgym@gmail.com</a>, call <a href="tel:+639618041523">0961 804 1523</a>, or visit Zone 4, Matagbac, Tabaco City, Albay, open daily 6:00 AM – 7:00 PM.',
                ],
            ],
        ],
    ],
];

$page = $docs[$doc] ?? $docs['privacy'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'PowerMagz Gym') ?></title>
    <meta name="description" content="<?= e($intro ?? 'PowerMagz Gym legal information.') ?>">
    <meta name="theme-color" content="#060707">
    <link rel="icon" type="image/png" href="<?= url_e('/public/images/powermagz_gym.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url_e('/public/css/site.css') ?>">
</head>

<body>
    <header class="legal-top">
        <div class="wrap">
            <a href="<?= url_e('/') ?>" class="brand" aria-label="PowerMagz Gym — back to home">
                <img src="<?= url_e('/public/images/powermagz_gym.png') ?>" alt="" width="34" height="34">
                <span class="brand__name">POWERMAGZ<span>GYM</span></span>
            </a>
            <nav class="legal-top__links" aria-label="Legal">
                <a href="<?= url_e('/') ?>">Home</a>
                <a href="<?= url_e('/#pricing') ?>">Membership</a>
                <a href="<?= url_e('/#visit') ?>">Visit</a>
                <a href="<?= url_e('/login') ?>">Log in</a>
                <a href="<?= url_e('/register') ?>" class="btn btn--primary">Join now</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="legal-hero">
            <div class="wrap">
                <p class="eyebrow"><?= e($kicker ?? 'Legal') ?></p>
                <h1><?= e($heading ?? 'Legal') ?></h1>
                <p><?= e($intro ?? '') ?></p>
                <p class="legal-meta">Last updated <?= e($updated ?? '') ?></p>
            </div>
        </section>

        <section class="legal-body">
            <div class="wrap legal-layout">
                <nav class="legal-toc" aria-label="On this page">
                    <?php foreach ($page['sections'] as $section): ?>
                        <a href="#<?= e($section['id']) ?>"><?= e($section['title']) ?></a>
                    <?php endforeach; ?>
                </nav>

                <div class="legal-content">
                    <?php foreach ($page['sections'] as $section): ?>
                        <section id="<?= e($section['id']) ?>">
                            <h2><?= e($section['title']) ?></h2>
                            <?php foreach (($section['paras'] ?? []) as $para): ?>
                                <p><?= $para ?></p>
                            <?php endforeach; ?>
                            <?php if (!empty($section['items'])): ?>
                                <ul>
                                    <?php foreach ($section['items'] as $item): ?>
                                        <li><?= $item ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <?php if (!empty($section['note'])): ?>
                                <p class="legal-note"><?= $section['note'] ?></p>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="legal-foot">
        <div class="wrap">
            <p>&copy; <?= date('Y') ?> PowerMagz Gym. All rights reserved.</p>
            <p>
                <a href="<?= url_e('/privacy') ?>">Privacy Policy</a>
                &nbsp;&middot;&nbsp;
                <a href="<?= url_e('/terms') ?>">Terms &amp; Conditions</a>
                &nbsp;&middot;&nbsp;
                <a href="<?= url_e('/') ?>">Back to home</a>
            </p>
        </div>
    </footer>
</body>

</html>
