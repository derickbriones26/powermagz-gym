/**
 * PowerMagz — Offline Connection Tracker
 * Shows a banner if the user loses internet while navigating the app.
 */
(function() {
    'use strict';

    // Create the banner element
    const banner = document.createElement('div');
    banner.id = 'offline-banner';
    banner.className = 'fixed bottom-6 left-1/2 -translate-x-1/2 z-[99999] hidden items-center gap-3 rounded-full bg-red-500 px-6 py-3 text-sm font-bold text-white shadow-2xl transition-all duration-300';
    banner.innerHTML = `
        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
        </svg>
        You are offline. Please check your connection.
    `;

    document.addEventListener('DOMContentLoaded', () => {
        document.body.appendChild(banner);

        function updateStatus() {
            if (navigator.onLine) {
                banner.classList.add('hidden');
                banner.classList.remove('flex');
            } else {
                banner.classList.remove('hidden');
                banner.classList.add('flex');
            }
        }

        // Listen for online/offline events
        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);

        // Check immediately on load
        updateStatus();
    });
})();