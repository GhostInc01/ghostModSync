@php
    $pricing = config('landing.content.pricing');
    $notifyForm = $pricing['notify_form'];
@endphp

<section
    id="pricing"
    aria-labelledby="pricing-heading"
    class="border-b border-gms-border bg-[#101522] py-20 sm:py-24"
>
    <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <p class="font-mono text-xs font-bold uppercase tracking-widest text-gms-cyan">
                {{ $pricing['eyebrow'] }}
            </p>
            <h2
                id="pricing-heading"
                class="mt-3 font-display text-3xl font-bold uppercase tracking-wide text-gms-text sm:text-5xl"
            >
                {{ $pricing['heading'] }}
            </h2>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-gms-muted sm:text-lg">
                {{ $pricing['description'] }}
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-8 rounded-3xl border border-gms-border bg-gms-card p-8 shadow-2xl lg:grid-cols-12 lg:p-10">
            {{-- Left Column: VTC Subscription Model Explanation --}}
            <div class="lg:col-span-6">
                <span class="inline-flex items-center gap-2 rounded-full border border-gms-cyan/40 bg-gms-cyan/10 px-4 py-1.5 font-mono text-xs font-extrabold uppercase tracking-wider text-gms-cyan">
                    <span class="h-2 w-2 rounded-full bg-gms-cyan" aria-hidden="true"></span>
                    <span>{{ $pricing['status_badge'] }}</span>
                </span>

                <h3 class="mt-5 text-2xl font-extrabold text-gms-text">
                    Group-Level VTC Subscription
                </h3>

                <ul class="mt-5 space-y-3.5">
                    @foreach ($pricing['model_points'] as $point)
                        <li class="flex items-start gap-3 text-sm text-gms-text sm:text-base">
                            <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-400" aria-hidden="true">
                                <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd"/>
                                </svg>
                            </span>
                            <span>{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Right Column: Pricing Notification Form (Placeholder Handler) --}}
            {{-- TODO: Connect the form to the backend mailing-list or notification service. --}}
            <div
                x-data="{
                    email: '{{ old('email', '') }}',
                    status: '{{ session('pricing_notify_status') ? 'success' : ($errors->has('email') ? 'error' : 'idle') }}',
                    message: '{{ session('pricing_notify_status') ?? ($errors->first('email') ?? '') }}',
                    submitting: false,
                    async submitForm() {
                        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        if (!emailPattern.test(this.email.trim())) {
                            this.status = 'error';
                            this.message = @js($notifyForm['error_invalid_email']);
                            return;
                        }
                        this.submitting = true;
                        try {
                            const response = await fetch('{{ route('pricing.notify') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ email: this.email.trim() })
                            });
                            const data = await response.json();
                            if (response.ok) {
                                this.status = 'success';
                                this.message = data.message || @js($notifyForm['success_message']);
                                this.email = '';
                            } else {
                                this.status = 'error';
                                this.message = (data.errors && data.errors.email ? data.errors.email[0] : data.message) || @js($notifyForm['error_invalid_email']);
                            }
                        } catch (e) {
                            this.status = 'error';
                            this.message = @js($notifyForm['error_invalid_email']);
                        } finally {
                            this.submitting = false;
                        }
                    }
                }"
                class="flex flex-col justify-center rounded-2xl border border-gms-border bg-[#080B12] p-6 lg:col-span-6"
            >
                <h4 class="text-lg font-bold text-gms-text">
                    {{ $notifyForm['heading'] }}
                </h4>

                <form
                    method="POST"
                    action="{{ route('pricing.notify') }}"
                    @submit.prevent="submitForm()"
                    novalidate
                    class="mt-4 space-y-4"
                >
                    @csrf
                    <div>
                        <label for="pricing-notify-email" class="block text-xs font-semibold uppercase tracking-wider text-gms-muted">
                            {{ $notifyForm['email_label'] }}
                        </label>
                        <div class="mt-2 flex flex-col gap-3 sm:flex-row">
                            <input
                                id="pricing-notify-email"
                                name="email"
                                type="email"
                                required
                                autocomplete="email"
                                x-model="email"
                                placeholder="{{ $notifyForm['email_placeholder'] }}"
                                :aria-invalid="status === 'error' ? 'true' : 'false'"
                                aria-describedby="pricing-notify-feedback pricing-notify-disclaimer"
                                class="w-full rounded-xl border border-gms-border bg-gms-card px-4 py-3 text-sm text-gms-text placeholder-gms-muted/60 focus:border-gms-cyan focus:outline-none focus:ring-2 focus:ring-gms-cyan"
                            />
                            <button
                                type="submit"
                                :disabled="submitting"
                                class="inline-flex shrink-0 items-center justify-center rounded-xl bg-gms-cyan px-5 py-3 text-sm font-extrabold text-[#080B12] transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-gms-cyan disabled:opacity-60"
                            >
                                <span x-text="submitting ? 'Validating...' : '{{ $notifyForm['button'] }}'">
                                    {{ $notifyForm['button'] }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <div
                        id="pricing-notify-feedback"
                        role="status"
                        aria-live="polite"
                        x-show="status !== 'idle'"
                        class="rounded-xl border px-4 py-3 text-xs font-medium"
                        :class="status === 'success'
                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-300'
                            : 'border-red-500/40 bg-red-500/10 text-red-300'"
                    >
                        <p x-text="message">{{ session('pricing_notify_status') ?? $errors->first('email') }}</p>
                    </div>

                    <p id="pricing-notify-disclaimer" class="text-xs leading-relaxed text-gms-muted">
                        {{ $notifyForm['disclaimer'] }}
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
