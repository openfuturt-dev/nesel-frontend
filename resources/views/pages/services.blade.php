@use('App\Support\Catalog')
@use('App\Support\Seo')
@use('App\Support\StructuredData')
@php
    $services = Catalog::services();
    $servicesById = collect($services)->keyBy('id');
    $domiciliation = $servicesById['domiciliation'];
    $courrier = $servicesById['courrier'];
    $reexpedition = $servicesById['reexpedition'];
    $bureaux = $servicesById['bureaux'];
    $supportServices = collect(['creation', 'administratif', 'investisseurs'])
        ->map(fn (string $id): array => $servicesById[$id]);

    $supportIcons = [
        'creation' => 'briefcase',
        'administratif' => 'documents',
        'investisseurs' => 'globe',
    ];

    $breadcrumbs = [
        ['name' => 'Accueil', 'url' => Seo::route('home')],
        ['name' => 'Services', 'url' => Seo::route('services')],
    ];
@endphp

@extends('layouts.app', [
    'title' => 'Services de domiciliation et accompagnement d’entreprise | Nesel',
    'description' => 'Services de domiciliation Nesel à Marrakech et Casablanca : adresse professionnelle, gestion du courrier, bureaux et accompagnement au lancement de votre activité.',
])

@push('structured-data')
    <x-json-ld :data="StructuredData::services($services)" />
@endpush

@section('content')
    <section class="relative overflow-hidden bg-nesel-navy text-white">
        <div class="premium-grid absolute inset-0 opacity-35" aria-hidden="true"></div>
        <div class="absolute -right-36 top-16 size-[34rem] rounded-full border border-white/10" aria-hidden="true"></div>
        <div class="absolute -right-16 top-40 size-72 rounded-full border border-nesel-red/30" aria-hidden="true"></div>

        <div class="relative mx-auto max-w-7xl px-5 py-16 sm:px-8 lg:px-10 lg:py-24 xl:py-28">
            <x-breadcrumb :items="$breadcrumbs" class="mb-12" />

            <div class="grid gap-14 lg:grid-cols-[1.08fr_0.92fr] lg:items-end lg:gap-20">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-nesel-gold">Une expertise à chaque étape</p>
                    <h1 class="mt-5 max-w-4xl text-balance text-4xl font-black leading-[0.98] tracking-[-0.055em] text-white sm:text-5xl xl:text-6xl">
                        Services de domiciliation et d’accompagnement pour votre entreprise
                    </h1>
                    <p class="mt-7 max-w-2xl text-lg leading-8 text-slate-300">
                        Adresse de siège social, gestion du courrier, espaces de travail, accompagnement à la constitution de votre société et suivi de votre dossier : Nesel réunit les services dont votre entreprise a besoin à Marrakech et à Casablanca.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="#domiciliation" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-md bg-nesel-red px-7 text-sm font-bold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-white">
                            Explorer nos services
                            <span aria-hidden="true">↓</span>
                        </a>
                        <a href="{{ route('offers') }}" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-md border border-white/25 px-7 text-sm font-bold text-white transition hover:border-white/60 hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-white">
                            Voir les offres
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>

                <nav aria-label="Catégories de services" class="border border-white/15 bg-white/[0.06] p-6 backdrop-blur-sm sm:p-8">
                    <div class="flex items-end justify-between gap-6 border-b border-white/15 pb-6">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.18em] text-white/45">Notre accompagnement</p>
                            <p class="mt-2 text-2xl font-black tracking-tight">7 expertises coordonnées</p>
                        </div>
                        <span class="text-5xl font-black tracking-[-0.06em] text-white/15" aria-hidden="true">07</span>
                    </div>
                    <ol class="divide-y divide-white/10">
                        <li>
                            <a href="#operations" class="group flex items-center gap-5 py-5">
                                <span class="flex size-10 shrink-0 items-center justify-center border border-white/15 text-xs font-black text-nesel-gold">01</span>
                                <span><strong class="block text-sm text-white">Opérations</strong><span class="mt-1 block text-xs leading-5 text-white/50">Domiciliation, courrier et réexpédition</span></span>
                                <span class="ml-auto transition group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                        <li>
                            <a href="#bureaux" class="group flex items-center gap-5 py-5">
                                <span class="flex size-10 shrink-0 items-center justify-center border border-white/15 text-xs font-black text-nesel-gold">02</span>
                                <span><strong class="block text-sm text-white">Espaces professionnels</strong><span class="mt-1 block text-xs leading-5 text-white/50">Bureaux, réunions et coworking</span></span>
                                <span class="ml-auto transition group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                        <li>
                            <a href="#accompagnement" class="group flex items-center gap-5 py-5">
                                <span class="flex size-10 shrink-0 items-center justify-center border border-white/15 text-xs font-black text-nesel-gold">03</span>
                                <span><strong class="block text-sm text-white">Accompagnement business</strong><span class="mt-1 block text-xs leading-5 text-white/50">Lancement, administration et investisseurs</span></span>
                                <span class="ml-auto transition group-hover:translate-x-1" aria-hidden="true">→</span>
                            </a>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </section>

    <section id="domiciliation" class="scroll-mt-24 bg-white py-20 sm:py-28 lg:py-32">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[1.05fr_0.95fr] lg:items-center lg:gap-24 lg:px-10">
            <div data-reveal>
                <div class="flex items-center gap-4">
                    <span class="flex size-12 items-center justify-center bg-nesel-red text-white"><x-service-icon name="building" class="size-6" /></span>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-nesel-red">01 · Service fondamental</p>
                </div>
                <h2 class="mt-7 max-w-2xl text-balance text-4xl font-black leading-[1.02] tracking-[-0.045em] text-nesel-navy sm:text-5xl">{{ $domiciliation['title'] }}</h2>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-600">{{ $domiciliation['intro'] }}</p>
                <p class="mt-6 max-w-xl text-base leading-7 text-slate-600">
                    En savoir plus sur la <a href="{{ route('domiciliation.marrakech') }}" class="font-bold text-nesel-red underline decoration-nesel-red/30 underline-offset-4 transition hover:decoration-nesel-red">domiciliation à Marrakech</a>
                    ou la <a href="{{ route('domiciliation.casablanca') }}" class="font-bold text-nesel-red underline decoration-nesel-red/30 underline-offset-4 transition hover:decoration-nesel-red">domiciliation à Casablanca</a>.
                </p>
            </div>

            <div class="relative" data-reveal>
                <div class="absolute -left-5 -top-5 size-24 border-l border-t border-nesel-red/30" aria-hidden="true"></div>
                <div class="relative border border-slate-200 bg-nesel-ivory p-7 shadow-[0_28px_90px_rgba(6,24,50,0.09)] sm:p-10">
                    <div class="flex items-center justify-between gap-6 border-b border-slate-200 pb-6">
                        <p class="text-xs font-black uppercase tracking-[0.18em] text-slate-500">Votre socle administratif</p>
                        <span class="text-4xl font-black tracking-[-0.06em] text-slate-200" aria-hidden="true">01</span>
                    </div>
                    <ul class="divide-y divide-slate-200">
                        @foreach ($domiciliation['items'] as $item)
                            <li class="flex gap-4 py-5 text-sm font-bold leading-6 text-nesel-navy">
                                <span class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-nesel-red text-xs text-white" aria-hidden="true">✓</span>
                                {{ $item }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section id="operations" class="scroll-mt-24 bg-nesel-ivory py-20 sm:py-28 lg:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-8 lg:grid-cols-[0.72fr_1.28fr] lg:items-end lg:gap-16">
                <x-section-heading eyebrow="Opérations" title="Votre courrier reste sous contrôle." description="De la réception à la transmission, Nesel organise chaque étape avec méthode et confidentialité." />
                <p class="max-w-xl border-l border-slate-300 pl-6 text-sm leading-7 text-slate-500 lg:justify-self-end">Une continuité opérationnelle pensée pour les dirigeants mobiles, les équipes à distance et les entreprises tournées vers l’international.</p>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-[1.12fr_0.88fr]">
                <article id="courrier" class="group scroll-mt-24 border border-slate-200 bg-white p-7 shadow-[0_18px_60px_rgba(6,24,50,0.06)] transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-[0_24px_70px_rgba(6,24,50,0.1)] sm:p-10" data-reveal>
                    <div class="flex items-start justify-between gap-6">
                        <span class="flex size-14 items-center justify-center border border-red-100 bg-red-50 text-nesel-red"><x-service-icon name="mail" /></span>
                        <span class="text-xs font-black tracking-[0.18em] text-slate-300">02</span>
                    </div>
                    <p class="mt-9 text-xs font-black uppercase tracking-[0.18em] text-nesel-red">Courrier</p>
                    <h3 class="mt-3 max-w-xl text-3xl font-black tracking-[-0.04em] text-nesel-navy">{{ $courrier['title'] }}</h3>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">{{ $courrier['intro'] }}</p>
                    <ul class="mt-8 grid gap-x-8 gap-y-4 border-t border-slate-200 pt-7 sm:grid-cols-2">
                        @foreach ($courrier['items'] as $item)
                            <li class="flex gap-3 text-sm font-semibold leading-6 text-nesel-navy"><span class="text-nesel-red" aria-hidden="true">✓</span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </article>

                <article id="reexpedition" class="group scroll-mt-24 border border-slate-200 bg-white p-7 shadow-[0_18px_60px_rgba(6,24,50,0.06)] transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-[0_24px_70px_rgba(6,24,50,0.1)] sm:p-10" data-reveal>
                    <div class="flex items-start justify-between gap-6">
                        <span class="flex size-14 items-center justify-center border border-red-100 bg-red-50 text-nesel-red"><x-service-icon name="send" /></span>
                        <span class="text-xs font-black tracking-[0.18em] text-slate-300">03</span>
                    </div>
                    <p class="mt-9 text-xs font-black uppercase tracking-[0.18em] text-nesel-red">Transmission</p>
                    <h3 class="mt-3 text-3xl font-black tracking-[-0.04em] text-nesel-navy">{{ $reexpedition['title'] }}</h3>
                    <p class="mt-5 text-base leading-7 text-slate-600">{{ $reexpedition['intro'] }}</p>
                    <ul class="mt-8 space-y-4 border-t border-slate-200 pt-7">
                        @foreach ($reexpedition['items'] as $item)
                            <li class="flex gap-3 text-sm font-semibold leading-6 text-nesel-navy"><span class="text-nesel-red" aria-hidden="true">✓</span>{{ $item }}</li>
                        @endforeach
                    </ul>
                    <p class="mt-7 border-l-2 border-nesel-gold pl-4 text-sm leading-6 text-slate-500">{{ $reexpedition['note'] }}</p>
                </article>
            </div>
        </div>
    </section>

    <section id="bureaux" class="relative scroll-mt-24 overflow-hidden bg-nesel-navy py-20 text-white sm:py-28 lg:py-32">
        <div class="premium-grid absolute inset-0 opacity-25" aria-hidden="true"></div>
        <div class="absolute -left-40 top-24 size-[30rem] rounded-full border border-white/10" aria-hidden="true"></div>
        <div class="relative mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.9fr_1.1fr] lg:items-center lg:gap-24 lg:px-10">
            <div data-reveal>
                <div class="flex size-14 items-center justify-center border border-white/15 bg-white/[0.06] text-nesel-gold"><x-service-icon name="workspace" /></div>
                <p class="mt-8 text-xs font-black uppercase tracking-[0.2em] text-nesel-gold">04 · Espaces professionnels</p>
                <h2 class="mt-4 max-w-2xl text-balance text-4xl font-black leading-[1.02] tracking-[-0.045em] sm:text-5xl">{{ $bureaux['title'] }}</h2>
                <p class="mt-6 max-w-xl text-lg leading-8 text-white/70">{{ $bureaux['intro'] }}</p>
                <p class="mt-7 max-w-xl border-l-2 border-nesel-red pl-5 text-sm leading-6 text-white/55">{{ $bureaux['note'] }}</p>
            </div>

            <div class="border border-white/15 bg-white/[0.06] p-6 backdrop-blur-sm sm:p-9" data-reveal>
                <p class="text-xs font-black uppercase tracking-[0.18em] text-white/45">Sur place, selon vos besoins</p>
                <ul class="mt-5 divide-y divide-white/15">
                    @foreach ($bureaux['items'] as $item)
                        <li class="group flex items-center gap-5 py-6">
                            <span class="text-xs font-black text-nesel-red">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="text-lg font-bold tracking-tight">{{ $item }}</span>
                            <span class="ml-auto text-white/30 transition group-hover:text-white" aria-hidden="true">↗</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </section>

    <section id="accompagnement" class="scroll-mt-24 bg-white py-20 sm:py-28 lg:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-end lg:gap-20">
                <x-section-heading eyebrow="Accompagnement business" title="Un appui structuré pour faire avancer l’entreprise." description="Lancement de votre activité, gestion courante ou investissement au Maroc : vous gardez un interlocuteur identifiable pour les étapes qui comptent." />
                <div class="hidden h-px bg-slate-200 lg:block" aria-hidden="true"></div>
            </div>

            <div class="mt-14 grid gap-6 lg:grid-cols-12">
                @foreach ($supportServices as $service)
                    <article id="{{ $service['id'] }}" @class([
                        'group scroll-mt-24 border border-slate-200 bg-nesel-ivory p-7 transition hover:-translate-y-1 hover:border-slate-300 hover:bg-white hover:shadow-[0_24px_70px_rgba(6,24,50,0.09)] sm:p-9',
                        'lg:col-span-7' => $service['id'] === 'creation',
                        'lg:col-span-5' => $service['id'] !== 'creation',
                    ]) data-reveal>
                        <div class="flex items-start justify-between gap-6">
                            <span class="flex size-12 items-center justify-center border border-slate-200 bg-white text-nesel-red"><x-service-icon :name="$supportIcons[$service['id']]" class="size-6" /></span>
                            <span class="text-xs font-black tracking-[0.18em] text-slate-300">{{ sprintf('%02d', $loop->iteration + 4) }}</span>
                        </div>
                        <p class="mt-8 text-xs font-black uppercase tracking-[0.18em] text-nesel-red">
                            {{ $service['id'] === 'creation' ? 'Lancement' : ($service['id'] === 'administratif' ? 'Administration' : 'Investisseurs') }}
                        </p>
                        <h3 class="mt-3 text-2xl font-black tracking-[-0.035em] text-nesel-navy sm:text-3xl">{{ $service['title'] }}</h3>
                        <p class="mt-5 text-base leading-7 text-slate-600">{{ $service['intro'] }}</p>
                        <ul class="mt-8 space-y-4 border-t border-slate-200 pt-7">
                            @foreach ($service['items'] as $item)
                                <li class="flex gap-3 text-sm font-semibold leading-6 text-nesel-navy"><span class="text-nesel-red" aria-hidden="true">✓</span>{{ $item }}</li>
                            @endforeach
                        </ul>
                        @if ($service['note'])
                            <p class="mt-7 border-l-2 border-nesel-gold pl-4 text-sm leading-6 text-slate-500">{{ $service['note'] }}</p>
                        @endif
                    </article>
                @endforeach
            </div>

            <p class="mt-10 max-w-3xl border-l-2 border-slate-300 pl-5 text-sm leading-6 text-slate-500">{{ config('business.private_company_disclaimer') }}</p>
        </div>
    </section>

    <x-cta-banner title="Parlons de votre entreprise" description="Expliquez-nous votre besoin et choisissez la solution de domiciliation la plus adaptée à votre activité.">
        <a href="{{ route('home', ['ville' => 'Marrakech']) }}#contact" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-md bg-nesel-red px-6 text-center text-sm font-bold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-white">Être rappelé pour Marrakech</a>
        <a href="{{ route('home', ['ville' => 'Casablanca']) }}#contact" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-md border border-white/25 px-6 text-center text-sm font-bold text-white transition hover:border-white/60 hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-white">Être rappelé pour Casablanca</a>
        <a href="{{ route('offers') }}" class="group inline-flex items-center justify-center gap-2 px-2 py-3 text-sm font-bold text-white">Découvrir nos offres de domiciliation <span class="transition group-hover:translate-x-1" aria-hidden="true">→</span></a>
    </x-cta-banner>
@endsection
