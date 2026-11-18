<?php

/**
 * Admin — Group Classes tab.
 * CRUD for the weekly recurring class schedule.
 */

$classes = $data['classes'] ?? [];
$counts  = $data['classBookingCounts'] ?? [];

$dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
$tierLabels = ['digital' => 'Digital', 'standard' => 'Standard', 'premium' => 'Premium'];
?>

<div class="space-y-6">

    <!-- Header -->
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h1 class="font-heading text-3xl tracking-wide text-white sm:text-4xl">GROUP CLASSES</h1>
            <p class="mt-1 text-sm text-gray-400">Weekly schedule members book from. Capacity and tier rules apply automatically.</p>
        </div>
        <button type="button" onclick="openClassModal()"
            class="tap-effect inline-flex h-11 items-center justify-center gap-2 rounded-full bg-[#00A6A6] px-5 text-xs font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.35)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c]">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            New class
        </button>
    </div>

    <?php if (!$classes): ?>
        <div class="rounded-2xl border border-dashed border-white/10 bg-[#111] p-12 text-center">
            <h2 class="font-heading text-2xl tracking-wide text-white">NO CLASSES YET</h2>
            <p class="mx-auto mt-2 max-w-sm text-sm text-gray-400">Add the first class so members can start booking.</p>
        </div>
    <?php else: ?>
        <div class="overflow-hidden rounded-2xl border border-white/5 bg-[#111]">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-white/5 text-[10px] uppercase tracking-widest text-gray-500">
                            <th class="px-5 py-4 font-bold">Class</th>
                            <th class="px-5 py-4 font-bold">Schedule</th>
                            <th class="px-5 py-4 font-bold">Coach</th>
                            <th class="px-5 py-4 font-bold">Tier</th>
                            <th class="px-5 py-4 font-bold">Capacity</th>
                            <th class="px-5 py-4 font-bold">Bookings</th>
                            <th class="px-5 py-4 text-right font-bold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach ($classes as $class): ?>
                            <?php
                            $day    = (int)$class['day_of_week'];
                            $start  = date('g:i A', strtotime($class['start_time']));
                            $end    = date('g:i A', strtotime($class['start_time']) + ((int)$class['duration_min'] * 60));
                            $booked = (int)($counts[$class['id']] ?? 0);
                            $active = ($class['status'] ?? '') === 'active';
                            ?>
                            <tr class="group transition-colors hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <div class="font-bold text-white"><?= e($class['name']) ?></div>
                                    <?php if (!empty($class['description'])): ?>
                                        <div class="mt-0.5 max-w-xs truncate text-xs text-gray-500" title="<?= e($class['description']) ?>">
                                            <?= e($class['description']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-gray-300">
                                    <div class="text-xs font-bold text-white"><?= e($dayNames[$day] ?? '—') ?></div>
                                    <div class="text-[11px] text-gray-500"><?= e($start) ?>–<?= e($end) ?></div>
                                </td>
                                <td class="px-5 py-4 text-gray-300"><?= e($class['coach_name'] ?: '—') ?></td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-gray-300">
                                        <?= e($tierLabels[$class['tier_min']] ?? $class['tier_min']) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-gray-300"><?= (int)$class['capacity'] ?></td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-white"><?= $booked ?></span>
                                        <span class="text-[10px] uppercase tracking-widest text-gray-500">upcoming</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider <?= $active ? 'bg-[#00A6A6]/10 text-[#00A6A6]' : 'bg-white/5 text-gray-500' ?>">
                                            <?= $active ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <button type="button"
                                            onclick='openClassModal(<?= json_encode($class, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)'
                                            class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-300 transition-colors hover:border-[#00A6A6]/50 hover:text-white">
                                            Edit
                                        </button>
                                        <form method="post" action="<?= url_e('/admin-dashboard/classes/delete') ?>"
                                            onsubmit="return confirm('Delete this class and all its bookings?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e($class['id']) ?>">
                                            <button type="submit"
                                                class="rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 transition-colors hover:border-red-500/50 hover:text-red-300">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Class modal -->
<div id="classModal" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 backdrop-blur-md">
    <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-3xl border border-white/10 bg-[#111] shadow-2xl">
        <div class="flex items-start justify-between border-b border-white/5 bg-gradient-to-br from-[#0a0a0a] to-[#00A6A6]/20 px-6 py-5">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-gray-400">Schedule</div>
                <h2 id="cm-title" class="mt-1 font-heading text-2xl tracking-wide text-white">NEW CLASS</h2>
            </div>
            <button type="button" onclick="closeClassModal()"
                class="rounded-full bg-white/5 p-2 text-gray-400 transition-colors hover:bg-white/10 hover:text-white" aria-label="Close">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <form id="classForm" method="post" action="<?= url_e('/admin-dashboard/classes/save') ?>" class="space-y-4 p-6">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="cm-id">

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Class name *</span>
                <input name="name" id="cm-name" required maxlength="120"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
            </label>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Description</span>
                <textarea name="description" id="cm-description" rows="2" maxlength="255"
                    class="w-full resize-none rounded-xl border border-white/10 bg-[#0a0a0a] p-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20"></textarea>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Coach</span>
                    <input name="coach_name" id="cm-coach" maxlength="120"
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Weekday *</span>
                    <select name="day_of_week" id="cm-day" required
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <?php foreach ($dayNames as $i => $dayName): ?>
                            <option value="<?= $i ?>"><?= e($dayName) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Start time *</span>
                    <input type="time" name="start_time" id="cm-start" required
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Duration (min) *</span>
                    <input type="number" name="duration_min" id="cm-duration" min="15" max="180" value="60" required
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Capacity *</span>
                    <input type="number" name="capacity" id="cm-capacity" min="1" max="100" value="20" required
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                </label>

                <label class="block">
                    <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Minimum tier *</span>
                    <select name="tier_min" id="cm-tier" required
                        class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                        <option value="standard">Standard &amp; Premium</option>
                        <option value="premium">Premium only</option>
                    </select>
                </label>
            </div>

            <label class="block">
                <span class="mb-2 block text-[10px] font-bold uppercase tracking-widest text-gray-500">Status</span>
                <select name="status" id="cm-status"
                    class="h-11 w-full rounded-xl border border-white/10 bg-[#0a0a0a] px-4 text-sm text-white outline-none focus:border-[#00A6A6] focus:ring-2 focus:ring-[#00A6A6]/20">
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </label>

            <div class="flex flex-col-reverse gap-3 pt-2 sm:flex-row">
                <button type="button" onclick="closeClassModal()"
                    class="rounded-full border border-white/10 px-5 py-3 text-sm font-bold text-gray-300 transition-colors hover:bg-white/5 hover:text-white sm:flex-1">
                    Cancel
                </button>
                <button type="submit"
                    class="rounded-full bg-[#00A6A6] px-5 py-3 text-sm font-bold text-white shadow-[0_0_15px_rgba(0,166,166,0.35)] transition-all hover:-translate-y-0.5 hover:bg-[#008c8c] sm:flex-[2]">
                    Save class
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function() {
        const modal = document.getElementById('classModal');
        const form  = document.getElementById('classForm');

        window.openClassModal = function(cls) {
            form.reset();
            document.getElementById('cm-title').textContent = cls ? 'EDIT CLASS' : 'NEW CLASS';
            document.getElementById('cm-id').value = cls ? cls.id : '';
            document.getElementById('cm-name').value = cls ? (cls.name || '') : '';
            document.getElementById('cm-description').value = cls ? (cls.description || '') : '';
            document.getElementById('cm-coach').value = cls ? (cls.coach_name || '') : '';
            document.getElementById('cm-day').value = cls ? String(cls.day_of_week) : '1';
            document.getElementById('cm-start').value = cls ? String(cls.start_time).slice(0, 5) : '07:00';
            document.getElementById('cm-duration').value = cls ? cls.duration_min : 60;
            document.getElementById('cm-capacity').value = cls ? cls.capacity : 20;
            document.getElementById('cm-tier').value = cls ? cls.tier_min : 'standard';
            document.getElementById('cm-status').value = cls ? cls.status : 'active';

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => document.getElementById('cm-name').focus(), 100);
        };

        window.closeClassModal = function() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        };

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('flex')) closeClassModal();
        });
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeClassModal();
        });
    })();
</script>
