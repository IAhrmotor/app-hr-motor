@php
    $navItems = config('navigation.main', []);
    $authUser = auth()->user();
    $forumUnreadNotifications = $authUser
        ? $authUser->unreadNotifications()
            ->get()
            ->sortByDesc(function ($notification) {
                $priority = data_get($notification->data, 'priority', false) ? 1 : 0;
                $createdAt = $notification->created_at?->timestamp ?? 0;

                return sprintf('%d-%010d', $priority, $createdAt);
            })
            ->take(8)
        : collect();
    $forumUnreadNotificationCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
@endphp
@php
    $navItemClass = 'inline-flex h-10 items-center whitespace-nowrap px-2 text-sm font-medium leading-none transition';
    $navItemActiveClass = 'text-brand-primary';
    $navItemInactiveClass = 'text-gray-700 hover:text-gray-900';
@endphp

<nav x-data="{ open: false, profileOpen: false, notificationsOpen: false, activeDropdown: null }" @keydown.escape.window="profileOpen = false; notificationsOpen = false; activeDropdown = null"
    class="sticky top-0 z-50 border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center">
            <img src="{{ asset('images/logo-horizontal.svg') }}" alt="HR Motor" class="h-10 w-auto">
        </a>

        <div class="flex items-center gap-2 md:gap-6">
            <div class="hidden items-center gap-8 md:flex">
                @foreach ($navItems as $item)
                    @if (! empty($item['children']))
                        @php
                            $dropdownKey = 'nav-' . \Illuminate\Support\Str::slug($item['label']);
                            $isParentActive = collect($item['children'])->contains(fn ($child) => request()->routeIs($child['route']));
                        @endphp
                        <div class="relative" @mouseenter="activeDropdown = '{{ $dropdownKey }}'" @mouseleave="activeDropdown = null">
                            <button type="button"
                                class="{{ $navItemClass }} gap-1.5 {{ $isParentActive ? $navItemActiveClass : $navItemInactiveClass }}"
                                :aria-expanded="(activeDropdown === '{{ $dropdownKey }}').toString()">
                                <span>{{ $item['label'] }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition"
                                    :class="{ 'rotate-180': activeDropdown === '{{ $dropdownKey }}' }" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            <div x-show="activeDropdown === '{{ $dropdownKey }}'" x-cloak class="absolute left-0 top-full h-3 w-full"></div>

                            <div x-show="activeDropdown === '{{ $dropdownKey }}'" x-cloak x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 translate-y-1"
                                class="absolute left-1/2 top-full mt-2 w-60 -translate-x-1/2 overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-xl">
                                <div class="p-2">
                                    @foreach ($item['children'] as $child)
                                        @php $isChildActive = request()->routeIs($child['route']); @endphp
                                        <a href="{{ route($child['route']) }}"
                                            class="block rounded-xl px-4 py-3 text-sm font-medium {{ $isChildActive ? 'text-brand-primary' : 'text-brand-secondary transition hover:text-brand-primary' }}">
                                            {{ $child['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        @php
                            $isItemActive = $item['route'] === 'agenda.index'
                                ? request()->routeIs('agenda.index', 'agenda.contacts.*')
                                : request()->routeIs($item['route']);
                        @endphp
                        <a href="{{ route($item['route']) }}"
                            class="{{ $navItemClass }} {{ $isItemActive ? $navItemActiveClass : $navItemInactiveClass }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
                @auth
                    @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                        @php
                            $isAdminActive = request()->routeIs('admin.index');
                            $isAdminLogsActive = request()->routeIs('admin.logs.*');
                            $isContentLogsActive = request()->routeIs('admin.content-logs.*');
                            $isUsersActive = request()->routeIs('users.*');
                            $isDealershipsActive = request()->routeIs('dealerships.*');
                            $isMagazineActive = request()->routeIs('admin.magazine.*');
                            $isContactsActive = request()->routeIs('admin.contacts.*');
                        @endphp
                        <a href="{{ route('admin.index') }}"
                            class="{{ $navItemClass }} px-1 font-semibold {{ $isAdminActive || $isAdminLogsActive || $isContentLogsActive || $isUsersActive || $isDealershipsActive || $isMagazineActive || $isContactsActive ? $navItemActiveClass : $navItemInactiveClass }}">
                            Admin
                        </a>
                    @endif
                @endauth
            </div>

            @auth
                <div class="relative" @click.outside="notificationsOpen = false">
                    <button type="button" @click="notificationsOpen = !notificationsOpen; profileOpen = false"
                        class="relative inline-flex h-10 w-10 cursor-pointer items-center justify-center rounded-lg text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                        aria-label="Ver notificaciones" :aria-expanded="notificationsOpen.toString()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0018 9.75v-.7V9a6 6 0 10-12 0v.05-.001v.701a8.967 8.967 0 00-2.312 6.022c1.733.64 3.561 1.083 5.454 1.31m5.715 0a24.255 24.255 0 01-5.715 0m5.715 0a3 3 0 11-5.715 0" />
                        </svg>

                        @if ($forumUnreadNotificationCount > 0)
                            <span
                                class="absolute -right-0.5 -top-0.5 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[11px] font-semibold leading-none text-white shadow-sm ring-2 ring-white">
                                {{ $forumUnreadNotificationCount > 9 ? '+9' : $forumUnreadNotificationCount }}
                            </span>
                        @endif
                    </button>

                    <div x-show="notificationsOpen" x-cloak x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute right-0 top-full mt-3 w-80 overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-xl sm:w-96">
                        <div class="flex items-center justify-between border-b border-brand-secondary/10 px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-brand-secondary">Notificaciones</p>
                            </div>
                        </div>

                        <div class="max-h-[28rem] overflow-y-auto p-2">
                            @forelse ($forumUnreadNotifications as $notification)
                                @php
                                    $isPriorityNotification = (bool) data_get($notification->data, 'priority', false);
                                    $notificationTitle = data_get($notification->data, 'title', data_get($notification->data, 'message', 'Notificación'));
                                    $notificationDescription = data_get($notification->data, 'description', data_get($notification->data, 'thread_title', ''));
                                    $notificationLinkLabel = data_get($notification->data, 'link_label', 'Abrir');
                                    $notificationLinkLabel = $notificationLinkLabel === 'Abrir enlace' ? 'Abrir' : $notificationLinkLabel;
                                    $notificationLinkUrl = data_get($notification->data, 'link_url', data_get($notification->data, 'thread_url'));
                                @endphp
                                <a href="{{ route('notifications.show', $notification->id) }}"
                                    class="block rounded-2xl px-3 py-3 transition {{ $isPriorityNotification ? 'mb-3 border border-amber-200 bg-amber-50/80 hover:bg-amber-100/80' : 'mb-1 hover:bg-brand-secondary/5' }} last:mb-0">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full {{ $isPriorityNotification ? 'bg-amber-500 text-white shadow-sm' : 'bg-brand-primary/10 text-brand-primary' }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="block h-3.5 w-3.5 shrink-0"
                                                viewBox="0 0 24 24" fill="none">
                                                @if ($isPriorityNotification)
                                                    <path d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z" fill="currentColor"/>
                                                @else
                                                    <path d="M9.15316 5.40838C10.4198 3.13613 11.0531 2 12 2C12.9469 2 13.5802 3.13612 14.8468 5.40837L15.1745 5.99623C15.5345 6.64193 15.7144 6.96479 15.9951 7.17781C16.2757 7.39083 16.6251 7.4699 17.3241 7.62805L17.9605 7.77203C20.4201 8.32856 21.65 8.60682 21.9426 9.54773C22.2352 10.4886 21.3968 11.4691 19.7199 13.4299L19.2861 13.9372C18.8096 14.4944 18.5713 14.773 18.4641 15.1177C18.357 15.4624 18.393 15.8341 18.465 16.5776L18.5306 17.2544C18.7841 19.8706 18.9109 21.1787 18.1449 21.7602C17.3788 22.3417 16.2273 21.8115 13.9243 20.7512L13.3285 20.4768C12.6741 20.1755 12.3469 20.0248 12 20.0248C11.6531 20.0248 11.3259 20.1755 10.6715 20.4768L10.0757 20.7512C7.77268 21.8115 6.62118 22.3417 5.85515 21.7602C5.08912 21.1787 5.21588 19.8706 5.4694 17.2544L5.53498 16.5776C5.60703 15.8341 5.64305 15.4624 5.53586 15.1177C5.42868 14.773 5.19043 14.4944 4.71392 13.9372L4.2801 13.4299C2.60325 11.4691 1.76482 10.4886 2.05742 9.54773C2.35002 8.60682 3.57986 8.32856 6.03954 7.77203L6.67589 7.62805C7.37485 7.4699 7.72433 7.39083 8.00494 7.17781C8.28555 6.96479 8.46553 6.64194 8.82547 5.99623L9.15316 5.40838Z" stroke="currentColor" stroke-width="1.5"/>
                                                @endif
                                            </svg>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-3">
                                                <p class="text-sm font-semibold {{ $isPriorityNotification ? 'text-amber-950' : 'text-brand-secondary' }}">
                                                    {{ $notificationTitle }}
                                                </p>

                                                @if ($isPriorityNotification)
                                                    <span class="inline-flex shrink-0 rounded-full bg-amber-100 px-2 py-1 text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-700">
                                                        Prioritaria
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($notificationDescription)
                                                <p class="mt-1 text-sm {{ $isPriorityNotification ? 'text-amber-900/75' : 'text-brand-secondary/70' }}">
                                                    {{ $notificationDescription }}
                                                </p>
                                            @endif
                                            <p class="mt-1 text-xs text-brand-secondary/40">
                                                {{ $notification->created_at?->diffForHumans() }}
                                            </p>
                                            @if ($notificationLinkUrl)
                                                <span class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold {{ $isPriorityNotification ? 'text-amber-700' : 'text-brand-primary' }}">
                                                    {{ $notificationLinkLabel }}
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                                    </svg>
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div class="rounded-2xl border border-dashed border-brand-secondary/10 bg-slate-50 px-4 py-6 text-center">
                                    <p class="text-sm font-semibold text-brand-secondary">No tienes notificaciones pendientes</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="relative hidden md:block" @click.outside="profileOpen = false">
                    <button type="button" @click="profileOpen = !profileOpen"
                        class="flex cursor-pointer items-center rounded-full bg-white/90 p-0.5 shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md"
                        aria-label="Abrir menu de perfil" :aria-expanded="profileOpen.toString()">
                        <img src="{{ auth()->user()->avatar_url }}" alt="Avatar de {{ auth()->user()->name }}"
                            class="h-10 w-10 rounded-full object-cover">
                    </button>

                    <div x-show="profileOpen" x-cloak x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0 translate-y-1"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-transition:leave="transition ease-in duration-100"
                        x-transition:leave-start="opacity-100 translate-y-0"
                        x-transition:leave-end="opacity-0 translate-y-1"
                        class="absolute right-0 mt-3 w-60 overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-xl">
                        <a href="{{ route('profile.show') }}" @click="profileOpen = false"
                            class="block border-b border-brand-secondary/10 px-4 py-3 transition hover:bg-brand-secondary/5">
                            <p class="text-sm font-semibold text-brand-secondary">{{ auth()->user()->name }}</p>
                            <p class="mt-1 text-xs text-brand-secondary/60">{{ auth()->user()->email }}</p>
                        </a>

                        <div class="p-2">
                            <a href="{{ route('profile.edit') }}" @click="profileOpen = false"
                                class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-medium text-brand-secondary transition hover:bg-brand-secondary/5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-brand-primary" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15.75 6.75a3 3 0 11-6 0 3 3 0 016 0zm-10.5 12a7.5 7.5 0 1115 0" />
                                </svg>
                                <span>Modificar perfil</span>
                            </a>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-3 text-left text-sm font-medium text-red-600 transition hover:bg-red-50">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15" />
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M18 12H9m0 0l3-3m-3 3l3 3" />
                                    </svg>
                                    <span>Cerrar sesion</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endauth

            <button type="button" @click="open = !open"
                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 md:hidden"
                aria-label="Abrir menu de navegacion" :aria-expanded="open.toString()">
                <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>

                <svg x-show="open" x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1" x-cloak class="border-t border-gray-200 bg-white md:hidden">
        <div class="mx-auto max-w-7xl px-6 py-4 lg:px-8">
            @auth
                <a href="{{ route('profile.show') }}" @click="open = false"
                    class="mb-3 flex items-center gap-3 rounded-2xl border border-brand-secondary/10 bg-slate-50 px-3 py-3 text-sm font-medium text-brand-secondary transition hover:bg-brand-secondary/5">
                    <img src="{{ auth()->user()->avatar_url }}" alt="Avatar de {{ auth()->user()->name }}"
                        class="h-11 w-11 rounded-full object-cover ring-1 ring-brand-secondary/10">
                    <div class="min-w-0">
                        <p class="truncate font-semibold">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-brand-secondary/60">Ver perfil</p>
                    </div>
                </a>
            @endauth

            @foreach ($navItems as $item)
                @if (! empty($item['children']))
                    @foreach ($item['children'] as $child)
                        @php $isChildActive = request()->routeIs($child['route']); @endphp
                        <a href="{{ route($child['route']) }}" @click="open = false"
                            class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ $isChildActive ? 'text-brand-primary' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                            {{ $child['label'] }}
                        </a>
                    @endforeach
                @else
                    @php
                        $isItemActive = $item['route'] === 'agenda.index'
                            ? request()->routeIs('agenda.index', 'agenda.contacts.*')
                            : request()->routeIs($item['route']);
                    @endphp
                    <a href="{{ route($item['route']) }}" @click="open = false"
                        class="block rounded-lg px-3 py-2 text-sm font-medium transition {{ $isItemActive ? 'text-brand-primary' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach

            @auth
                @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                    @php
                        $isAdminActive = request()->routeIs('admin.index');
                        $isAdminLogsActive = request()->routeIs('admin.logs.*');
                        $isContentLogsActive = request()->routeIs('admin.content-logs.*');
                        $isUsersActive = request()->routeIs('users.*');
                        $isDealershipsActive = request()->routeIs('dealerships.*');
                        $isMagazineActive = request()->routeIs('admin.magazine.*');
                        $isContactsActive = request()->routeIs('admin.contacts.*');
                    @endphp
                    <a href="{{ route('admin.index') }}" @click="open = false"
                        class="mt-2 block rounded-lg px-3 py-2 text-sm font-medium transition {{ $isAdminActive || $isAdminLogsActive || $isContentLogsActive || $isUsersActive || $isDealershipsActive || $isMagazineActive || $isContactsActive ? 'text-brand-primary' : 'text-gray-700 hover:bg-gray-100 hover:text-gray-900' }}">
                        Admin
                    </a>
                @endif
            @endauth

            <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-gray-200 pt-2">
                @csrf
                <button type="submit" @click="open = false"
                    class="flex w-full cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-red-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-7.5a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 006 21h7.5a2.25 2.25 0 002.25-2.25V15" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 12H9m0 0l3-3m-3 3l3 3" />
                    </svg>
                    <span>Cerrar sesion</span>
                </button>
            </form>
        </div>
    </div>
</nav>
