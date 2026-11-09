@php
    $faq = config('landing.content.faq');
@endphp

<section
    id="faq"
    aria-labelledby="faq-heading"
    class="border-b border-gms-border bg-[#101522] py-20 sm:py-24"
>
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                {{ $faq['eyebrow'] }}
            </p>
            <h2
                id="faq-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $faq['heading'] }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-gms-muted sm:text-lg">
                {{ $faq['subtitle'] }}
            </p>
        </div>

        <div
            x-data="{ activeIndex: 0 }"
            class="mt-12 space-y-3.5"
        >
            @foreach ($faq['items'] as $index => $item)
                <div class="overflow-hidden rounded-2xl border border-gms-border bg-gms-card transition hover:border-gms-cyan/40">
                    <h3>
                        <button
                            type="button"
                            id="faq-trigger-{{ $index }}"
                            aria-controls="faq-panel-{{ $index }}"
                            :aria-expanded="(activeIndex === {{ $index }}).toString()"
                            @click="activeIndex = (activeIndex === {{ $index }} ? null : {{ $index }})"
                            class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left text-base font-bold text-gms-text focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-gms-cyan sm:text-lg"
                        >
                            <span>{{ $index + 1 }}. {{ $item['question'] }}</span>
                            <span
                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-gms-border bg-[#080B12] text-gms-cyan transition-transform duration-200"
                                :class="activeIndex === {{ $index }} ? 'rotate-180' : ''"
                                aria-hidden="true"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                        </button>
                    </h3>

                    <div
                        id="faq-panel-{{ $index }}"
                        role="region"
                        aria-labelledby="faq-trigger-{{ $index }}"
                        x-show="activeIndex === {{ $index }}"
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0"
                        x-transition:enter-end="opacity-100"
                        class="border-t border-gms-border/70 px-6 pb-5 pt-4 text-sm leading-relaxed text-gms-muted sm:text-base"
                    >
                        <p>{{ $item['answer'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
