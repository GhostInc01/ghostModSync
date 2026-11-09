@php
    $features = config('landing.content.features');
@endphp

<section
    id="features"
    aria-labelledby="features-heading"
    class="border-b border-gms-border bg-[#080B12] py-20 sm:py-24"
>
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                {{ $features['eyebrow'] }}
            </p>
            <h2
                id="features-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $features['heading'] }}
            </h2>
            <p class="mt-4 text-base leading-relaxed text-gms-muted sm:text-lg">
                {{ $features['subtitle'] }}
            </p>
        </div>

        <div class="mt-14 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features['cards'] as $card)
                <article class="group rounded-2xl border border-gms-border bg-gms-card p-6 transition hover:-translate-y-1 hover:border-gms-cyan/50">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl border border-gms-cyan/30 bg-gms-cyan/10 text-gms-cyan transition group-hover:bg-gms-cyan group-hover:text-[#080B12]">
                        <x-landing.icon :name="$card['icon']" class="h-6 w-6" />
                    </div>
                    <h3 class="mt-5 font-display text-xl font-bold uppercase tracking-wide text-gms-text">
                        {{ $card['title'] }}
                    </h3>
                    <p class="mt-2.5 text-sm leading-relaxed text-gms-muted">
                        {{ $card['description'] }}
                    </p>
                </article>
            @endforeach
        </div>
    </div>
</section>
