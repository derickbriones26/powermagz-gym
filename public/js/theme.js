// PowerMagz — Dark mode toggle
// Persists choice in localStorage. Falls back to OS preference.
(function () {
  const KEY = 'pmgz-theme';

  function getInitial() {
    const stored = localStorage.getItem(KEY);
    if (stored === 'dark' || stored === 'light') return stored;
    return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark' : 'light';
  }

  function apply(theme) {
    document.documentElement.classList.toggle('dark', theme === 'dark');
  }

  function syncIcons() {
    const isDark = document.documentElement.classList.contains('dark');
    document.querySelectorAll('[data-theme-icon]').forEach(el => {
      el.textContent = isDark ? '☀️' : '🌙';
    });
  }

  // Apply immediately
  apply(getInitial());

  // Public toggle
  window.toggleTheme = function () {
    const isDark = document.documentElement.classList.contains('dark');
    const next = isDark ? 'light' : 'dark';
    apply(next);
    localStorage.setItem(KEY, next);
    syncIcons();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', syncIcons);
  } else {
    syncIcons();
  }
})();