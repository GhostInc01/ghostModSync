@php
    $cookieCopy = config('landing.content.cookie_consent');
@endphp

{{-- TODO: Connect the final consent configuration to the actual privacy and analytics implementation. --}}
<aside
    x-data="{
        visible: false,
        init() {
            try {
                const saved = window.localStorage.getItem('{{ $cookieCopy['storage_key'] }}');
                this.visible = !saved;
            } catch (e) {
                this.visible = true;
            }
        },
        saveChoice(choice) {
            this.visible = false;
            try {
                window.localStorage.setItem('{{ $cookieCopy['storage_key'] }}', JSON.stringify({
                    choice: choice,
                    updatedAt: new Date().toISOString()
                }));
            } catch (e) {}
        }
    }"
    x-show="visible"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-y-4"
    x-transition:enter-end="opacity-100 translate-y-0"
    aria-label="{{ $cookieCopy['title'] }}"
    class="fixed bottom-4 left-4 right-4 z-50 mx-auto max-w-4xl rounded-2xl border border-gms-border bg-[#101522]/95 p-5 text-gms-text shadow-2xl backdrop-blur-md"
    style="display: none;"
>
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
            <h2 class="text-sm font-bold text-gms-text">
                {{ $cookieCopy['title'] }}
            </h2>
            <p class="text-xs leading-relaxed text-gms-muted">
                {{ $cookieCopy['description'] }}
                <a
                    href="{{ route('legal.privacy') }}"
                    class="font-semibold text-gms-cyan underline underline-offset-4 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan rounded"
                >
                    {{ $cookieCopy['privacy_link_label'] }}
                </a>
            </p>
        </div>

        <div class="flex shrink-0 flex-wrap items-center gap-2.5">
            <button
                type="button"
                @click="saveChoice('essential_only')"
                class="inline-flex items-center justify-center rounded-xl border border-gms-border bg-gms-card px-4 py-2 text-xs font-semibold text-gms-text transition hover:border-gms-cyan/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
            >
                {{ $cookieCopy['essential_only'] }}
            </button>
            <button
                type="button"
                @click="saveChoice('accepted_all')"
                class="inline-flex items-center justify-center rounded-xl bg-gms-cyan px-4 py-2 text-xs font-extrabold text-[#080B12] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
            >
                {{ $cookieCopy['accept_all'] }}
            </button>
        </div>
    </div>
</aside>
