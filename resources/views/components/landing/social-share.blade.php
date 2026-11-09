@php
    $shareCopy = config('landing.content.social_share');
    $shareUrl = url('/');
    $shareTitle = config('landing.site.seo.title');
    $facebookShareHref = 'https://www.facebook.com/sharer/sharer.php?u='.urlencode($shareUrl);
    $xShareHref = 'https://twitter.com/intent/tweet?url='.urlencode($shareUrl).'&text='.urlencode($shareTitle);
@endphp

<div
    x-data="{
        canNativeShare: false,
        copied: false,
        init() {
            this.canNativeShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';
        },
        async triggerNativeShare() {
            if (!this.canNativeShare) return;
            try {
                await navigator.share({
                    title: @js($shareTitle),
                    text: @js(config('landing.site.tagline')),
                    url: @js($shareUrl)
                });
            } catch (e) {}
        },
        async copyLink() {
            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(@js($shareUrl));
                } else {
                    const input = document.createElement('input');
                    input.value = @js($shareUrl);
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    document.body.removeChild(input);
                }
                this.copied = true;
                setTimeout(() => { this.copied = false; }, 3000);
            } catch (e) {}
        }
    }"
    class="flex flex-wrap items-center justify-center gap-3 pt-6"
>
    <span class="text-xs font-semibold uppercase tracking-wider text-gms-muted">
        {{ $shareCopy['label'] }}
    </span>

    <a
        href="{{ $facebookShareHref }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="{{ $shareCopy['facebook_label'] }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gms-border bg-[#080B12] px-3 py-1.5 text-xs font-semibold text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
    >
        <span>{{ $shareCopy['facebook_label'] }}</span>
    </a>

    <a
        href="{{ $xShareHref }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="{{ $shareCopy['x_label'] }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gms-border bg-[#080B12] px-3 py-1.5 text-xs font-semibold text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
    >
        <span>{{ $shareCopy['x_label'] }}</span>
    </a>

    <button
        type="button"
        x-show="canNativeShare"
        x-cloak
        @click="triggerNativeShare()"
        aria-label="{{ $shareCopy['native_label'] }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gms-border bg-[#080B12] px-3 py-1.5 text-xs font-semibold text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
    >
        <span>{{ $shareCopy['native_label'] }}</span>
    </button>

    <button
        type="button"
        @click="copyLink()"
        aria-label="{{ $shareCopy['copy_label'] }}"
        class="inline-flex items-center gap-1.5 rounded-lg border border-gms-border bg-[#080B12] px-3 py-1.5 text-xs font-semibold text-gms-text transition hover:border-gms-cyan/60 hover:text-gms-cyan focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan"
    >
        <span x-text="copied ? '{{ $shareCopy['copied_feedback'] }}' : '{{ $shareCopy['copy_label'] }}'">
            {{ $shareCopy['copy_label'] }}
        </span>
    </button>

    <span class="sr-only" role="status" aria-live="polite" x-text="copied ? '{{ $shareCopy['copied_feedback'] }}' : ''"></span>
</div>
