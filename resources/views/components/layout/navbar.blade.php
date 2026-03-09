@php
    $navItems = [
        [
            'label' => 'Vídeos',
            'route' => 'videos',
        ],
    ];
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-50 border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center">
            <img
                src="{{ asset('images/logo-horizontal.svg') }}"
                alt="HR Motor"
                class="h-10 w-auto"
            >
        </a>

        <div class="flex items-center gap-2 md:gap-6">
            <div class="hidden items-center gap-8 md:flex">
                @foreach ($navItems as $item)
                    <a
                        href="{{ route($item['route']) }}"
                        class="text-sm font-medium text-gray-700 transition hover:text-gray-900"
                    >
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            <button
                type="button"
                class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                aria-label="Ver novedades"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0018 9.75v-.7V9a6 6 0 10-12 0v.05-.001v.701a8.967 8.967 0 00-2.312 6.022c1.733.64 3.561 1.083 5.454 1.31m5.715 0a24.255 24.255 0 01-5.715 0m5.715 0a3 3 0 11-5.715 0"
                    />
                </svg>
            </button>

            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center justify-center rounded-lg p-2 text-gray-700 transition hover:bg-gray-100 hover:text-gray-900 md:hidden"
                aria-label="Abrir menú de navegación"
                :aria-expanded="open.toString()"
            >
                <svg
                    x-show="!open"
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>

                <svg
                    x-show="open"
                    x-cloak
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.8"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6l-12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-cloak
        class="border-t border-gray-200 bg-white md:hidden"
    >
        <div class="mx-auto max-w-7xl px-6 py-4 lg:px-8">
            @foreach ($navItems as $item)
                <a
                    href="{{ route($item['route']) }}"
                    @click="open = false"
                    class="block rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 hover:text-gray-900"
                >
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>
    </div>
</nav>