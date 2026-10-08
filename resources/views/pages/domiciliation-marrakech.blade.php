@use('App\Support\Seo')
@use('App\Support\StructuredData')
@php
    $location = config('business.locations.marrakech');
    $contactUrl = '#contact';

    $breadcrumbs = [
        ['name' => 'Accueil', 'url' => Seo::route('home')],
        ['name' => 'Domiciliation à Marrakech', 'url' => Seo::route('domiciliation.marrakech')],
    ];

    $faqs = [
        [
            'question' => 'Qu’est-ce que la domiciliation d’entreprise ?',
            'answer' => 'Domicilier son entreprise, c’est établir son siège à l’adresse d’un prestataire spécialisé, ici Nesel. Votre société dispose ainsi d’une adresse professionnelle à Marrakech sans que vous ayez à louer un bureau, et vous restez libre de travailler où vous le souhaitez.',
        ],
        [
            'question' => 'Pourquoi domicilier son entreprise à Marrakech ?',
            'answer' => 'Si vos clients, vos partenaires ou votre activité sont à Marrakech, une adresse dans la ville rend votre entreprise plus lisible pour eux. C’est aussi une solution adaptée aux activités qui se déroulent surtout sur le terrain ou à distance et qui n’ont pas besoin d’un local permanent.',
        ],
        [
            'question' => 'Peut-on utiliser l’adresse Nesel comme siège social ?',
            'answer' => 'Oui, c’est précisément l’objet de la domiciliation : l’adresse fournie par Nesel devient le siège de votre entreprise. Votre conseiller vous précise les conditions qui s’appliquent à votre situation avant la mise en place.',
        ],
        [
            'question' => 'Quels documents sont nécessaires ?',
            'answer' => 'Ils dépendent de votre situation, notamment selon que vous créez une société ou que vous transférez le siège d’une entreprise existante. Votre conseiller vous communique la liste complète au moment de la proposition, pour que vous puissiez préparer votre dossier en une seule fois.',
        ],
        [
            'question' => 'Comment mon courrier est-il géré ?',
            'answer' => 'Nesel réceptionne le courrier adressé à votre entreprise à Marrakech et vous informe de son arrivée, pour que rien d’important ne vous échappe. Les modalités de mise à disposition vous sont présentées avec votre proposition.',
        ],
        [
            'question' => 'En combien de temps ma domiciliation est-elle active ?',
            'answer' => 'Le délai de mise en place dépend de votre situation et des éléments nécessaires au service de domiciliation. Votre conseiller vous indique les prochaines étapes après vérification de votre dossier.',
        ],
        [
            'question' => 'Comment démarrer ma demande ?',
            'answer' => 'Remplissez le formulaire de rappel en choisissant Marrakech comme ville souhaitée. Un conseiller Nesel vous recontacte pour comprendre votre projet, puis vous adresse une proposition adaptée.',
        ],
    ];
@endphp

@extends('layouts.app', [
    'hasContactForm' => true,
    'title' => 'Domiciliation d’entreprise à Marrakech | Nesel',
    'description' => 'Domiciliez votre entreprise à Marrakech avec Nesel : une adresse professionnelle pour votre siège social, la réception de votre courrier et une équipe locale pour vous accompagner.',
    'ogImage' => $location['image'],
])

@push('structured-data')
    <x-json-ld :data="StructuredData::localBusiness('marrakech')" />
    <x-json-ld :data="StructuredData::faqPage($faqs)" />
@endpush

@section('content')
    <section class="relative overflow-hidden bg-nesel-navy">
        <div class="mx-auto grid max-w-[1600px] lg:grid-cols-[1fr_0.9fr]">
            <div class="relative z-10 flex items-center px-5 py-16 sm:px-10 lg:px-16 lg:py-24 xl:px-24">
                <div class="max-w-xl">
                    <x-breadcrumb :items="$breadcrumbs" class="mb-10" />
                    <h1 class="text-balance text-5xl font-black leading-[0.95] tracking-[-0.055em] text-white sm:text-6xl">
                        Domiciliation d’entreprise à <span class="text-nesel-red">Marrakech</span>
                    </h1>
                    <p class="mt-7 max-w-lg text-lg leading-8 text-slate-300">
                        Installez le siège de votre société à Marrakech sans louer de bureau. Nesel vous fournit une adresse professionnelle, réceptionne votre courrier et vous accompagne dans les démarches liées à votre domiciliation.
                    </p>
                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $contactUrl }}" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-md bg-nesel-red px-7 text-sm font-bold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-white">
                            Demander une proposition
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="#services-inclus" class="inline-flex min-h-13 items-center justify-center rounded-md border border-white/30 px-7 text-sm font-bold text-white transition hover:border-white hover:bg-white/10">Ce qui est inclus</a>
                    </div>
                </div>
            </div>

            <div class="relative min-h-[360px] overflow-hidden lg:min-h-full">
                <x-picture :src="$location['image']" alt="" class="absolute inset-0 size-full object-cover object-left" width="1680" height="945" fetchpriority="high" />
                <div class="absolute inset-0 bg-[#601b16]/45" aria-hidden="true"></div>
            </div>
        </div>
    </section>

    <section class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20">
                <div data-reveal>
                    <p class="section-kicker">Pour qui ?</p>
                    <h2 class="section-title mt-4">À qui s’adresse la domiciliation à Marrakech ?</h2>
                    <p class="mt-6 max-w-md text-base leading-7 text-slate-600">
                        Marrakech accueille des projets très variés : tourisme et hôtellerie, restauration, artisanat, événementiel, services aux entreprises ou activités numériques. Beaucoup de ces entreprises n’ont pas besoin d’un bureau permanent, mais toutes ont besoin d’une adresse fiable pour leurs documents et leurs échanges.
                    </p>
                </div>

                <div class="grid gap-px border border-slate-300 bg-slate-300 sm:grid-cols-2" data-reveal>
                    <article class="bg-nesel-ivory p-7 sm:p-9">
                        <h3 class="text-xl font-extrabold tracking-tight">Vous lancez votre activité</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Une adresse de siège est l’une des premières étapes de la constitution de votre société. Nous mettons en place votre domiciliation et vous accompagnons pour avancer sereinement.</p>
                    </article>
                    <article class="bg-nesel-ivory p-7 sm:p-9">
                        <h3 class="text-xl font-extrabold tracking-tight">Vous travaillez sur le terrain</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Guide, consultant, créatif, prestataire événementiel : votre activité se passe chez vos clients. Une adresse professionnelle sépare clairement votre entreprise de votre domicile.</p>
                    </article>
                    <article class="bg-nesel-ivory p-7 sm:p-9">
                        <h3 class="text-xl font-extrabold tracking-tight">Vous n’êtes pas toujours sur place</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Vous développez une activité à Marrakech depuis une autre ville ou depuis l’étranger. Votre courrier est réceptionné sur place et vous êtes tenu informé.</p>
                    </article>
                    <article class="bg-nesel-ivory p-7 sm:p-9">
                        <h3 class="text-xl font-extrabold tracking-tight">Vous transférez votre siège</h3>
                        <p class="mt-3 text-sm leading-6 text-slate-600">Votre entreprise existe déjà et vous souhaitez l’installer à Marrakech. Nous vous orientons dans les étapes liées au changement d’adresse.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="services-inclus" class="scroll-mt-24 bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div data-reveal>
                <p class="section-kicker">Services</p>
                <h2 class="section-title mt-4">Ce que comprend votre domiciliation à Marrakech</h2>
            </div>

            <div class="mt-14 grid border-t border-slate-300 sm:grid-cols-2 lg:grid-cols-4" data-reveal>
                <article class="service-item sm:border-r sm:border-slate-300">
                    <span class="service-number">01</span>
                    <h3>Une adresse de siège social</h3>
                    <p>Une adresse professionnelle à Marrakech pour établir le siège social de votre entreprise et l’indiquer sur vos documents commerciaux.</p>
                </article>
                <article class="service-item lg:border-r lg:border-slate-300">
                    <span class="service-number">02</span>
                    <h3>La réception de votre courrier</h3>
                    <p>Nous réceptionnons les plis adressés à votre entreprise et vous prévenons de leur arrivée.</p>
                </article>
                <article class="service-item sm:border-r sm:border-slate-300">
                    <span class="service-number">03</span>
                    <h3>Un accompagnement administratif</h3>
                    <p>Nous vous guidons dans les démarches liées à votre domiciliation, de la constitution du dossier à l’activation.</p>
                </article>
                <article class="service-item">
                    <span class="service-number">04</span>
                    <h3>Un interlocuteur identifié</h3>
                    <p>Une équipe locale qui connaît votre dossier et vous répond simplement, sans jargon.</p>
                </article>
            </div>

            <p class="mt-8 flex flex-wrap gap-x-8 gap-y-3 text-sm font-bold" data-reveal>
                <a href="{{ route('services') }}" class="border-b border-nesel-red pb-1 text-nesel-red hover:text-red-700">Découvrez l’ensemble de nos services de domiciliation</a>
                <a href="{{ route('offers') }}" class="border-b border-nesel-red pb-1 text-nesel-red hover:text-red-700">Comparer les offres Silver, Golden et Diamond</a>
            </p>

            <div class="mt-16 grid gap-10 lg:grid-cols-2 lg:gap-20" data-reveal>
                <div>
                    <h3 class="text-2xl font-black tracking-[-0.03em]">Comment fonctionne la domiciliation ?</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        Le principe est simple : le siège de votre entreprise est établi à l’adresse fournie par Nesel. Vous exercez votre activité là où vous le souhaitez — chez vos clients, à domicile ou en déplacement — et le courrier professionnel de votre société arrive chez nous.
                    </p>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        Les conditions de votre domiciliation (services inclus, durée, tarif) sont détaillées dans la proposition que nous vous adressons, sans frais cachés.
                    </p>
                </div>
                <div>
                    <h3 class="text-2xl font-black tracking-[-0.03em]">Votre adresse à Marrakech</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        {{ $location['map_query'] }}
                    </p>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        Cette adresse figurera sur vos statuts, vos factures et vos échanges avec l’administration et vos partenaires.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="localisation" class="scroll-mt-24 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div data-reveal>
                <p class="section-kicker">Localisation</p>
                <h2 class="section-title mt-4">Où nous trouver à Marrakech</h2>
            </div>
            <x-google-map
                class="mt-12"
                :name="$location['name']"
                :address="$location['map_query']"
                :place-id="$location['google_place_id']"
                title="Localisation du bureau Nesel à Marrakech"
            />
        </div>
    </section>

    <section class="bg-nesel-navy py-24 text-white sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-14 lg:grid-cols-[0.8fr_1.2fr] lg:gap-24">
                <div data-reveal>
                    <p class="section-kicker text-nesel-gold">Souscription</p>
                    <h2 class="section-title mt-4 text-white">Les étapes pour domicilier votre entreprise à Marrakech</h2>
                </div>
                <ol class="divide-y divide-white/15 border-y border-white/15" data-reveal>
                    <li class="step-row">
                        <span>01</span>
                        <div><h3>Vous nous présentez votre projet</h3><p>Création ou transfert de siège, activité, calendrier : quelques minutes d’échange suffisent pour cerner votre besoin.</p></div>
                    </li>
                    <li class="step-row">
                        <span>02</span>
                        <div><h3>Vous recevez une proposition claire</h3><p>Nous vous orientons vers la formule adaptée et vous indiquons les documents à fournir.</p></div>
                    </li>
                    <li class="step-row">
                        <span>03</span>
                        <div><h3>Vous transmettez votre dossier</h3><p>Nous vérifions avec vous que tout est complet pour éviter les allers-retours.</p></div>
                    </li>
                    <li class="step-row">
                        <span>04</span>
                        <div><h3>Votre adresse est activée</h3><p>Après vérification des éléments nécessaires, votre service de domiciliation à Marrakech est mis en place.</p></div>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    <section class="py-24 sm:py-32">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-2 lg:gap-20 lg:px-10">
            <div data-reveal>
                <p class="section-kicker">Votre dossier</p>
                <h2 class="section-title mt-4">Les documents à préparer</h2>
                <p class="mt-6 text-base leading-7 text-slate-600">
                    Les pièces demandées varient selon votre situation : création de société ou transfert de siège, forme juridique, nombre de dirigeants. Votre conseiller vous transmet la liste exacte avec votre proposition.
                </p>
                <div class="mt-8 border-l-4 border-nesel-red pl-5">
                    <p class="font-bold text-nesel-navy">Vous souhaitez préparer votre dossier dès maintenant ?</p>
                    <p class="mt-1 text-sm leading-6 text-slate-600">Un conseiller Nesel vous indique les pièces adaptées à votre situation.</p>
                    <a href="#contact" class="mt-4 inline-flex items-center gap-2 border-b border-nesel-red pb-1 text-sm font-bold text-nesel-red hover:text-red-700">Demander la liste à un conseiller <span aria-hidden="true">→</span></a>
                </div>
            </div>

            <div data-reveal>
                <p class="section-kicker">Pourquoi Nesel</p>
                <h2 class="section-title mt-4">Pourquoi choisir Nesel à Marrakech ?</h2>
                <ul class="mt-8 space-y-5 text-base leading-7 text-slate-600">
                    <li class="border-l-4 border-nesel-red pl-5"><strong class="text-nesel-navy">Un suivi humain.</strong> Vous échangez avec une personne qui connaît votre dossier, pas avec un formulaire.</li>
                    <li class="border-l-4 border-nesel-red pl-5"><strong class="text-nesel-navy">Une proposition transparente.</strong> Ce qui est inclus est écrit noir sur blanc, sans frais cachés.</li>
                    <li class="border-l-4 border-nesel-red pl-5"><strong class="text-nesel-navy">Des réponses rapides.</strong> Une relation simple et directe, sans jargon inutile.</li>
                    <li class="border-l-4 border-nesel-red pl-5"><strong class="text-nesel-navy">Deux villes, un seul interlocuteur.</strong> Si votre activité s’étend, Nesel propose aussi la <a href="{{ route('domiciliation.casablanca') }}" class="font-semibold text-nesel-red underline underline-offset-4">domiciliation d’entreprise à Casablanca</a>.</li>
                </ul>
            </div>
        </div>
    </section>

    <section class="bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-4xl px-5 sm:px-8 lg:px-10">
            <p class="section-kicker">FAQ</p>
            <h2 class="section-title mt-4">Questions fréquentes sur la domiciliation à Marrakech</h2>
            <x-faq :items="$faqs" class="mt-12" />
        </div>
    </section>

    <x-contact-section
        class="bg-nesel-ivory"
        kicker="Demande de rappel"
        title="Prêt à domicilier votre entreprise à Marrakech ?"
        description="Laissez-nous vos coordonnées : un conseiller vous rappelle pour faire le point sur votre projet et vous adresser une proposition."
        city="Marrakech"
    />
@endsection
