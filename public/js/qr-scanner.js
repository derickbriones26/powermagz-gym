(function () {
  const reader = document.getElementById('qr-reader');
  if (!reader) return;

  let done = false, scanner = null;
  const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
  const base = document.querySelector('meta[name="base"]')?.content
            || (window.location.pathname.split('/').slice(0, -2).join('/')) || '';

  const showSuccess = (rec) => {
    document.getElementById('scan-area').classList.add('hidden');
    const c = document.getElementById('success-card'); c.classList.remove('hidden');
    const t = new Date(rec.time_in);
    document.getElementById('success-text').textContent =
      `Timed in at ${t.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}. Enjoy!`;
  };

  const showAlready = (rec) => {
    document.getElementById('scan-area').classList.add('hidden');
    const c = document.getElementById('already-card'); c.classList.remove('hidden');
    const t = new Date(rec.time_in);
    document.getElementById('already-text').textContent =
      `You timed in today at ${t.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}.`;
    document.getElementById('time-out-btn').onclick = async () => {
      const fd = new FormData();
      fd.append('_csrf', csrf); fd.append('record_id', rec.id);
      const r = await fetch(`${base}/time-in/out`, { method: 'POST', body: fd });
      if (r.ok) window.location.href = `${base}/attendance`;
    };
  };

  const handleScan = async (text) => {
    if (done || !text.includes('PMGZ')) return;
    done = true;
    try { if (scanner) { await scanner.stop(); scanner.clear(); } } catch (_) {}
    const fd = new FormData();
    fd.append('_csrf', csrf); fd.append('decodedText', text);
    try {
      const res = await fetch(`${base}/time-in/scan`, { method: 'POST', body: fd });
      const data = await res.json();
      if (data.status === 'success') showSuccess(data.record);
      else if (data.status === 'already') showAlready(data.record);
      else done = false;
    } catch { done = false; }
  };

  const start = async () => {
    scanner = new Html5Qrcode('qr-reader');
    try {
      await scanner.start({ facingMode: 'environment' },
        { fps: 10, qrbox: { width: 220, height: 220 } }, handleScan, () => {});
    } catch (e) {
      document.getElementById('scan-area').classList.add('hidden');
      document.getElementById('camera-error').classList.remove('hidden');
    }
  };
  start();
})();