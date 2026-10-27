</main>
</div> <!-- End main-content -->

<!-- Modern Sign Out Modal -->
<div id="signOutModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4">
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-[#111] border border-white/10 shadow-2xl modal-animate">
        <div class="p-8 text-center">
            <!-- Red Glow Icon -->
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10 text-red-500 shadow-[0_0_20px_rgba(239,68,68,0.2)]">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </div>

            <h3 class="mt-6 font-heading text-3xl tracking-wide text-white">SIGN OUT?</h3>
            <p class="mt-2 text-sm text-gray-400 leading-relaxed">
                You're about to sign out of your <strong class="text-white"><?= e($brandSub) ?></strong> account. Any unsaved changes will be lost.
            </p>
        </div>

        <!-- Buttons -->
        <div class="grid grid-cols-2 gap-3 border-t border-white/5 p-4 bg-[#0a0a0a]">
            <button type="button" onclick="closeSignOutModal()" class="rounded-xl border border-white/10 bg-transparent py-3 text-sm font-bold text-white transition-all hover:bg-white/5">
                Cancel
            </button>
            <form method="post" action="<?= url_e('/logout') ?>" class="contents">
                <?= csrf_field() ?>
                <button type="submit" class="w-full rounded-xl bg-red-500 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(239,68,68,0.3)] transition-all hover:bg-red-600 hover:shadow-[0_0_25px_rgba(239,68,68,0.5)]">
                    Sign Out
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Global Scripts -->
<script src="<?= url_e('/public/js/app.js') ?>"></script>
<script src="<?= url_e('/public/js/notifications.js') ?>"></script>
<script src="<?= url_e('/public/js/a11y.js') ?>"></script>

<!-- Offline Tracker & Service Worker -->
<script src="<?= url_e('/public/js/offline.js') ?>"></script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?= url_e('/public/sw.js') ?>').then(registration => {
                console.log('ServiceWorker registered:', registration.scope);
            }).catch(err => {
                console.log('ServiceWorker registration failed:', err);
            });
        });
    }
</script>

<script>
    // ==========================================
    // 1. Mobile Sidebar Toggle Logic
    // ==========================================
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');

    window.toggleSidebarMobile = function() {
        const isOpen = !sidebar.classList.contains('-translate-x-full');

        if (isOpen) {
            // Close it
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        } else {
            // Open it
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    };

    // ==========================================
    // 2. Sign Out Modal Logic
    // ==========================================
    window.openSignOutModal = function() {
        const m = document.getElementById('signOutModal');
        m.classList.replace('hidden', 'flex');
    };

    window.closeSignOutModal = function() {
        const m = document.getElementById('signOutModal');
        m.classList.replace('flex', 'hidden');
    };

    // Close modal on backdrop click
    document.getElementById('signOutModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSignOutModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSignOutModal();
        }
    });
</script>
</body>

</html>