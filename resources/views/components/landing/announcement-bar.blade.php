@php
    $announcement = config('landing.content.announcement');
    $discordUrl = config('landing.socials.discord_invite');
@endphp

@if (!empty($announcement['enabled']))
    {{-- TODO: Replace announcement text in config/landing/content.php when the official beta launch date is confirmed. --}}
    <div
        x-data="{
            dismissed: false,
            init() {
                try {
                    this.dismissed = window.sessionStorage.getItem('{{ $announcement['storage_key'] }}') === '1';
                } catch (e) {
                    this.dismissed = false;
                }
            },
            dismiss() {
                this.dismissed = true;
                try {
                    window.sessionStorage.setItem('{{ $announcement['storage_key'] }}', '1');
                } catch (e) {}
            }
        }"
        x-show="!dismissed"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        role="region"
        aria-label="Platform launch announcement"
        class="relative z-50 border-b border-gms-border bg-[#101522] text-gms-text"
    >
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-2.5 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs sm:text-sm">
                <span class="inline-flex items-center rounded-full border border-gms-cyan/40 bg-gms-cyan/10 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-gms-cyan">
                    {{ $announcement['badge'] }}
                </span>
                <p class="font-medium text-gms-text">
                    {{ $announcement['text'] }}
                </p>
                @if (!empty($discordUrl))
                    <a
                        href="{{ $discordUrl }}"
                        class="inline-flex items-center gap-1 font-semibold text-gms-cyan underline decoration-gms-cyan/50 underline-offset-4 transition hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan rounded"
                    >
                        <span>{{ $announcement['discord_cta'] }}</span>
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                @endif
            </div>

            <button
                type="button"
                @click="dismiss()"
                aria-label="{{ $announcement['dismiss_label'] }}"
                class="inline-flex shrink-0 items-center justify-center rounded-md p-1.5 text-gms-muted transition hover:bg-gms-card hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
            >
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/>
                </svg>
            </button>
        </div>
    </div>
@endif
