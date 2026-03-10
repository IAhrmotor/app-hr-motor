@extends('layouts.app')

@section('content')
    <div
        x-data='{
            search: "",
            debouncedSearch: "",
            isSearching: false,
            searchTimeout: null,
            videos: @json($videos),

            init() {
                this.debouncedSearch = this.search;

                this.$watch("search", (value) => {
                    this.isSearching = true;

                    clearTimeout(this.searchTimeout);

                    this.searchTimeout = setTimeout(() => {
                        this.debouncedSearch = value;
                        this.isSearching = false;
                    }, 250);
                });
            },

            matches(video) {
                const term = this.debouncedSearch.toLowerCase().trim();

                if (!term) return true;

                return video.title.toLowerCase().includes(term);
            },

            get filteredVideos() {
                return this.videos.filter(video => this.matches(video));
            }
        }'
    >
        <section
            class="relative overflow-hidden"
            style="background-image: url('{{ asset('images/hero/hero-videos.jpg') }}'); background-size: cover; background-position: center;"
        >
            <div class="absolute inset-0 bg-black/55"></div>

            <div class="relative mx-auto max-w-7xl px-6 py-6 sm:py-8 lg:px-8 lg:py-10">
                <div class="max-w-3xl">
                    <span
                        class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.12em] text-white backdrop-blur-sm"
                    >
                        Biblioteca interna
                    </span>

                    <h1 class="mt-3 text-2xl font-bold tracking-tight text-white md:text-3xl lg:text-4xl">
                        Vídeos de formación
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-6 text-white/85 md:text-base">
                        Accede a contenidos de apoyo sobre herramientas, procesos y formación del equipo comercial.
                    </p>

                    <div class="mt-5 max-w-xl">
                        <label for="video-search" class="sr-only">
                            Buscar vídeos
                        </label>

                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="h-5 w-5 text-white/70"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m21 21-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"
                                    />
                                </svg>
                            </div>

                            <input
                                id="video-search"
                                x-model="search"
                                type="text"
                                placeholder="Buscar por título..."
                                class="w-full rounded-2xl border border-white/15 bg-white/10 py-2.5 pl-12 pr-4 text-sm text-white placeholder:text-white/65 backdrop-blur-md outline-none transition focus:border-white/30 focus:bg-white/15"
                            >
                        </div>

                        <p class="mt-2 text-sm text-white/75">
                            <span x-show="!isSearching && search.trim() === ''">
                                Mostrando todos los vídeos.
                            </span>

                            <span x-show="!isSearching && search.trim() !== ''">
                                Resultados encontrados:
                                <span x-text="filteredVideos.length"></span>
                            </span>

                            <span x-show="isSearching" x-cloak class="inline-flex items-center gap-2">
                                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"></span>
                                Buscando vídeos...
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <main class="mx-auto max-w-7xl px-6 py-10 lg:px-8">
            <section>
                <div x-show="isSearching" x-cloak class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                    <template x-for="index in 3" :key="'skeleton-' + index">
                        <article class="overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm">
                            <div class="aspect-video animate-pulse bg-slate-200"></div>

                            <div class="border-t border-brand-secondary/10 px-4 py-4">
                                <div class="mx-auto h-5 w-3/4 animate-pulse rounded bg-slate-200"></div>
                            </div>
                        </article>
                    </template>
                </div>

                <template x-if="!isSearching && filteredVideos.length > 0">
                    <div class="grid gap-8 md:grid-cols-2 xl:grid-cols-3">
                        <template x-for="video in filteredVideos" :key="video.youtube_id">
                            <article
                                class="overflow-hidden rounded-2xl border border-brand-secondary/10 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md"
                            >
                                <div class="aspect-video">
                                    <iframe
                                        class="h-full w-full"
                                        :src="'https://www.youtube.com/embed/' + video.youtube_id"
                                        :title="video.title"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                        allowfullscreen
                                    ></iframe>
                                </div>

                                <div class="border-t border-brand-secondary/10 px-4 py-4">
                                    <h2
                                        class="text-center text-sm font-semibold text-brand-secondary md:text-base"
                                        x-text="video.title"
                                    ></h2>
                                </div>
                            </article>
                        </template>
                    </div>
                </template>

                <template x-if="!isSearching && filteredVideos.length === 0">
                    <div class="rounded-3xl border border-brand-secondary/10 bg-white p-10 text-center shadow-sm">
                        <h2 class="text-xl font-semibold text-brand-secondary">
                            No se han encontrado vídeos
                        </h2>

                        <p class="mt-3 text-sm text-brand-secondary/70 md:text-base">
                            Prueba con otro término de búsqueda.
                        </p>
                    </div>
                </template>
            </section>
        </main>
    </div>
@endsection