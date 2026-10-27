<section id="contact" class="bg-white py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="font-heading text-5xl tracking-wide text-black sm:text-6xl">VISIT US</h2>
            <p class="mt-6 text-lg text-gray-500">Find PowerMagz Gym in Tabaco City, Albay.</p>
        </div>

        <div class="mt-16 grid gap-8 lg:grid-cols-2">
            <div class="space-y-4">
                <?php $contacts = [
                    ['📍', 'Address',  'Zone 4, Matagbac, Tabaco City, Albay', null],
                    ['📞', 'Contact',  '09618041523', null],
                    ['🕕', 'Hours',    '6:00 AM – 7:00 PM, Every Day', null],
                    ['📘', 'Facebook', 'web.facebook.com/zabbyandamber', 'https://web.facebook.com/zabbyandamber'],
                ];
                foreach ($contacts as [$icon, $label, $value, $href]): ?>
                    <div class="group flex items-start gap-4 rounded-2xl border border-gray-200 bg-white p-6 transition-all duration-300
                      hover:-translate-y-1 hover:border-[#00A6A6] hover:shadow-xl hover:shadow-[#00A6A6]/10">
                        <div class="inline-flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-[#00A6A6]/10 text-xl
                        transition-all duration-300 group-hover:scale-110 group-hover:rotate-6 group-hover:bg-[#00A6A6] group-hover:text-white group-hover:shadow-lg group-hover:shadow-[#00A6A6]/40">
                            <?= $icon ?>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider text-gray-400 transition-colors duration-300 group-hover:text-[#00A6A6]">
                                <?= e($label) ?>
                            </div>
                            <?php if ($href): ?>
                                <a href="<?= e($href) ?>" target="_blank" rel="noreferrer"
                                    class="text-base font-medium text-black transition-colors duration-300 hover:text-[#00A6A6]">
                                    <?= e($value) ?>
                                </a>
                            <?php else: ?>
                                <div class="text-base font-medium text-black"><?= e($value) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Map container — NO overflow:hidden here -->
            <div class="group map-outer relative min-h-[400px] rounded-2xl border border-gray-200 shadow-lg transition-all duration-500 hover:shadow-2xl hover:shadow-[#00A6A6]/20 hover:ring-2 hover:ring-[#00A6A6]">
                <!-- Filter applied to this inner wrapper, NOT to the iframe itself -->
                <div class="map-filter h-full w-full overflow-hidden rounded-2xl">
                    <iframe title="PowerMagz Gym Location"
                        src="https://www.google.com/maps?q=Powermagz+Gym,+Tabaco+City,+Albay,+Philippines&z=17&output=embed&hl=en"
                        class="h-full min-h-[400px] w-full border-0 transition-all duration-500 group-hover:scale-[1.02]"
                        loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    /* Google Maps — dark mode filter */
    /* Applied to .map-filter (a div), NOT the iframe, to avoid overflow:hidden conflicts */

    .map-filter {
        transition: filter 0.4s ease;
        -webkit-transition: -webkit-filter 0.4s ease;
    }

    html.dark .map-filter {
        -webkit-filter: invert(92%) hue-rotate(180deg) brightness(95%) contrast(90%) !important;
        filter: invert(92%) hue-rotate(180deg) brightness(95%) contrast(90%) !important;
        isolation: isolate;
    }

    /* Map outer border in dark mode */
    html.dark .map-outer {
        border-color: #334155 !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5) !important;
    }
</style>