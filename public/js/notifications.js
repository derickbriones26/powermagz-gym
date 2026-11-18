// PowerMagz — Notification bell (2026 UI - Persistent Server State)
(function () {
  const bell = document.getElementById('notif-bell');
  if (!bell) return;

  const panel = document.getElementById('notif-panel');
  const list  = document.getElementById('notif-list');
  const dot   = document.getElementById('notif-dot');
  const base  = document.querySelector('meta[name="base"]')?.content || '';
  const api   = base + '/api/notifications';
  const readApi = base + '/api/notifications/read';
  
  // Get CSRF token from the meta tag in portal_head.php
  const csrf  = document.querySelector('meta[name="csrf"]')?.content || '';

  let items = [];

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
  }

  const toneMeta = {
    warn: {
      bg: 'bg-amber-500/10', 
      icon: '<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>'
    },
    success: {
      bg: 'bg-green-500/10', 
      icon: '<svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    },
    info: {
      bg: 'bg-[#00A6A6]/10', 
      icon: '<svg class="w-4 h-4 text-[#00A6A6]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
    }
  };

  function render() {
    // 🔐 Use the server's unread_count for the badge
    if (window.unreadCount > 0) {
      dot.classList.remove('hidden');
    } else {
      dot.classList.add('hidden');
    }

    if (items.length === 0) {
      list.innerHTML = `
        <div class="p-8 text-center">
          <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white/5 text-gray-500">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <p class="mt-3 text-sm font-semibold text-gray-400">You're all caught up!</p>
        </div>`;
      return;
    }

    list.innerHTML = items.map((n, i) => {
      const meta = toneMeta[n.tone] || toneMeta.info;
      const title = escapeHtml(n.title || n.action || 'Notification');
      const desc = escapeHtml(n.description || n.desc || n.notes || '');
      return `
        <div class="flex items-start gap-3 p-4 ${i > 0 ? 'border-t border-white/10' : ''} transition-colors hover:bg-white/[0.02]">
          <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full ${meta.bg}">
            ${meta.icon}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-sm font-bold leading-snug break-words" style="color: #ffffff !important;">${title}</div>
            <div class="mt-1 text-xs leading-relaxed break-words" style="color: #9ca3af !important;">${desc}</div>
          </div>
        </div>`;
    }).join('');
  }

  async function load() {
    try {
      const res  = await fetch(api, { credentials: 'same-origin' });
      const data = await res.json();
      items = Array.isArray(data.items) ? data.items : [];
      window.unreadCount = data.unread_count || 0;
    } catch (e) {
      items = [];
      window.unreadCount = 0;
    }
    render();
  }

  window.toggleNotif = function () {
    const wasHidden = panel.classList.contains('hidden');
    if (wasHidden) {
      panel.classList.remove('hidden');
    } else {
      panel.classList.add('hidden');
    }
  };

  // 🔐 Call the server to mark all as read
  window.markAllRead = async function () {
    try {
      const res = await fetch(readApi, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-CSRF-Token': csrf
        },
        body: '_csrf=' + encodeURIComponent(csrf),
        credentials: 'same-origin'
      });

      if (res.ok) {
        window.unreadCount = 0;
        dot.classList.add('hidden');
        list.innerHTML = `
          <div class="p-8 text-center" style="animation: fade-in 0.3s ease-out;">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-[#00A6A6]/10 text-[#00A6A6]">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <p class="mt-3 text-sm font-bold" style="color: #ffffff !important;">All caught up!</p>
            <p class="mt-1 text-xs" style="color: #9ca3af !important;">No new notifications.</p>
          </div>`;
      }
    } catch (e) {
      console.error('Failed to mark notifications as read:', e);
    }
  };

  document.addEventListener('click', (e) => {
    if (!bell.contains(e.target) && !panel.contains(e.target)) {
      panel.classList.add('hidden');
    }
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') panel.classList.add('hidden');
  });

  load();
  setInterval(() => {
    if (panel.classList.contains('hidden')) load();
  }, 60000);
})();