<?php

/**
 * Member — Occupancy Widget (2026 Dark UI - Mobile Responsive)
 */
?>

<div id="occupancy-widget" class="w-full max-w-full overflow-hidden rounded-3xl border border-white/5 bg-[#111] p-4 sm:p-6 lg:p-8 shadow-sm transition-all duration-300 hover:shadow-[0_10px_30px_-10px_rgba(0,166,166,0.2)]">

  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 w-full">
    <div>
      <h3 class="font-heading text-xl sm:text-2xl tracking-wide text-white">LIVE GYM OCCUPANCY</h3>
      <p class="mt-1 text-[10px] sm:text-xs text-gray-400">How many members are in the gym right now</p>
    </div>

    <!-- Live Badge -->
    <div class="flex items-center gap-2 self-start sm:self-auto rounded-full bg-red-500/10 border border-red-500/20 px-3 py-1.5">
      <span class="relative flex h-2.5 w-2.5">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-60"></span>
        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-red-500"></span>
      </span>
      <span class="text-[10px] font-bold uppercase tracking-wider text-red-500">Live</span>
    </div>
  </div>

  <!-- Main display -->
  <!-- 🔐 Added min-w-0 to allow grid items to shrink -->
  <div class="mt-4 sm:mt-6 grid gap-4 sm:gap-6 lg:grid-cols-[1fr_2fr] w-full min-w-0">

    <!-- Big count card (Compact on Mobile) -->
    <!-- 🔐 Added min-w-0 w-full to contain the content -->
    <div class="flex flex-col justify-center rounded-2xl bg-gradient-to-br from-[#0a0a0a] via-[#050505] to-[#00A6A6]/20 border border-white/5 p-4 sm:p-6 shadow-inner relative overflow-hidden w-full min-w-0">
      <!-- Glow orb -->
      <div class="absolute -right-10 -top-10 h-32 w-32 rounded-full bg-[#00A6A6]/20 blur-2xl pointer-events-none"></div>

      <div class="relative w-full min-w-0">
        <div class="text-[9px] sm:text-[10px] font-bold uppercase tracking-[0.2em] sm:tracking-[0.3em] text-gray-500">
          Currently Inside
        </div>

        <!-- SKELETON LOADER (Visible initially) -->
        <div id="occ-skeleton" class="animate-pulse mt-2">
          <div class="h-10 sm:h-14 w-16 sm:w-24 rounded-lg bg-white/10"></div>
          <div class="mt-2 sm:mt-4 h-1.5 w-full rounded-full bg-white/5"></div>
          <div class="mt-2 h-2 w-1/2 rounded-full bg-white/5"></div>
        </div>

        <!-- REAL CONTENT (Hidden initially) -->
        <div id="occ-content" class="hidden w-full min-w-0">
          <div class="mt-1 flex flex-wrap items-baseline gap-1.5">
            <div id="occ-count" class="font-heading text-4xl sm:text-5xl lg:text-6xl leading-none text-[#00A6A6] drop-shadow-[0_0_15px_rgba(0,166,166,0.3)]">0</div>
            <div class="text-xs sm:text-sm text-gray-500">/ <span id="occ-capacity">50</span></div>
          </div>

          <div class="mt-1.5 text-[10px] sm:text-xs text-gray-400">
            Capacity: <span id="occ-capacity-label" class="text-white font-bold">50</span> members
          </div>

          <!-- Progress bar -->
          <div class="mt-2 sm:mt-4 h-1.5 sm:h-2 w-full overflow-hidden rounded-full bg-white/10">
            <div id="occ-bar" class="h-full rounded-full bg-gradient-to-r from-[#00A6A6] to-red-500 transition-all duration-700 ease-out"
              style="width: 0%;"></div>
          </div>

          <div id="occ-status" class="mt-1.5 text-[10px] sm:text-xs font-semibold text-gray-400">—</div>
        </div>
      </div>
    </div>

    <!-- Hourly chart -->
    <!-- 🔐 Added min-w-0 overflow-hidden so Chart.js can't stretch the parent -->
    <div class="relative h-44 sm:h-56 lg:h-auto lg:min-h-[220px] rounded-2xl border border-white/5 bg-[#0a0a0a] p-3 sm:p-4 w-full min-w-0 overflow-hidden">

      <!-- CHART SKELETON -->
      <div id="chart-skeleton" class="absolute inset-3 sm:inset-4 animate-pulse flex flex-col justify-end gap-2">
        <div class="flex items-end justify-between h-full gap-1.5 sm:gap-2">
          <div class="w-full bg-white/5 rounded-t-sm h-[20%]"></div>
          <div class="w-full bg-white/5 rounded-t-sm h-[40%]"></div>
          <div class="w-full bg-white/5 rounded-t-sm h-[70%]"></div>
          <div class="w-full bg-white/5 rounded-t-sm h-[50%]"></div>
          <div class="w-full bg-white/5 rounded-t-sm h-[90%]"></div>
          <div class="w-full bg-white/5 rounded-t-sm h-[30%]"></div>
          <div class="w-full bg-white/5 rounded-t-sm h-[60%]"></div>
        </div>
      </div>

      <!-- REAL CHART -->
      <!-- 🔐 Added max-w-full to the canvas -->
      <canvas id="occ-chart" class="opacity-0 transition-opacity duration-500 max-w-full"></canvas>
    </div>
  </div>

  <!-- Last updated & Refresh -->
  <div class="mt-4 sm:mt-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-0 border-t border-white/5 pt-4 w-full">
    <div class="flex items-center gap-2 text-[10px] sm:text-xs text-gray-500">
      <span id="occ-dot" class="h-1.5 w-1.5 rounded-full bg-gray-500 animate-pulse"></span>
      <span id="occ-updated">Connecting to live feed…</span>
    </div>

    <!-- Modern Refresh Button -->
    <button type="button" onclick="refreshOccupancy(true)"
      class="group flex w-full sm:w-auto items-center justify-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-[10px] sm:text-xs font-bold text-gray-300 transition-all duration-300 hover:border-[#00A6A6] hover:bg-[#00A6A6]/10 hover:text-[#00A6A6]">
      <svg class="h-3.5 w-3.5 sm:h-4 sm:w-4 transition-transform duration-500 group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
      </svg>
      Refresh Data
    </button>
  </div>
</div>

<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
  (function() {
    var ENDPOINT = '<?= url_e('/api/occupancy') ?>';
    var REFRESH_MS = 30000;
    var occChart = null;
    var isFirstLoad = true;

    function revealContent() {
      if (!isFirstLoad) return;

      var skeleton = document.getElementById('occ-skeleton');
      if (skeleton) skeleton.classList.add('hidden');

      var content = document.getElementById('occ-content');
      if (content) content.classList.remove('hidden');

      var chartSkeleton = document.getElementById('chart-skeleton');
      if (chartSkeleton) chartSkeleton.classList.add('hidden');

      var chartCanvas = document.getElementById('occ-chart');
      if (chartCanvas) {
        chartCanvas.classList.remove('opacity-0');
        chartCanvas.classList.add('opacity-100');
      }

      isFirstLoad = false;
    }

    // Safety Timeout
    setTimeout(revealContent, 3000);

    function initChart(hours, checkins) {
      var canvas = document.getElementById('occ-chart');
      if (!canvas || typeof Chart === 'undefined') return;

      var ctx = canvas.getContext('2d');
      var gradient = ctx.createLinearGradient(0, 0, 0, 220);
      gradient.addColorStop(0, '#00A6A6');
      gradient.addColorStop(1, 'rgba(0,166,166,0.1)');

      if (occChart) occChart.destroy();

      occChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: hours,
          datasets: [{
            data: checkins,
            backgroundColor: gradient,
            hoverBackgroundColor: '#00A6A6',
            borderRadius: 4,
            borderSkipped: false,
            maxBarThickness: 24
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          animation: {
            duration: 800,
            easing: 'easeOutQuart'
          },
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              backgroundColor: '#111',
              titleColor: '#00A6A6',
              bodyColor: '#fff',
              borderColor: 'rgba(255,255,255,0.1)',
              borderWidth: 1,
              padding: 10,
              cornerRadius: 8,
              displayColors: false,
              callbacks: {
                title: function(items) {
                  return 'At ' + items[0].label;
                },
                label: function(item) {
                  return item.raw + ' check-in' + (item.raw !== 1 ? 's' : '');
                }
              }
            }
          },
          scales: {
            x: {
              grid: {
                display: false
              },
              ticks: {
                color: '#6b7280',
                font: {
                  size: 9
                },
                maxRotation: 0,
                autoSkip: true,
                maxTicksLimit: 6
              }
            },
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(255,255,255,0.05)',
                drawBorder: false
              },
              ticks: {
                color: '#6b7280',
                font: {
                  size: 9
                },
                precision: 0,
                maxTicksLimit: 4
              }
            }
          }
        }
      });
    }

    function refreshOccupancy(manual) {
      var dot = document.getElementById('occ-dot');
      var updated = document.getElementById('occ-updated');

      if (!isFirstLoad) {
        if (dot) dot.className = 'h-1.5 w-1.5 rounded-full bg-amber-500 animate-pulse';
        if (updated) updated.textContent = 'Updating...';
      }

      fetch(ENDPOINT, {
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json'
          }
        })
        .then(function(r) {
          if (!r.ok) throw new Error('HTTP ' + r.status);
          return r.json();
        })
        .then(function(data) {
          var inGym = Number(data.in || 0);
          var capacity = Number(data.capacity || 50);
          var hourly = Array.isArray(data.hourly) ? data.hourly : [];
          var hours = [];
          var counts = [];

          for (var i = 0; i < hourly.length; i++) {
            hours.push(hourly[i].hour);
            counts.push(hourly[i].checkins);
          }

          var countEl = document.getElementById('occ-count');
          if (countEl) {
            var prev = Number(countEl.textContent || 0);
            countEl.textContent = inGym;
            if (prev !== inGym && !isFirstLoad) {
              countEl.style.transform = 'scale(1.15)';
              setTimeout(function() {
                countEl.style.transform = '';
              }, 300);
            }
          }

          var capEl = document.getElementById('occ-capacity');
          if (capEl) capEl.textContent = capacity;

          var capLabel = document.getElementById('occ-capacity-label');
          if (capLabel) capLabel.textContent = capacity;

          var pct = Math.min(100, Math.round((inGym / capacity) * 100));
          var bar = document.getElementById('occ-bar');
          if (bar) bar.style.width = pct + '%';

          var status = document.getElementById('occ-status');
          if (status) {
            if (pct < 30) status.textContent = 'Plenty of space available';
            else if (pct < 70) status.textContent = 'Moderately busy';
            else if (pct < 90) status.textContent = 'Getting crowded';
            else status.textContent = 'Almost full — arrive early!';
          }

          revealContent();

          if (hours.length) {
            try {
              initChart(hours, counts);
            } catch (e) {
              console.error('Chart error:', e);
            }
          }

          if (updated) {
            updated.textContent = 'Updated ' + new Date().toLocaleTimeString([], {
              hour: '2-digit',
              minute: '2-digit',
              second: '2-digit'
            });
          }
          if (dot) dot.className = 'h-1.5 w-1.5 rounded-full bg-green-500';
        })
        .catch(function(err) {
          console.error('Occupancy fetch error:', err);
          if (updated) updated.textContent = 'Live data unavailable';
          if (dot) dot.className = 'h-1.5 w-1.5 rounded-full bg-red-500';
          revealContent();
        });
    }

    refreshOccupancy();
    setInterval(function() {
      if (!document.hidden) refreshOccupancy();
    }, REFRESH_MS);

    document.addEventListener('visibilitychange', function() {
      if (!document.hidden) refreshOccupancy();
    });

    window.refreshOccupancy = refreshOccupancy;
  })();
</script>