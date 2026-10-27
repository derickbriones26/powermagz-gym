// PowerMagz — Notification bell (topbar)
(function () {
  const bell = document.getElementById('notif-bell');
  if (!bell) return;

  const panel = document.getElementById('notif-panel');
  const list  = document.getElementById('notif-list');
  const dot   = document.getElementById('notif-dot');
  const KEY   = 'pmgz-notif-seen';
  const base  = document.querySelector('meta[name="base"]')?.content || '';
  const api   = base + '/api/notifications';

  let items = [];

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    })[c]);
  }

  function render() {
    const seen = Number(localStorage.getItem(KEY) || 0);

    if (items.length > seen) dot.classList.remove('hidden');
    else                     dot.classList.add('hidden');

    if (items.length === 0) {
      list.innerHTML = `
        <div class="p-6 text-center">
          <div class="mx-auto text-4xl text-gray-300">✓</div>
          <p class="mt-2 text-sm text-gray-400">You're all caught up!</p>
        </div>`;
      return;
    }

    const tone = {
      warn:    '⚠️',
      success: '✅',
      info:    'ℹ️',
    };

    list.innerHTML = items.map((n, i) => `
      <div class="flex gap-3 p-4 ${i > 0 ? 'border-t border-gray-100' : ''}">
        <div class="mt-0.5 flex-shrink-0 text-base">${tone[n.tone] || tone.info}</div>
        <div>
          <div class="text-sm font-semibold text-black">${escapeHtml(n.title)}</div>
          <div class="mt-0.5 text-xs text-gray-500">${escapeHtml(n.desc)}</div>
        </div>
      </div>`).join('');
  }

  async function load() {
    try {
      const res  = await fetch(api, { credentials: 'same-origin' });
      const data = await res.json();
      items = Array.isArray(data.items) ? data.items : [];
    } catch (e) {
      items = [];
    }
    render();
  }

  window.toggleNotif = function () {
    const wasHidden = panel.classList.contains('hidden');
    if (wasHidden) {
      panel.classList.remove('hidden');
      localStorage.setItem(KEY, String(items.length));
      dot.classList.add('hidden');
    } else {
      panel.classList.add('hidden');
    }
  };

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!bell.contains(e.target) && !panel.contains(e.target)) {
      panel.classList.add('hidden');
    }
  });

  // Close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') panel.classList.add('hidden');
  });

  load();
  setInterval(load, 60000); // refresh every 60s
})();