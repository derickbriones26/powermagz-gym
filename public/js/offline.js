/**
 * PowerMagz — offline connection banner.
 * Framework-free so it works on every page (including the public site,
 * which does not load Tailwind).
 */
(function() {
    'use strict';

    var style = document.createElement('style');
    style.textContent =
        '.pmgz-offline{position:fixed;left:50%;bottom:24px;transform:translateX(-50%);z-index:99999;' +
        'display:flex;align-items:center;gap:10px;max-width:calc(100% - 32px);padding:12px 20px;' +
        'border-radius:999px;background:#dc2626;color:#fff;font:700 13px/1.4 system-ui,sans-serif;' +
        'box-shadow:0 18px 40px -18px rgba(0,0,0,.8);}' +
        '.pmgz-offline[hidden]{display:none;}' +
        '.pmgz-offline svg{width:18px;height:18px;flex-shrink:0;}' +
        '@media (prefers-reduced-motion:no-preference){.pmgz-offline{animation:pmgz-drop .25s ease-out;}}' +
        '@keyframes pmgz-drop{from{opacity:0;transform:translate(-50%,8px);}to{opacity:1;transform:translate(-50%,0);}}';
    document.head.appendChild(style);

    var banner = document.createElement('div');
    banner.id = 'offline-banner';
    banner.className = 'pmgz-offline';
    banner.setAttribute('role', 'status');
    banner.setAttribute('aria-live', 'polite');
    banner.hidden = true;
    banner.innerHTML =
        '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">' +
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" ' +
        'd="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />' +
        '</svg><span>You are offline. Please check your connection.</span>';

    function updateStatus() {
        banner.hidden = navigator.onLine;
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.body.appendChild(banner);
        window.addEventListener('online', updateStatus);
        window.addEventListener('offline', updateStatus);
        updateStatus();
    });
})();
