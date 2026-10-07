{{--
    Page data passed through @extends('layouts.app', [...]):
    - title (required), description (required)
    - ogTitle, ogDescription, ogImage (optional, path relative to public/)
--}}
@use('App\Support\Seo')
@use('App\Support\StructuredData')
@php
    $canonicalUrl = Seo::canonical();
    $ogTitle ??= $title;
    $ogDescription ??= $description;
    $ogImageUrl = Seo::url($ogImage ?? config('business.og_image'));

    // On the homepage, keep section links as pure fragments for in-page scrolling.
    $homeUrl = request()->routeIs('home') ? '' : route('home');

    $navigationLinks = [
        'home' => 'Accueil',
        'services' => 'Services',
        'offers' => 'Offres',
        'domiciliation.marrakech' => 'Marrakech',
        'domiciliation.casablanca' => 'Casablanca',
    ];
@endphp
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title }}</title>
        <meta name="description" content="{{ $description }}">
        <meta name="keywords" content="{{ implode(', ', config('seo.keywords')) }}">
        <meta name="robots" content="{{ config('seo.indexing_enabled') ? 'index, follow' : 'noindex, nofollow' }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <link rel="icon" type="image/jpeg" href="{{ asset('nesel-logo.jpeg') }}?v=2">
        <link rel="apple-touch-icon" href="{{ asset('nesel-logo.jpeg') }}?v=2">

        <meta property="og:type" content="website">
        <meta property="og:locale" content="fr_MA">
        <meta property="og:site_name" content="{{ config('business.name') }}">
        <meta property="og:title" content="{{ $ogTitle }}">
        <meta property="og:description" content="{{ $ogDescription }}">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $ogImageUrl }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $ogTitle }}">
        <meta name="twitter:description" content="{{ $ogDescription }}">
        <meta name="twitter:image" content="{{ $ogImageUrl }}">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <x-json-ld :data="StructuredData::organization()" />
        @stack('structured-data')

        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-18474666544"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());
            gtag('config', 'AW-18474666544');
        </script>
    </head>
    <body class="bg-nesel-ivory text-nesel-navy antialiased">
        <header class="sticky top-0 z-50 border-b border-slate-200 bg-white/95 backdrop-blur-sm">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 sm:px-8 lg:px-10">
                <a href="{{ $homeUrl }}#accueil" class="flex items-center gap-3" aria-label="Nesel, retour à l'accueil">
                    <span class="flex size-12 items-center justify-center overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <x-picture :src="config('business.logo')" alt="" width="48" height="48" class="size-12 object-cover" />
                    </span>
                    <span>
                        <span class="block text-xl font-black tracking-[-0.05em] text-nesel-navy">NESEL</span>
                        <span class="block text-[9px] font-bold uppercase tracking-[0.24em] text-slate-500">Business address</span>
                    </span>
                </a>

                <nav class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex" aria-label="Navigation principale">
                    @foreach ($navigationLinks as $routeName => $label)
                        <a href="{{ route($routeName) }}" @if (request()->routeIs($routeName)) aria-current="page" @endif @class(['transition hover:text-nesel-red', 'text-nesel-red' => request()->routeIs($routeName)])>{{ $label }}</a>
                    @endforeach
                    <a href="{{ $homeUrl }}#contact" class="inline-flex min-h-11 items-center justify-center rounded-md bg-nesel-red px-5 text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-nesel-red focus:ring-offset-2">Contact</a>
                </nav>

                <button id="menu-toggle" type="button" class="flex size-11 items-center justify-center rounded-md border border-slate-200 text-nesel-navy lg:hidden" aria-expanded="false" aria-controls="mobile-menu" aria-label="Ouvrir le menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round" />
                    </svg>
                </button>
            </div>

            <nav id="mobile-menu" class="hidden border-t border-slate-200 bg-white px-5 py-5 lg:hidden" aria-label="Navigation mobile">
                <div class="mx-auto flex max-w-7xl flex-col gap-1 text-sm font-semibold">
                    @foreach ($navigationLinks as $routeName => $label)
                        <a href="{{ route($routeName) }}" @if (request()->routeIs($routeName)) aria-current="page" @endif @class(['rounded-md px-3 py-3 hover:bg-slate-50', 'text-nesel-red' => request()->routeIs($routeName)])>{{ $label }}</a>
                    @endforeach
                    <a href="{{ $homeUrl }}#contact" class="mt-2 rounded-md bg-nesel-red px-4 py-3 text-center text-white">Contact</a>
                </div>
            </nav>
        </header>

        <main>
            @yield('content')
        </main>

        <footer class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-5 py-8 text-sm text-slate-500 sm:px-8 lg:px-10">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-3">
                        <x-picture :src="config('business.logo')" alt="Logo Nesel" width="36" height="36" class="size-9 rounded-md object-cover" loading="lazy" />
                        <strong class="text-nesel-navy">NESEL</strong>
                        <span>· Domiciliation d’entreprises</span>
                    </div>
                    <nav aria-label="Liens de pied de page">
                        <ul class="flex flex-wrap gap-x-6 gap-y-2 font-semibold">
                            <li><a href="{{ route('home') }}" class="transition hover:text-nesel-red">Accueil</a></li>
                            <li><a href="{{ route('services') }}" class="transition hover:text-nesel-red">Services</a></li>
                            <li><a href="{{ route('offers') }}" class="transition hover:text-nesel-red">Offres</a></li>
                            <li><a href="{{ route('domiciliation.marrakech') }}" class="transition hover:text-nesel-red">Domiciliation à Marrakech</a></li>
                            <li><a href="{{ route('domiciliation.casablanca') }}" class="transition hover:text-nesel-red">Domiciliation à Casablanca</a></li>
                        </ul>
                    </nav>
                    <p>© {{ date('Y') }} Nesel. Marrakech · Casablanca</p>
                </div>
                <p class="mt-6 border-t border-slate-200 pt-5 leading-6 text-slate-600">{{ config('business.private_company_disclaimer') }}</p>
            </div>
        </footer>

        @if (session('contact_success'))
            <div id="form-success" class="fixed bottom-5 left-5 right-5 z-[60] border-l-4 border-emerald-500 bg-nesel-navy px-5 py-4 text-sm font-semibold text-white shadow-2xl sm:left-auto sm:w-[420px]" role="status" aria-live="polite">
                {{ session('contact_success') }}
            </div>
        @endif
    </body>
</html>
