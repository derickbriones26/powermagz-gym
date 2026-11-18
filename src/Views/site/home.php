<?php require BASE_PATH . '/src/Views/layouts/site_header.php'; ?>

<!-- 1. Hero — the gym, the location and the one action -->
<?php require BASE_PATH . '/src/Views/site/hero.php'; ?>

<!-- 1b. Capability ticker — the real system, kept short -->
<div class="ticker" role="group" aria-label="What every membership includes">
    <div class="ticker__track">
        <div class="ticker__group">
            <span>QR check-in &amp; check-out</span>
            <span>Coach-led strength training</span>
            <span>GCash, cash, bank &amp; card</span>
            <span>Progress you can see</span>
            <span>One shared member account</span>
            <span>Open daily &middot; 6 AM &ndash; 7 PM</span>
        </div>
        <div class="ticker__group" aria-hidden="true">
            <span>QR check-in &amp; check-out</span>
            <span>Coach-led strength training</span>
            <span>GCash, cash, bank &amp; card</span>
            <span>Progress you can see</span>
            <span>One shared member account</span>
            <span>Open daily &middot; 6 AM &ndash; 7 PM</span>
        </div>
    </div>
</div>

<!-- 2. Training — the three core pillars -->
<?php require BASE_PATH . '/src/Views/site/features.php'; ?>

<!-- 2b. System core — interactive 3D object with the real member capabilities -->
<?php require BASE_PATH . '/src/Views/site/core.php'; ?>

<!-- 3. Services — what the gym offers, from the client's service list -->
<?php require BASE_PATH . '/src/Views/site/services.php'; ?>

<!-- 3. Inside — story, vision and mission -->
<?php require BASE_PATH . '/src/Views/site/about.php'; ?>

<!-- 4. Membership — three clear tiers -->
<?php require BASE_PATH . '/src/Views/site/pricing.php'; ?>

<!-- 5. Visit — address, hours, contact and map -->
<?php require BASE_PATH . '/src/Views/site/visit_us.php'; ?>

<!-- 6. Close — one action -->
<?php require BASE_PATH . '/src/Views/site/final_cta.php'; ?>

<?php require BASE_PATH . '/src/Views/layouts/site_footer.php'; ?>
