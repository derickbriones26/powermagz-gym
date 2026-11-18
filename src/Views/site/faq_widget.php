<?php
/**
 * Landing FAQ assistant — a tappable answer dock, bottom-right.
 *
 * The answers below are deterministic and must mirror the live pages:
 * pricing.php (tiers and amounts), visit_us.php (hours, address, contact),
 * and the member capabilities. No invented data — update here when the
 * gym's prices, hours or policies change.
 */
$faqs = [
    [
        'id'       => 'prices',
        'label'    => 'Prices',
        'keywords' => 'price|prices|cost|costs|fee|fees|rate|rates|how much|much|membership|plan|plans|tier|tiers|500|1000|2000|peso|monthly|per month|digital|standard|premium',
        'answer'   => '<p><strong>Digital — ₱500 / month.</strong> A digital-only plan for existing members: the digital workout system, QR time in and out, your attendance calendar and the AI workout recommender.</p>'
            . '<p><strong>Standard — ₱1,000 to start.</strong> ₱500 membership fee plus ₱500 monthly, with full gym access, group class booking and every payment method.</p>'
            . '<p><strong>Premium — ₱2,000 / month.</strong> Everything in Standard, plus personal coaching, nutrition guidance, priority class booking and the 24/7 AI coach.</p>'
            . '<p><a href="#pricing">See the full comparison</a> or <a href="' . url_e('/register') . '">create your account</a>.</p>',
    ],
    [
        'id'       => 'qr',
        'label'    => 'QR check-in',
        'keywords' => 'qr|check in|check-in|checkin|scan|door|attendance|time in|time out|entry|exit|session|log',
        'answer'   => '<p>Every tier includes <strong>QR time in and time out</strong>. Scan at the door and the session is logged automatically — no paper, no queue.</p>'
            . '<p>Your visits appear in your member dashboard, and coaches use them to keep your plan honest. <a href="#core">See how the system works</a>.</p>',
    ],
    [
        'id'       => 'hours',
        'label'    => 'Hours &amp; location',
        'keywords' => 'hours|hour|open|opening|close|closing|time|schedule|location|located|address|where|map|directions|find you|matagbac|tabaco|albay|zone 4|visit|today|daily',
        'answer'   => '<p><strong>Open daily, 6:00 AM to 7:00 PM.</strong> Walk in any day to meet the coaches and see the floor.</p>'
            . '<p>Zone 4, Matagbac, Tabaco City, Albay. <a href="#visit">Open the map</a>.</p>',
    ],
    [
        'id'       => 'payments',
        'label'    => 'Payments',
        'keywords' => 'pay|payment|payments|gcash|cash|bank|bank transfer|transfer|card|receipt|receipts|balance|due|method|methods|bill|invoice|renew|renewing|money',
        'answer'   => '<p>The gym accepts <strong>GCash, cash, bank transfer or card</strong> — the methods available to you appear at checkout in your member dashboard.</p>'
            . '<p>Your balances, receipts and payment history stay visible from day one.</p>',
    ],
    [
        'id'       => 'join',
        'label'    => 'How to join',
        'keywords' => 'join|joining|register|signup|sign up|account|start|first|enroll|enrollment|new member|become a member|steps|trial|walk in|walk-in|walkin|try',
        'answer'   => '<p>Three steps: <strong>create your account</strong>, <strong>pick your membership</strong>, then <strong>scan in and train</strong>. Your QR pass and training plan go live as soon as payment clears.</p>'
            . '<p>Not ready yet? Walk in during opening hours, or call and the gym will walk you through it. <a href="' . url_e('/register') . '">Start your account</a>.</p>',
    ],
    [
        'id'       => 'coaching',
        'label'    => 'Coaching',
        'keywords' => 'coach|coaches|trainer|trainers|personal coaching|personal training|program|class|classes|group class|nutrition|guidance|adjust|workout plan|instructor|partner',
        'answer'   => '<p>Certified coaches build your training and adjust it as you get stronger. <strong>Personal coaching and nutrition guidance are part of Premium</strong>; group class booking is included in Standard and Premium.</p>'
            . '<p>Premium members also get the 24/7 AI coach for schedule, training, nutrition and recovery questions. <a href="#pricing">Compare tiers</a>.</p>',
    ],
];
?>
<div class="faq-dock" id="faqDock" hidden>
    <section class="faq-panel" id="faqPanel" aria-label="PowerMagz FAQ assistant" hidden>
        <header class="faq-panel__head">
            <div>
                <p class="faq-panel__eyebrow">PowerMagz FAQ</p>
                <p class="faq-panel__title">Instant answers</p>
            </div>
            <button type="button" class="faq-close" data-faq-close aria-label="Close FAQ">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </header>

        <div class="faq-panel__body">
            <p class="faq-intro">Tap a question — or type your own. Answers match the gym&rsquo;s real prices, hours and policies.</p>

            <div class="faq-chips" role="group" aria-label="Common questions">
                <?php foreach ($faqs as $faq): ?>
                    <button type="button" class="faq-chip" data-faq="<?= e($faq['id']) ?>" aria-pressed="false"><?= $faq['label'] ?></button>
                <?php endforeach; ?>
            </div>

            <div class="faq-answer" id="faqAnswer" aria-live="polite">
                <p class="faq-answer__hint">Pick a question above to see the answer here.</p>
            </div>

            <form class="faq-ask" id="faqForm" autocomplete="off">
                <label class="sr-only" for="faqInput">Ask a question</label>
                <input id="faqInput" name="q" type="text" maxlength="120" placeholder="Ask about prices, hours, joining&hellip;">
                <button type="submit" class="faq-ask__send" aria-label="Send question">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                    </svg>
                </button>
            </form>

            <p class="faq-foot">
                Still stuck? <a href="tel:+639618041523">Call 0961 804 1523</a>
                <span aria-hidden="true">&middot;</span>
                <a href="mailto:powermagzgym@gmail.com">Email the gym</a>
            </p>
        </div>

        <div class="faq-sources" hidden>
            <?php foreach ($faqs as $faq): ?>
                <div data-faq-source="<?= e($faq['id']) ?>" data-keywords="<?= e($faq['keywords']) ?>"><?= $faq['answer'] ?></div>
            <?php endforeach; ?>
        </div>
    </section>

    <button type="button" class="faq-launcher" id="faqLauncher" aria-expanded="false" aria-controls="faqPanel">
        <span class="faq-launcher__icon" aria-hidden="true">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
            </svg>
        </span>
        <span class="faq-launcher__label">FAQ AI</span>
        <span class="faq-launcher__dot" aria-hidden="true"></span>
    </button>
</div>
