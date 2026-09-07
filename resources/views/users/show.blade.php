@extends('layouts.app')

@section('content')
    @php
        $isOwnProfile = auth()->id() === $user->id;
        $commissionData = $commissionData ?? null;
        $commissionError = $commissionError ?? null;
        $commissionMonth = $commissionMonth ?? now()->format('Y-m');
        $commissionMonthNames = [
            '01' => 'enero', '02' => 'febrero', '03' => 'marzo', '04' => 'abril',
            '05' => 'mayo', '06' => 'junio', '07' => 'julio', '08' => 'agosto',
            '09' => 'septiembre', '10' => 'octubre', '11' => 'noviembre', '12' => 'diciembre',
        ];
        $formatCommissionMonth = static function (?string $month, bool $lowercase = false) use ($commissionMonthNames): string {
            if (! is_string($month) || ! preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $month, $matches)) {
                return '';
            }

            $label = $commissionMonthNames[$matches[2]] . ' de ' . $matches[1];

            return $lowercase ? $label : ucfirst($label);
        };
        $visibleRole = app_visible_role(auth()->user());
        $canOpenRankings = app_can_access_rankings(auth()->user());
        $salesRankingPosition = $rankingPositions['sales']['position'] ?? null;
        $salesTotal = $rankingPositions['sales']['total'] ?? 0;
        $purchaseRankingPosition = $rankingPositions['purchases']['position'] ?? null;
        $purchaseTotal = $rankingPositions['purchases']['total'] ?? 0;
        $tenureBadge = $user->tenure_badge;
        $itScheduleDays = [
            'monday' => 'Lunes',
            'tuesday' => 'Martes',
            'wednesday' => 'Miercoles',
            'thursday' => 'Jueves',
            'friday' => 'Viernes',
        ];
        $tenureBadgeStyles = [
            'starter' => [
                'container' => 'border-amber-200/80 bg-gradient-to-br from-amber-50 to-white text-amber-950 shadow-amber-100/40',
                'icon' => 'bg-amber-100 text-amber-700 ring-1 ring-amber-200/80',
                'eyebrow' => 'text-amber-700/70',
                'label' => 'text-amber-950',
            ],
            'veteran' => [
                'container' => 'border-sky-200/80 bg-gradient-to-br from-sky-50 to-white text-sky-950 shadow-sky-100/40',
                'icon' => 'bg-sky-100 text-sky-700 ring-1 ring-sky-200/80',
                'eyebrow' => 'text-sky-700/70',
                'label' => 'text-sky-950',
            ],
            'legacy' => [
                'container' => 'border-emerald-200/80 bg-gradient-to-br from-emerald-50 to-white text-emerald-950 shadow-emerald-100/40',
                'icon' => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200/80',
                'eyebrow' => 'text-emerald-700/70',
                'label' => 'text-emerald-950',
            ],
        ];
        $tenureBadgeStyle = $tenureBadge ? ($tenureBadgeStyles[$tenureBadge['tone']] ?? $tenureBadgeStyles['starter']) : null;
    @endphp

    <main
        x-data="imageLightbox()"
        x-effect="document.body.classList.toggle('overflow-hidden', isImageOpen)"
        @keydown.escape.window="closeImage()"
        @keydown.window="handleKeydown($event)"
        class="mx-auto flex min-h-screen max-w-5xl flex-col px-6 py-8"
    >
        <section class="rounded-3xl border border-brand-secondary/10 bg-white p-6 shadow-sm md:p-8">
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                <div class="flex min-w-0 items-center gap-5">
                    <button
                        type="button"
                        @click="openImage({ src: @js($user->avatar_url), alt: @js('Avatar de '.$user->name), title: @js($user->name) })"
                        class="group relative h-24 w-24 shrink-0 cursor-pointer overflow-hidden rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-primary/40"
                        aria-label="Ampliar imagen de {{ $user->name }}"
                    >
                        <img src="{{ $user->avatar_url }}" alt="Avatar de {{ $user->name }}" class="block h-full w-full rounded-full object-cover ring-2 ring-brand-primary/10 transition duration-300 group-hover:scale-105 group-hover:brightness-75">
                        <span class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-full bg-brand-secondary/0 text-xs font-semibold uppercase tracking-[0.18em] text-white opacity-0 transition duration-300 group-hover:bg-brand-secondary/35 group-hover:opacity-100">
                            Ver
                        </span>
                    </button>

                    <div class="min-w-0">
                        <p class="text-sm font-semibold uppercase tracking-[0.18em] text-brand-primary/80">Perfil de usuario</p>
                        <h1 class="mt-2 text-3xl font-bold tracking-tight text-brand-secondary">{{ $user->name }}</h1>
                        <p class="mt-2 text-sm text-brand-secondary/65">{{ $user->email }}</p>
                        <div class="mt-2 space-y-2">
                            @if (filled($user->job_position))
                                <p class="text-base font-medium tracking-tight text-brand-secondary/80 md:text-lg">
                                    {{ $user->job_position }}
                                </p>
                            @endif

                        @if ($user->company_entry_date)
                            <p class="text-sm text-brand-secondary/60">
                                Desde: {{ $user->company_entry_date->format('d/m/Y') }}
                            </p>
                        @endif

                        @if ($user->isDisabled())
                            <span class="mt-3 inline-flex rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">Desactivado</span>
                        @endif
                    </div>
                        @if ($user->isDisabled())
                            <div class="mt-4 inline-flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
                                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 text-slate-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M12 9v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                                        <path d="M12 17h.01" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="font-semibold text-slate-700">Cuenta desactivada</p>
                                    <p class="mt-0.5 text-slate-500">Este usuario no puede iniciar sesión ni acceder a la aplicación hasta que se reactive.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col items-start gap-4 lg:items-end">
                    <div class="flex items-center justify-start gap-3 lg:justify-end">
                    @unless ($isOwnProfile)
                        <a href="{{ route('chat.beta', ['recipient' => $user->id]) }}" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-primary text-white transition hover:opacity-90" title="Chatear" aria-label="Chatear con {{ $user->name }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M8 10.5H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M8 14H13.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M17 3.33782C15.5291 2.48697 13.8214 2 12 2C6.47715 2 2 6.47715 2 12C2 13.5997 2.37562 15.1116 3.04346 16.4525C3.22094 16.8088 3.28001 17.2161 3.17712 17.6006L2.58151 19.8267C2.32295 20.793 3.20701 21.677 4.17335 21.4185L6.39939 20.8229C6.78393 20.72 7.19121 20.7791 7.54753 20.9565C8.88837 21.6244 10.4003 22 12 22C17.5228 22 22 17.5228 22 12C22 10.1786 21.513 8.47087 20.6622 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                            </svg>
                        </a>
                    @endunless

                    @if ($user->linkedin_url)
                        <a href="{{ $user->linkedin_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0A66C2] text-white transition hover:opacity-90" title="Ver LinkedIn" aria-label="Ver LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path d="M6.94 8.5H3.56V19h3.38V8.5ZM5.25 3C4.17 3 3.3 3.88 3.3 4.96c0 1.07.87 1.94 1.95 1.94 1.08 0 1.95-.87 1.95-1.94C7.2 3.88 6.33 3 5.25 3Zm14.45 9.47c0-3.17-1.69-4.64-3.95-4.64-1.82 0-2.64 1-3.09 1.7V8.5H9.28c.04.68 0 10.5 0 10.5h3.38v-5.86c0-.31.02-.62.11-.84.25-.62.82-1.27 1.79-1.27 1.27 0 1.78.96 1.78 2.37V19h3.38v-6.53Z" />
                            </svg>
                        </a>
                    @endif

                    @if ($isOwnProfile)
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center rounded-2xl border border-brand-secondary/15 px-4 py-3 text-sm font-semibold text-brand-secondary transition hover:bg-brand-secondary/5">Editar perfil</a>
                    @endif

                    </div>

                    @if ($tenureBadge)
                        <div class="inline-flex items-center gap-3 rounded-3xl border px-4 py-3 shadow-sm {{ $tenureBadgeStyle['container'] }}">
                            <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl {{ $tenureBadgeStyle['icon'] }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.19623 6.43783C2.64395 5.48125 2.9717 4.25807 3.92828 3.70578L7.39238 1.70578C8.34897 1.1535 9.57215 1.48125 10.1244 2.43783L12.0436 5.76189L13.9627 2.43783C14.515 1.48125 15.7382 1.1535 16.6948 1.70578L20.1589 3.70578C21.1155 4.25807 21.4432 5.48125 20.8909 6.43783L18.0155 11.4183C18.6408 12.4661 19 13.6911 19 15C19 18.866 15.866 22 12 22C8.13401 22 5 18.866 5 15C5 13.6603 5.37636 12.4085 6.02912 11.3445L3.19623 6.43783ZM16.656 9.77293L19.1589 5.43783L15.6948 3.43783L13.1983 7.76189L13.4188 8.14391C14.6457 8.39647 15.7553 8.97003 16.656 9.77293ZM8.39238 3.43783L11.0623 8.06229C9.67124 8.24852 8.4095 8.84331 7.40175 9.72201L4.92828 5.43783L8.39238 3.43783ZM12 20C14.7614 20 17 17.7615 17 15C17 12.2386 14.7614 10 12 10C9.23858 10 7 12.2386 7 15C7 17.7615 9.23858 20 12 20Z"/>
                                </svg>
                            </span>
                            <div class="min-w-0">
                                <p class="text-[0.65rem] font-semibold uppercase tracking-[0.24em] {{ $tenureBadgeStyle['eyebrow'] }}">Antigüedad</p>
                                <p class="mt-0.5 text-sm font-bold {{ $tenureBadgeStyle['label'] }}">{{ $tenureBadge['label'] }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-slate-50 p-6">
                <h2 class="text-lg font-semibold text-brand-secondary">Información general</h2>

                <dl class="mt-5 grid gap-5 text-sm md:grid-cols-3">
                    @if ($user->resolved_dealership_name)
                        <div>
                            <dt class="text-brand-secondary/60">Delegación</dt>
                            <dd class="mt-2">
                                @if ($user->assignedDealership)
                                    <a href="{{ route('dealerships.show', $user->assignedDealership) }}"
                                        class="font-semibold text-brand-secondary transition hover:text-brand-primary">
                                        {{ $user->resolved_dealership_name }}
                                    </a>
                                @else
                                    <span class="font-semibold text-brand-secondary">{{ $user->resolved_dealership_name }}</span>
                                @endif
                            </dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-brand-secondary/60">Rol</dt>
                        <dd class="mt-1">
                            <span class="inline-flex rounded-full bg-brand-primary/10 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-brand-primary">{{ $user->role_label }}</span>
                        </dd>
                    </div>

                    @if (filled($user->phone))
                        <div>
                            <dt class="text-brand-secondary/60">Teléfono</dt>
                            <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->phone }}</dd>
                        </div>
                    @endif

                    @if (filled($user->enreach_extension))
                        <div>
                            <dt class="text-brand-secondary/60">Extensión Enreach</dt>
                            <dd class="mt-1 font-semibold text-brand-secondary">{{ $user->enreach_extension }}</dd>
                        </div>
                    @endif

                    @if ($user->isDisabled())
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 md:col-span-3">
                            <dt class="text-brand-secondary/60">Estado de la cuenta</dt>
                            <dd class="mt-1 text-sm font-semibold uppercase tracking-wide text-slate-600">Cuenta desactivada</dd>
                            <p class="mt-2 text-sm text-slate-600">
                                Usuario desactivado desde {{ $user->disabled_at?->format('d/m/Y H:i') ?? 'fecha desconocida' }}.
                            </p>
                            @if ($user->disabled_reason)
                                <p class="mt-2 text-sm text-slate-500"><span class="font-semibold text-slate-600">Motivo:</span> {{ $user->disabled_reason }}</p>
                            @endif
                        </div>
                    @endif
                </dl>
            </section>

            @if ($user->isRankedCommercial() && ($salesRankingPosition || $purchaseRankingPosition))
                <section class="mt-8 rounded-3xl border border-brand-secondary/10 bg-white p-6">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-brand-secondary">Posición en rankings</h2>
                            <p class="mt-1 text-sm text-brand-secondary/65">Puesto actual del mes en ventas y compras.</p>
                        </div>
                        <span class="inline-flex w-fit rounded-full bg-brand-secondary/5 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-brand-secondary/70">
                            {{ $user->role_label }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @if ($canOpenRankings)
                            <a href="{{ route('leaderboard.sales') }}" class="block rounded-3xl transition hover:-translate-y-1 hover:shadow-md">
                                <div class="rounded-3xl border border-amber-200/70 bg-amber-50/80 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700/80">Ranking ventas</p>
                                    <p class="mt-3 text-3xl font-bold text-amber-800">
                                        {{ $salesRankingPosition ? 'Top ' . $salesRankingPosition : 'Sin posición' }}
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-amber-800/85">
                                        {{ number_format((float) $salesTotal, 0, ',', '.') }} ventas este mes
                                    </p>
                                    <p class="mt-1 text-sm text-amber-800/75">Según el ranking mensual de ventas.</p>
                                </div>
                            </a>
                        @else
                            <div class="block rounded-3xl">
                                <div class="rounded-3xl border border-amber-200/70 bg-amber-50/80 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700/80">Ranking ventas</p>
                                    <p class="mt-3 text-3xl font-bold text-amber-800">
                                        {{ $salesRankingPosition ? 'Top ' . $salesRankingPosition : 'Sin posición' }}
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-amber-800/85">
                                        {{ number_format((float) $salesTotal, 0, ',', '.') }} ventas este mes
                                    </p>
                                    <p class="mt-1 text-sm text-amber-800/75">Según el ranking mensual de ventas.</p>
                                </div>
                            </div>
                        @endif

                        @if ($canOpenRankings)
                            <a href="{{ route('leaderboard.purchases') }}" class="block rounded-3xl transition hover:-translate-y-1 hover:shadow-md">
                                <div class="rounded-3xl border border-sky-200/70 bg-sky-50/80 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700/80">Ranking compras</p>
                                    <p class="mt-3 text-3xl font-bold text-sky-800">
                                        {{ $purchaseRankingPosition ? 'Top ' . $purchaseRankingPosition : 'Sin posición' }}
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-sky-800/85">
                                        {{ number_format((float) $purchaseTotal, 0, ',', '.') }} compras este mes
                                    </p>
                                    <p class="mt-1 text-sm text-sky-800/75">Según el ranking mensual de compras.</p>
                                </div>
                            </a>
                        @else
                            <div class="block rounded-3xl">
                                <div class="rounded-3xl border border-sky-200/70 bg-sky-50/80 p-5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700/80">Ranking compras</p>
                                    <p class="mt-3 text-3xl font-bold text-sky-800">
                                        {{ $purchaseRankingPosition ? 'Top ' . $purchaseRankingPosition : 'Sin posición' }}
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-sky-800/85">
                                        {{ number_format((float) $purchaseTotal, 0, ',', '.') }} compras este mes
                                    </p>
                                    <p class="mt-1 text-sm text-sky-800/75">Según el ranking mensual de compras.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            @if ($isOwnProfile && $user->role === \App\Models\User::ROLE_USER && in_array($user->extra_role, [\App\Models\User::ROLE_COMMERCIAL, \App\Models\User::ROLE_STORE_MANAGER, \App\Models\User::ROLE_AREA_MANAGER, \App\Models\User::ROLE_HR_NEWCARS], true) && filled($user->salesforce_user_id))
                <section class="mt-8 rounded-3xl border border-brand-primary/15 bg-gradient-to-br from-brand-primary/5 via-white to-white p-6 shadow-sm" data-personal-commissions-card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-primary/80">Resumen económico</p>
                            <h2 class="mt-2 text-xl font-semibold text-brand-secondary">Comisiones personales</h2>
                            <p class="mt-1 text-sm text-brand-secondary/65">Consulta únicamente tu comisión oficial del mes seleccionado.</p>
                        </div>

                        <form method="GET" action="{{ route('profile.show') }}" class="flex flex-col gap-2 sm:items-end">
                            <label for="commission-month" class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-secondary/60">Mes consultado</label>
                            <div class="flex gap-2">
                                <input id="commission-month" name="month" type="month" value="{{ $commissionMonth }}" max="{{ now()->format('Y-m') }}"
                                    class="rounded-2xl border border-brand-secondary/15 bg-white px-3 py-2 text-sm text-brand-secondary outline-none focus:border-brand-primary focus:ring-2 focus:ring-brand-primary/20">
                                <button type="submit" class="rounded-2xl bg-brand-primary px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90">Consultar</button>
                            </div>
                        </form>
                    </div>

                    @if ($commissionError)
                        <div class="mt-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            @switch($commissionError['status'])
                                @case(404)
                                    No se ha encontrado información de comisiones para este comercial o no está habilitado.
                                    @break
                                @case(422)
                                    El mes seleccionado no es válido o no se pueden consultar meses futuros.
                                    @break
                                @case(429)
                                    Se ha alcanzado temporalmente el límite de consultas. Inténtalo de nuevo más tarde.
                                    @if (! empty($commissionError['retry_after']))
                                        <span class="mt-1 block text-xs">Puedes reintentarlo en {{ $commissionError['retry_after'] }} segundos.</span>
                                    @endif
                                    @break
                                @default
                                    El servicio de comisiones no está disponible temporalmente. Inténtalo de nuevo más tarde.
                            @endswitch
                        </div>
                    @elseif (is_array($commissionData) && ! $commissionData['has_data'])
                        <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            No hay datos económicos para {{ $formatCommissionMonth($commissionData['month'] ?? $commissionMonth, true) }}.
                        </div>
                    @elseif (is_array($commissionData) && $commissionData['has_data'])
                        @php
                            $commissionRow = is_array($commissionData['row'] ?? null) ? $commissionData['row'] : [];
                            $commissionDetails = is_array($commissionRow['details'] ?? null) ? $commissionRow['details'] : [];
                            $commissionHasValue = static fn (array $source, string $key): bool => array_key_exists($key, $source) && $source[$key] !== null;
                            $commissionHasAmount = static fn (array $source, string $key): bool => $commissionHasValue($source, $key) && is_numeric($source[$key]) && (float) $source[$key] !== 0.0;
                            $formatCommissionAmount = static fn (mixed $value): string => number_format(abs((float) $value), 2, ',', '.') . ' €';
                            $commissionMode = strtolower((string) ($commissionRow['commission_mode'] ?? $commissionData['commission_mode'] ?? 'comercial'));
                            $deliveryAdjustment = null;
                            if (is_numeric($commissionRow['prima_total'] ?? null) && is_numeric($commissionRow['prima_adjusted'] ?? null)) {
                                $calculatedDeliveryAdjustment = (float) $commissionRow['prima_total'] - (float) $commissionRow['prima_adjusted'];
                                $deliveryAdjustment = $calculatedDeliveryAdjustment !== 0.0 ? $calculatedDeliveryAdjustment : null;
                            }

                            $commercialSections = [
                                ['lines' => [
                                    ['field' => 'sales_amount', 'label' => 'Ventas gestionadas', 'sign' => '+'],
                                    ['field' => 'shared_amount', 'label' => 'Comisión por ventas de otros comerciales', 'sign' => '+'],
                                    ['field' => 'stock_150_amount', 'label' => 'Incentivo Stock 150', 'sign' => '+'],
                                    ['field' => 'bonus_15_amount', 'label' => 'Bonificación 15', 'sign' => '+'],
                                    ['field' => 'discount_penalty_amount', 'label' => 'Penalización por descuento', 'sign' => '−'],
                                    ['field' => 'purchases_amount', 'label' => 'Tasaciones y cambios', 'sign' => '+'],
                                ], 'subtotal' => ['field' => 'prima_total', 'label' => 'Subtotal de prima']],
                                ['lines' => [
                                    ['value' => $deliveryAdjustment, 'label' => 'Ajuste por tramo de entregas', 'sign' => '−'],
                                ], 'subtotal' => ['field' => 'prima_adjusted', 'label' => 'Prima ajustada']],
                                ['lines' => [
                                    ['field' => 'guarantee_penalty', 'label' => 'Penalización de garantías', 'sign' => '−'],
                                    ['field' => 'reviews_penalty', 'label' => 'Penalización de reseñas', 'sign' => '−'],
                                    ['field' => 'financing_penalty', 'label' => 'Penalización de financiación', 'sign' => '−'],
                                    ['field' => 'financing_cancellation_penalty_amount', 'label' => 'Cancelación de financiación', 'sign' => '−'],
                                ], 'subtotal' => ['field' => 'prima_after_penalties', 'label' => 'Prima después de penalizaciones']],
                                ['lines' => [
                                    ['field' => 'financing_product_amount', 'label' => 'Comisión de producto financiero', 'sign' => '+'],
                                    ['field' => 'guarantee_product_amount', 'label' => 'Comisión de producto de garantía', 'sign' => '+'],
                                ]],
                            ];
                            $appraiserLines = [
                                ['field' => 'purchases_amount', 'label' => 'Compras gestionadas', 'sign' => '+'],
                                ['field' => 'sales_amount', 'label' => 'Ventas gestionadas', 'sign' => '+'],
                                ['field' => 'appraiser_financing_commission', 'label' => 'Comisión de financiación', 'sign' => '+'],
                                ['field' => 'appraiser_speed_amount', 'label' => 'Incentivo por velocidad', 'sign' => '+'],
                                ['field' => 'financing_cancellation_penalty_amount', 'label' => 'Cancelación de financiación', 'sign' => '−'],
                            ];
                            $settlementSections = $commissionMode === 'tasador' ? [['lines' => $appraiserLines]] : $commercialSections;
                            $commissionLineHasAmount = static fn (array $line): bool => array_key_exists('value', $line)
                                ? is_numeric($line['value']) && (float) $line['value'] !== 0.0
                                : $commissionHasAmount($commissionRow, $line['field']);
                            $commissionLineValue = static fn (array $line): mixed => array_key_exists('value', $line)
                                ? $line['value']
                                : $commissionRow[$line['field']];
                            $operationTypeLabels = [
                                'sale' => 'Venta', 'venta' => 'Venta',
                                'shared_sale' => 'Venta compartida propia', 'venta_compartida' => 'Venta compartida propia',
                                'appraisal' => 'Tasación', 'tasacion' => 'Tasación', 'tasación' => 'Tasación',
                                'change' => 'Cambio', 'cambio' => 'Cambio',
                            ];
                            $formatOperationDate = static function (mixed $value): ?string {
                                if (! is_string($value) || trim($value) === '') {
                                    return null;
                                }

                                try {
                                    return \Carbon\CarbonImmutable::parse($value)->format('d/m/Y');
                                } catch (\Throwable) {
                                    return $value;
                                }
                            };
                            $operationRows = is_array($commissionDetails['operations'] ?? null) ? $commissionDetails['operations'] : [];
                            $operationRows = array_is_list($operationRows) ? $operationRows : [$operationRows];
                            $operationGroups = collect($operationRows)
                                ->filter(fn (mixed $operation): bool => is_array($operation)
                                    && array_key_exists('commission_amount', $operation)
                                    && $operation['commission_amount'] !== null
                                    && is_numeric($operation['commission_amount'])
                                    && (float) $operation['commission_amount'] !== 0.0)
                                ->groupBy(fn (array $operation): string => implode('|', [
                                    (string) ($operation['type'] ?? ''),
                                    (string) ($operation['reason'] ?? ''),
                                    (string) $operation['commission_amount'],
                                ]));
                            $sharedRows = is_array($commissionDetails['shared'] ?? null) ? $commissionDetails['shared'] : [];
                            $sharedRows = array_is_list($sharedRows) ? $sharedRows : [$sharedRows];
                            $sharedRows = collect($sharedRows)->filter(fn (mixed $shared): bool => is_array($shared)
                                && array_key_exists('amount', $shared)
                                && $shared['amount'] !== null
                                && is_numeric($shared['amount'])
                                && (float) $shared['amount'] !== 0.0);
                            $sharedGroups = $sharedRows->groupBy(fn (array $shared): string => implode('|', [
                                (string) ($shared['operation'] ?? ''),
                                (string) $shared['amount'],
                            ]));
                            $hasSharedDetails = $sharedRows->isNotEmpty();

                            $detailDefinitions = [
                                'shared' => ['title' => 'Comisión por venta de otro comercial', 'amount' => 'amount', 'fields' => [
                                    'operation' => 'Operación', 'commercial' => 'Comercial implicado', 'commercial_name' => 'Comercial implicado', 'amount' => 'Importe',
                                ]],
                                'stock_150' => ['title' => 'Stock 150', 'amount' => 'amount', 'fields' => [
                                    'operation' => 'Operación', 'vehicle' => 'Vehículo', 'vehiculo' => 'Vehículo',
                                    'stock_days' => 'Días en stock', 'days_in_stock' => 'Días en stock', 'amount' => 'Importe',
                                ]],
                                'financing_cancellations' => ['title' => 'Cancelaciones de financiación', 'amount' => 'amount', 'fields' => [
                                    'concept' => 'Concepto', 'concepto' => 'Concepto', 'reason' => 'Motivo', 'motivo' => 'Motivo', 'amount' => 'Importe',
                                ]],
                                'appraiser_sales' => ['title' => 'Ventas del tasador', 'amount' => 'commission_amount', 'fields' => ['commission_amount' => 'Importe']],
                                'appraiser_financing' => ['title' => 'Financiación del tasador', 'amount' => 'commission_amount', 'fields' => ['commission_amount' => 'Importe']],
                                'appraiser_speed' => ['title' => 'Velocidad de gestión', 'amount' => 'commission_amount', 'fields' => [
                                    'speed_tier' => 'Tramo de velocidad', 'speed_bracket' => 'Tramo de velocidad',
                                    'days_until_sale' => 'Días hasta la venta', 'days' => 'Días hasta la venta', 'commission_amount' => 'Importe',
                                ]],
                            ];
                            $hasOtherDetailGroups = collect($detailDefinitions)->filter(fn (array $definition, string $detailKey): bool => $detailKey !== 'shared')
                                ->contains(function (array $definition, string $detailKey) use ($commissionDetails): bool {
                                    $rows = $commissionDetails[$detailKey] ?? null;
                                    if (! is_array($rows)) {
                                        return false;
                                    }

                                    $rows = array_is_list($rows) ? $rows : [$rows];

                                    return collect($rows)->contains(fn (mixed $row): bool => is_array($row)
                                        && array_key_exists($definition['amount'], $row)
                                        && $row[$definition['amount']] !== null
                                        && is_numeric($row[$definition['amount']])
                                        && (float) $row[$definition['amount']] !== 0.0);
                                });
                        @endphp

                        @if ($commissionHasValue($commissionRow, 'final_commission'))
                            <div class="mt-6 rounded-3xl border border-brand-secondary/10 bg-white px-4 py-4 sm:px-6">
                                <div class="space-y-2">
                                    @foreach ($settlementSections as $sectionIndex => $section)
                                        @php
                                            $visibleLines = collect($section['lines'])->filter($commissionLineHasAmount);
                                        @endphp
                                        @if ($visibleLines->isNotEmpty() || (isset($section['subtotal']) && $commissionHasAmount($commissionRow, $section['subtotal']['field'])))
                                            @if ($sectionIndex > 0)
                                                <div class="my-3 border-t border-brand-secondary/10"></div>
                                            @endif
                                            @foreach ($visibleLines as $line)
                                                <div class="flex items-center justify-between gap-4 py-1.5 text-sm sm:text-base">
                                                    <span class="min-w-0 text-brand-secondary/75">
                                                        <span class="mr-2 inline-block w-4 font-semibold {{ $line['sign'] === '−' ? 'text-rose-600' : 'text-emerald-700' }}">{{ $line['sign'] }}</span> {{ $line['label'] }}
                                                    </span>
                                                    <span class="shrink-0 font-semibold text-brand-secondary">{{ $formatCommissionAmount($commissionLineValue($line)) }}</span>
                                                </div>
                                            @endforeach
                                            @if (isset($section['subtotal']) && $commissionHasAmount($commissionRow, $section['subtotal']['field']))
                                                <div class="mt-4 border-t border-brand-secondary/15 pt-3">
                                                    <div class="flex items-center justify-between gap-4 py-1 text-sm font-semibold text-brand-secondary sm:text-base">
                                                        <span>{{ $section['subtotal']['label'] }}</span>
                                                        <span class="shrink-0">{{ $formatCommissionAmount($commissionRow[$section['subtotal']['field']]) }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @endif
                                    @endforeach
                                </div>
                                <div class="mt-3 border-t-2 border-brand-secondary/15 pt-3">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="font-semibold text-brand-secondary">Comisión final</span>
                                        <span class="shrink-0 text-3xl font-bold tracking-tight text-emerald-800 sm:text-4xl">{{ $formatCommissionAmount($commissionRow['final_commission']) }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (($commissionMode === 'comercial' && $operationGroups->isNotEmpty()) || $hasSharedDetails || $hasOtherDetailGroups)
                            <details x-data="{ open: false }" x-on:toggle="open = $event.target.open" class="group mt-4 rounded-2xl border border-brand-secondary/10 bg-white">
                                <summary :aria-expanded="open ? 'true' : 'false'" class="flex cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 font-semibold text-brand-secondary marker:hidden">
                                    <span>Ver detalle</span>
                                    <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 shrink-0 text-brand-secondary/45 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </summary>
                                <div class="space-y-3 border-t border-brand-secondary/10 p-4">
                                    <p class="text-sm text-brand-secondary/65">El detalle explica el origen de los importes y no se suma de nuevo a la comisión final.</p>
                                    @if ($commissionMode === 'comercial' && $operationGroups->isNotEmpty())
                                        <h3 class="pt-1 text-sm font-semibold text-brand-secondary">Operaciones</h3>
                                        @foreach ($operationGroups as $operationGroup)
                                        @php
                                            $firstOperation = $operationGroup->first();
                                            $operationReason = filled($firstOperation['reason'] ?? null)
                                                ? (strcasecmp(trim((string) $firstOperation['reason']), 'Venta compartida') === 0
                                                    ? 'Venta compartida propia'
                                                    : $firstOperation['reason'])
                                                : ($operationTypeLabels[strtolower((string) ($firstOperation['type'] ?? ''))] ?? 'Operación');
                                            $operationCount = $operationGroup->count();
                                            $operationTotal = $operationGroup->sum(fn (array $operation): float => (float) $operation['commission_amount']);
                                            $operationLabel = $operationReason . ($operationCount > 1 ? ' × ' . $operationCount : '');
                                        @endphp
                                        <details x-data="{ open: false }" x-on:toggle="open = $event.target.open" class="group rounded-xl border border-brand-secondary/10 bg-slate-50">
                                            <summary :aria-expanded="open ? 'true' : 'false'" class="grid cursor-pointer list-none grid-cols-[minmax(0,1fr)_6.5rem_1.25rem] items-center gap-3 px-3 py-3 text-sm font-semibold text-brand-secondary marker:hidden sm:grid-cols-[minmax(0,1fr)_8rem_1.25rem] sm:text-base">
                                                <span class="min-w-0">{{ $operationLabel }}</span>
                                                <span class="w-full text-right">{{ $formatCommissionAmount($operationTotal) }}</span>
                                                <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 justify-self-end text-brand-secondary/45 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                                </svg>
                                            </summary>
                                            <div class="border-t border-brand-secondary/10 bg-white p-3">
                                                <div class="hidden overflow-x-auto md:block">
                                                    <table class="min-w-full text-left text-sm">
                                                        <tbody class="divide-y divide-brand-secondary/10">
                                                            @foreach ($operationGroup as $operation)
                                                                <tr>
                                                                    @if (filled($operation['reason'] ?? null))
                                                                        <td class="px-3 py-2"><span class="block text-xs text-brand-secondary/55">Motivo</span>{{ $operation['reason'] }}</td>
                                                                    @endif
                                                                    @if (filled($operation['type'] ?? null))
                                                                        <td class="px-3 py-2"><span class="block text-xs text-brand-secondary/55">Tipo de operación</span>{{ $operationTypeLabels[strtolower((string) $operation['type'])] ?? 'Operación' }}</td>
                                                                    @endif
                                                                    @if (filled($operation['cv_signed_date'] ?? null))
                                                                        <td class="px-3 py-2"><span class="block text-xs text-brand-secondary/55">Fecha</span>{{ $formatOperationDate($operation['cv_signed_date']) }}</td>
                                                                    @endif
                                                                    @if (filled($operation['vehicle_plate'] ?? null))
                                                                        <td class="px-3 py-2"><span class="block text-xs text-brand-secondary/55">Matrícula</span>{{ $operation['vehicle_plate'] }}</td>
                                                                    @endif
                                                                    @if (filled($operation['opportunity_name'] ?? null))
                                                                        <td class="px-3 py-2"><span class="block text-xs text-brand-secondary/55">Operación</span>{{ $operation['opportunity_name'] }}</td>
                                                                    @endif
                                                                    <td class="whitespace-nowrap px-3 py-2"><span class="block text-xs text-brand-secondary/55">Importe</span>{{ $formatCommissionAmount($operation['commission_amount']) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <div class="space-y-2 md:hidden">
                                                    @foreach ($operationGroup as $operation)
                                                        <div class="rounded-xl bg-slate-50 p-3">
                                                            @if (filled($operation['reason'] ?? null))
                                                                <div class="flex items-start justify-between gap-3 py-1 text-sm"><span class="text-brand-secondary/60">Motivo</span><span class="text-right font-medium text-brand-secondary">{{ $operation['reason'] }}</span></div>
                                                            @endif
                                                            @if (filled($operation['type'] ?? null))
                                                                <div class="flex items-start justify-between gap-3 py-1 text-sm"><span class="text-brand-secondary/60">Tipo de operación</span><span class="text-right font-medium text-brand-secondary">{{ $operationTypeLabels[strtolower((string) $operation['type'])] ?? 'Operación' }}</span></div>
                                                            @endif
                                                            @if (filled($operation['cv_signed_date'] ?? null))
                                                                <div class="flex items-start justify-between gap-3 py-1 text-sm"><span class="text-brand-secondary/60">Fecha</span><span class="text-right font-medium text-brand-secondary">{{ $formatOperationDate($operation['cv_signed_date']) }}</span></div>
                                                            @endif
                                                            @if (filled($operation['vehicle_plate'] ?? null))
                                                                <div class="flex items-start justify-between gap-3 py-1 text-sm"><span class="text-brand-secondary/60">Matrícula</span><span class="text-right font-medium text-brand-secondary">{{ $operation['vehicle_plate'] }}</span></div>
                                                            @endif
                                                            @if (filled($operation['opportunity_name'] ?? null))
                                                                <div class="flex items-start justify-between gap-3 py-1 text-sm"><span class="text-brand-secondary/60">Operación</span><span class="text-right font-medium text-brand-secondary">{{ $operation['opportunity_name'] }}</span></div>
                                                            @endif
                                                            <div class="flex items-start justify-between gap-3 py-1 text-sm"><span class="text-brand-secondary/60">Importe</span><span class="text-right font-medium text-brand-secondary">{{ $formatCommissionAmount($operation['commission_amount']) }}</span></div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </details>
                                        @endforeach
                                    @endif
                                @if ($sharedGroups->isNotEmpty())
                                    @foreach ($sharedGroups as $sharedGroup)
                                        @php
                                            $sharedCount = $sharedGroup->count();
                                            $sharedTotal = $sharedGroup->sum(fn (array $shared): float => (float) $shared['amount']);
                                            $sharedLabel = 'Comisión por venta de otro comercial' . ($sharedCount > 1 ? ' × ' . $sharedCount : '');
                                        @endphp
                                        <details x-data="{ open: false }" x-on:toggle="open = $event.target.open" class="group rounded-xl border border-brand-secondary/10 bg-slate-50">
                                            <summary :aria-expanded="open ? 'true' : 'false'" class="grid cursor-pointer list-none grid-cols-[minmax(0,1fr)_6.5rem_1.25rem] items-center gap-3 px-3 py-3 text-sm font-semibold text-brand-secondary marker:hidden sm:grid-cols-[minmax(0,1fr)_8rem_1.25rem] sm:text-base">
                                                <span class="min-w-0">{{ $sharedLabel }}</span>
                                                <span class="w-full text-right">{{ $formatCommissionAmount($sharedTotal) }}</span>
                                                <svg :class="open ? 'rotate-180' : ''" class="h-4 w-4 justify-self-end text-brand-secondary/45 transition-transform duration-200" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                                </svg>
                                            </summary>
                                            <div class="grid gap-2 border-t border-brand-secondary/10 bg-white p-3 sm:grid-cols-2">
                                                @foreach ($sharedGroup as $sharedRow)
                                                    <div class="rounded-xl bg-slate-50 p-3 text-sm">
                                                        @if (filled($sharedRow['operation'] ?? null))
                                                            <p><span class="text-brand-secondary/60">Operación:</span> {{ $sharedRow['operation'] }}</p>
                                                        @endif
                                                        @if (filled($sharedRow['commercial'] ?? null))
                                                            <p><span class="text-brand-secondary/60">Comercial implicado:</span> {{ $sharedRow['commercial'] }}</p>
                                                        @elseif (filled($sharedRow['commercial_name'] ?? null))
                                                            <p><span class="text-brand-secondary/60">Comercial implicado:</span> {{ $sharedRow['commercial_name'] }}</p>
                                                        @endif
                                                        <p class="mt-1 font-semibold text-brand-secondary">Importe: {{ $formatCommissionAmount($sharedRow['amount']) }}</p>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endforeach
                                @endif
                        @php
                            $availableDetailGroups = collect($detailDefinitions)->filter(function (array $definition, string $detailKey) use ($commissionDetails): bool {
                                if ($detailKey === 'shared') {
                                    return false;
                                }

                                $rows = $commissionDetails[$detailKey] ?? null;
                                if (! is_array($rows)) {
                                    return false;
                                }
                                $rows = array_is_list($rows) ? $rows : [$rows];

                                return collect($rows)->contains(function (mixed $row) use ($definition): bool {
                                    return is_array($row) && array_key_exists($definition['amount'], $row)
                                        && $row[$definition['amount']] !== null && is_numeric($row[$definition['amount']])
                                        && (float) $row[$definition['amount']] !== 0.0;
                                });
                            });
                        @endphp

                        @if ($availableDetailGroups->isNotEmpty())
                            <div class="space-y-4 pt-2">
                                    @foreach ($availableDetailGroups as $detailKey => $definition)
                                        @php
                                            $detailRows = $commissionDetails[$detailKey];
                                            $detailRows = array_is_list($detailRows) ? $detailRows : [$detailRows];
                                        @endphp
                                        <section>
                                            <h3 class="mb-2 text-sm font-semibold text-brand-secondary">{{ $definition['title'] }}</h3>
                                            <div class="hidden overflow-x-auto md:block">
                                                <table class="min-w-full text-left text-sm">
                                                    <tbody class="divide-y divide-brand-secondary/10">
                                                        @foreach ($detailRows as $detailRow)
                                                            @if (is_array($detailRow) && array_key_exists($definition['amount'], $detailRow) && $detailRow[$definition['amount']] !== null && is_numeric($detailRow[$definition['amount']]) && (float) $detailRow[$definition['amount']] !== 0.0)
                                                                <tr>
                                                                    @foreach ($definition['fields'] as $field => $label)
                                                                        @if (array_key_exists($field, $detailRow) && $detailRow[$field] !== null && $detailRow[$field] !== '')
                                                                            <td class="whitespace-nowrap px-3 py-2 align-top">
                                                                                <span class="block text-xs text-brand-secondary/55">{{ $label }}</span>
                                                                                <span class="font-medium text-brand-secondary">{{ $field === $definition['amount'] ? $formatCommissionAmount($detailRow[$field]) : $detailRow[$field] }}</span>
                                                                            </td>
                                                                        @endif
                                                                    @endforeach
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="space-y-2 md:hidden">
                                                @foreach ($detailRows as $detailRow)
                                                    @if (is_array($detailRow) && array_key_exists($definition['amount'], $detailRow) && $detailRow[$definition['amount']] !== null && is_numeric($detailRow[$definition['amount']]) && (float) $detailRow[$definition['amount']] !== 0.0)
                                                        <div class="rounded-xl bg-slate-50 p-3">
                                                            @foreach ($definition['fields'] as $field => $label)
                                                                @if (array_key_exists($field, $detailRow) && $detailRow[$field] !== null && $detailRow[$field] !== '')
                                                                    <div class="flex items-start justify-between gap-3 py-1 text-sm">
                                                                        <span class="text-brand-secondary/60">{{ $label }}</span>
                                                                        <span class="text-right font-medium text-brand-secondary">{{ $field === $definition['amount'] ? $formatCommissionAmount($detailRow[$field]) : $detailRow[$field] }}</span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                                </div>
                                </details>
                    @endif
                    @endif
                </section>
            @endif
        </section>

        <div
            x-cloak
            x-show="isImageOpen"
            x-transition.opacity
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 px-6 py-8 backdrop-blur-sm"
            @click.self="closeImage()"
        >
            <div class="inline-flex max-w-[calc(100vw-3rem)] flex-col items-center">
                <div
                    x-ref="imageViewport"
                    class="relative touch-none overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl"
                    :class="imageScale > 1 ? (isDragging ? 'cursor-grabbing' : 'cursor-grab') : 'cursor-zoom-in'"
                    @wheel.prevent="handleWheel($event)"
                    @pointerdown="handlePointerDown($event)"
                    @pointermove="handlePointerMove($event)"
                    @pointerup="handlePointerUp($event)"
                    @pointercancel="handlePointerCancel($event)"
                >
                    <button
                        type="button"
                        @pointerdown.stop
                        @click.stop="closeImage()"
                        class="absolute right-3 top-3 z-10 inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Cerrar imagen ampliada"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <img
                        :src="imageUrl"
                        :alt="imageAlt"
                        @dblclick="toggleZoom($event.clientX, $event.clientY)"
                        draggable="false"
                        @dragstart.prevent
                        class="block max-h-[80vh] w-auto max-w-[calc(100vw-3rem)] select-none object-contain bg-white will-change-transform"
                        :class="isDragging ? 'transition-none' : 'transition-transform duration-200'"
                        :style="`transform: translate3d(${translateX}px, ${translateY}px, 0) scale(${imageScale}); transform-origin: center center;`"
                    >
                </div>

                <div class="mt-4 flex items-center justify-center gap-2">
                    <button
                        type="button"
                        @click="zoomOut()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Reducir zoom"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12H4" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="resetZoom()"
                        class="inline-flex h-10 min-w-20 items-center justify-center rounded-full bg-white/90 px-3 text-sm font-semibold text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Restablecer zoom"
                    >
                        <span x-text="`${imageScale.toFixed(2).replace(/\.00$/, '')}x`"></span>
                    </button>
                    <button
                        type="button"
                        @click="downloadImage()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Descargar imagen"
                    >
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-5 w-5">
                            <path d="M12.5535 16.5061C12.4114 16.6615 12.2106 16.75 12 16.75C11.7894 16.75 11.5886 16.6615 11.4465 16.5061L7.44648 12.1311C7.16698 11.8254 7.18822 11.351 7.49392 11.0715C7.79963 10.792 8.27402 10.8132 8.55352 11.1189L11.25 14.0682V3C11.25 2.58579 11.5858 2.25 12 2.25C12.4142 2.25 12.75 2.58579 12.75 3V14.0682L15.4465 11.1189C15.726 10.8132 16.2004 10.792 16.5061 11.0715C16.8118 11.351 16.833 11.8254 16.5535 12.1311L12.5535 16.5061Z" fill="#1C274C"/>
                            <path d="M3.75 15C3.75 14.5858 3.41422 14.25 3 14.25C2.58579 14.25 2.25 14.5858 2.25 15V15.0549C2.24998 16.4225 2.24996 17.5248 2.36652 18.3918C2.48754 19.2919 2.74643 20.0497 3.34835 20.6516C3.95027 21.2536 4.70814 21.5125 5.60825 21.6335C6.47522 21.75 7.57754 21.75 8.94513 21.75H15.0549C16.4225 21.75 17.5248 21.75 18.3918 21.6335C19.2919 21.5125 20.0497 21.2536 20.6517 20.6516C21.2536 20.0497 21.5125 19.2919 21.6335 18.3918C21.75 17.5248 21.75 16.4225 21.75 15.0549V15C21.75 14.5858 21.4142 14.25 21 14.25C20.5858 14.25 20.25 14.5858 20.25 15C20.25 16.4354 20.2484 17.4365 20.1469 18.1919C20.0482 18.9257 19.8678 19.3142 19.591 19.591C19.3142 19.8678 18.9257 20.0482 18.1919 20.1469C17.4365 20.2484 16.4354 20.25 15 20.25H9C7.56459 20.25 6.56347 20.2484 5.80812 20.1469C5.07435 20.0482 4.68577 19.8678 4.40901 19.591C4.13225 19.3142 3.9518 18.9257 3.85315 18.1919C3.75159 17.4365 3.75 16.4354 3.75 15Z" fill="#1C274C"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="zoomIn()"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-brand-secondary shadow-lg transition hover:bg-white"
                        aria-label="Aumentar zoom"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </div>

                <p class="mt-4 text-center text-sm font-medium text-white/80" x-text="imageTitle || @js($user->name)">
                </p>
            </div>
        </div>
    </main>
@endsection
