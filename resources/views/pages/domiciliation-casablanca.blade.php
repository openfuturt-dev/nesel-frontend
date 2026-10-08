@use('App\Support\Seo')
@use('App\Support\StructuredData')
@php
    $location = config('business.locations.casablanca');
    $contactUrl = '#contact';

    $breadcrumbs = [
        ['name' => 'Accueil', 'url' => Seo::route('home')],
        ['name' => 'Domiciliation à Casablanca', 'url' => Seo::route('domiciliation.casablanca')],
    ];

    $faqs = [
        [
            'question' => 'La domiciliation d’entreprise, concrètement, qu’est-ce que c’est ?',
            'answer' => 'C’est un service qui permet à votre société d’avoir son siège à l’adresse de Nesel à Casablanca. Vous bénéficiez d’une adresse professionnelle sans les charges d’un bureau, et votre courrier est réceptionné pour vous.',
        ],
        [
            'question' => 'Pourquoi choisir Casablanca pour domicilier sa société ?',
            'answer' => 'Casablanca est la capitale économique du Maroc. Si vous travaillez avec des entreprises, des banques ou des partenaires installés dans la ville, y avoir votre siège simplifie les échanges et renforce la cohérence de votre image.',
        ],
        [
            'question' => 'L’adresse peut-elle devenir le siège social de ma société ?',
            'answer' => 'Oui. La domiciliation consiste justement à établir le siège de votre entreprise à l’adresse fournie par Nesel. Votre conseiller vérifie avec vous les conditions propres à votre situation.',
        ],
        [
            'question' => 'Quels justificatifs dois-je préparer ?',
            'answer' => 'La liste varie selon votre projet : création d’une nouvelle société ou transfert du siège d’une société existante. Elle vous est communiquée avec votre proposition afin que vous puissiez tout rassembler dès le départ.',
        ],
        [
            'question' => 'Que devient le courrier adressé à mon entreprise ?',
            'answer' => 'Il est réceptionné par Nesel à Casablanca, et vous êtes informé de son arrivée. Les modalités de mise à disposition sont précisées dans votre proposition.',
        ],
        [
            'question' => 'Puis-je transférer à Casablanca le siège d’une société existante ?',
            'answer' => 'Oui, la domiciliation convient aussi bien à une création qu’à un transfert de siège. Précisez-le lors de votre demande : votre conseiller vous oriente sur les étapes à suivre.',
        ],
        [
            'question' => 'Comment lancer ma demande ?',
            'answer' => 'Utilisez le formulaire de rappel en sélectionnant Casablanca. Un conseiller Nesel vous recontacte pour comprendre votre besoin et vous adresser une proposition.',
        ],
    ];
@endphp

@extends('layouts.app', [
    'hasContactForm' => true,
    'title' => 'Domiciliation d’entreprise à Casablanca | Nesel',
    'description' => 'Nesel domicilie votre société à Casablanca : une adresse de siège social dans la capitale économique, la réception de votre courrier et un conseiller qui suit votre dossier.',
    'ogImage' => $location['image'],
])

@push('structured-data')
    <x-json-ld :data="StructuredData::localBusiness('casablanca')" />
    <x-json-ld :data="StructuredData::faqPage($faqs)" />
@endpush

@section('content')
    <section class="relative overflow-hidden bg-nesel-navy">
        <div class="mx-auto grid max-w-[1600px] lg:grid-cols-[1fr_0.9fr]">
            <div class="relative z-10 flex items-center px-5 py-16 sm:px-10 lg:px-16 lg:py-24 xl:px-24">
                <div class="max-w-xl">
                    <x-breadcrumb :items="$breadcrumbs" class="mb-10" />
                    <h1 class="text-balance text-5xl font-black leading-[0.95] tracking-[-0.055em] text-white sm:text-6xl">
                        Domiciliation d’entreprise à <span class="text-nesel-red">Casablanca</span>
                    </h1>
                    <p class="mt-7 max-w-lg text-lg leading-8 text-slate-300">
                        Casablanca est le principal centre d’affaires du Maroc. Y établir le siège de votre société vous rapproche de vos clients et de vos partenaires, sans supporter le coût d’un bureau que vous n’occuperiez pas au quotidien.
                    </p>
                    <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $contactUrl }}" class="inline-flex min-h-13 items-center justify-center gap-2 rounded-md bg-nesel-red px-7 text-sm font-bold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-white">
                            Être rappelé par un conseiller
                            <span aria-hidden="true">→</span>
                        </a>
                        <a href="#avantages" class="inline-flex min-h-13 items-center justify-center rounded-md border border-white/30 px-7 text-sm font-bold text-white transition hover:border-white hover:bg-white/10">Pourquoi Casablanca</a>
                    </div>
                </div>
            </div>

            <div class="relative min-h-[360px] overflow-hidden lg:min-h-full">
                <x-picture :src="$location['image']" alt="" class="absolute inset-0 size-full object-cover object-right" width="1680" height="945" fetchpriority="high" />
                <div class="absolute inset-0 bg-nesel-navy/40" aria-hidden="true"></div>
            </div>
        </div>
    </section>

    <div class="bg-nesel-red text-white">
        <div class="mx-auto grid max-w-7xl gap-4 px-5 py-5 text-sm font-bold sm:grid-cols-3 sm:px-8 lg:px-10">
            <p class="flex items-center gap-3"><span class="text-nesel-gold">✓</span> Siège social à Casablanca</p>
            <p class="flex items-center gap-3"><span class="text-nesel-gold">✓</span> Courrier réceptionné pour vous</p>
            <p class="flex items-center gap-3"><span class="text-nesel-gold">✓</span> Un conseiller dédié à votre dossier</p>
        </div>
    </div>

    <section id="avantages" class="scroll-mt-24 py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="max-w-3xl" data-reveal>
                <p class="section-kicker">La capitale économique</p>
                <h2 class="section-title mt-4">Une adresse à Casablanca, sans les contraintes d’un bureau</h2>
                <p class="mt-6 text-base leading-7 text-slate-600">
                    Pour beaucoup d’entreprises, la question n’est pas d’avoir des locaux, mais d’avoir la bonne adresse. La domiciliation répond précisément à ce besoin : le siège de votre société est établi à Casablanca, tandis que vous organisez votre travail comme vous l’entendez.
                </p>
            </div>

            <div class="mt-14 grid gap-10 md:grid-cols-3" data-reveal>
                <article class="border-t-4 border-nesel-navy pt-6">
                    <h3 class="text-xl font-extrabold tracking-tight">Être au plus près de vos interlocuteurs</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Clients, fournisseurs, banques, cabinets de conseil : une grande partie de l’activité économique marocaine passe par Casablanca. Une adresse dans la ville vous inscrit naturellement dans cet environnement.</p>
                </article>
                <article class="border-t-4 border-nesel-navy pt-6">
                    <h3 class="text-xl font-extrabold tracking-tight">Maîtriser vos frais fixes</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Louer un bureau pour une adresse que vous utilisez surtout sur vos documents pèse sur votre trésorerie. La domiciliation vous permet de consacrer ce budget au développement de votre activité.</p>
                </article>
                <article class="border-t-4 border-nesel-navy pt-6">
                    <h3 class="text-xl font-extrabold tracking-tight">Garder votre liberté d’organisation</h3>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Télétravail, rendez-vous chez vos clients, équipes réparties : votre siège reste stable à Casablanca, quelle que soit la façon dont vous travaillez.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="bg-white py-24 sm:py-32">
        <div class="mx-auto grid max-w-7xl gap-14 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20 lg:px-10">
            <div data-reveal>
                <p class="section-kicker">Pour quels projets ?</p>
                <h2 class="section-title mt-4">Les situations où la domiciliation à Casablanca a du sens</h2>
            </div>

            <dl class="divide-y divide-slate-200 border-y border-slate-200" data-reveal>
                <div class="grid gap-2 py-7 sm:grid-cols-[0.9fr_1.1fr] sm:gap-8">
                    <dt class="text-lg font-extrabold tracking-tight">Lancer une nouvelle société</dt>
                    <dd class="text-sm leading-6 text-slate-600">Vous avez besoin d’une adresse de siège pour la constitution de votre société, sans vous engager sur un bail dès le démarrage.</dd>
                </div>
                <div class="grid gap-2 py-7 sm:grid-cols-[0.9fr_1.1fr] sm:gap-8">
                    <dt class="text-lg font-extrabold tracking-tight">Travailler en indépendant ou en petite équipe</dt>
                    <dd class="text-sm leading-6 text-slate-600">Consultants, agences, prestataires B2B : vos clients sont à Casablanca, votre bureau peut être ailleurs.</dd>
                </div>
                <div class="grid gap-2 py-7 sm:grid-cols-[0.9fr_1.1fr] sm:gap-8">
                    <dt class="text-lg font-extrabold tracking-tight">Transférer un siège existant</dt>
                    <dd class="text-sm leading-6 text-slate-600">Vous déménagez, vous quittez un local devenu trop coûteux ou vous souhaitez rapprocher votre siège de votre marché.</dd>
                </div>
                <div class="grid gap-2 py-7 sm:grid-cols-[0.9fr_1.1fr] sm:gap-8">
                    <dt class="text-lg font-extrabold tracking-tight">Piloter votre activité à distance</dt>
                    <dd class="text-sm leading-6 text-slate-600">Vous vivez dans une autre ville ou à l’étranger : votre courrier est réceptionné à Casablanca et vous êtes informé de chaque arrivée.</dd>
                </div>
            </dl>
        </div>
    </section>

    <section class="py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-14 lg:grid-cols-2 lg:gap-20">
                <div data-reveal>
                    <p class="section-kicker">L’offre</p>
                    <h2 class="section-title mt-4">Les services inclus dans votre domiciliation</h2>
                    <ul class="mt-10 space-y-6">
                        <li>
                            <h3 class="text-lg font-extrabold tracking-tight">Adresse professionnelle et siège social</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">L’adresse de Nesel à Casablanca devient celle de votre société, sur vos statuts comme sur vos documents commerciaux.</p>
                        </li>
                        <li>
                            <h3 class="text-lg font-extrabold tracking-tight">Réception et suivi du courrier</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Vos plis sont réceptionnés et vous êtes prévenu à leur arrivée.</p>
                        </li>
                        <li>
                            <h3 class="text-lg font-extrabold tracking-tight">Accompagnement dans vos démarches</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600">Une équipe locale vous guide dans les étapes administratives liées à votre domiciliation.</p>
                        </li>
                    </ul>
                    <p class="mt-8 flex flex-col gap-3 text-sm font-bold">
                        <a href="{{ route('offers') }}" class="self-start border-b border-nesel-red pb-1 text-nesel-red hover:text-red-700">Comparer les offres Silver, Golden et Diamond</a>
                        <a href="{{ route('services') }}" class="self-start border-b border-nesel-red pb-1 text-nesel-red hover:text-red-700">Découvrez l’ensemble de nos services de domiciliation</a>
                    </p>
                </div>

                <div class="bg-white p-8 shadow-[0_24px_80px_rgba(6,24,50,0.08)] sm:p-10" data-reveal>
                    <h3 class="text-2xl font-black tracking-[-0.03em]">Votre adresse à Casablanca</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        {{ $location['map_query'] }}
                    </p>

                    <h3 class="mt-10 text-2xl font-black tracking-[-0.03em]">Préparer votre dossier</h3>
                    <p class="mt-4 text-base leading-7 text-slate-600">
                        Les justificatifs à fournir dépendent de votre projet et de la forme de votre société. Nous vous remettons la liste précise avec votre proposition.
                    </p>
                        <div class="mt-8 border-l-4 border-nesel-red pl-5">
                        <p class="font-bold text-nesel-navy">Vous souhaitez préparer votre dossier dès maintenant ?</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Un conseiller Nesel vous indique les pièces adaptées à votre situation.</p>
                        <a href="#contact" class="mt-4 inline-flex items-center gap-2 border-b border-nesel-red pb-1 text-sm font-bold text-nesel-red hover:text-red-700">Demander la liste à un conseiller <span aria-hidden="true">→</span></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="localisation" class="scroll-mt-24 bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div data-reveal>
                <p class="section-kicker">Localisation</p>
                <h2 class="section-title mt-4">Où nous trouver à Casablanca</h2>
            </div>
            <x-google-map
                class="mt-12"
                :name="$location['name']"
                :address="$location['map_query']"
                :place-id="$location['google_place_id']"
                title="Localisation du bureau Nesel à Casablanca"
            />
        </div>
    </section>

    <section class="bg-nesel-navy py-24 text-white sm:py-32">
        <div class="mx-auto max-w-7xl px-5 sm:px-8 lg:px-10">
            <div class="grid gap-14 lg:grid-cols-[0.8fr_1.2fr] lg:gap-24">
                <div data-reveal>
                    <p class="section-kicker text-nesel-gold">Mise en place</p>
                    <h2 class="section-title mt-4 text-white">De votre premier appel à l’activation de votre adresse</h2>
                </div>
                <ol class="divide-y divide-white/15 border-y border-white/15" data-reveal>
                    <li class="step-row">
                        <span>01</span>
                        <div><h3>Un premier échange</h3><p>Vous nous décrivez votre société, votre calendrier et ce que vous attendez de votre adresse à Casablanca.</p></div>
                    </li>
                    <li class="step-row">
                        <span>02</span>
                        <div><h3>Une proposition sans surprise</h3><p>Formule, services inclus et pièces à fournir : tout est précisé, sans frais cachés.</p></div>
                    </li>
                    <li class="step-row">
                        <span>03</span>
                        <div><h3>L’activation de votre domiciliation</h3><p>Après vérification des éléments nécessaires, votre adresse est activée pour le service de domiciliation et votre courrier peut nous être adressé.</p></div>
                    </li>
                </ol>
            </div>
        </div>
    </section>

    <section class="py-24 sm:py-32">
        <div class="mx-auto grid max-w-7xl gap-10 px-5 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:gap-20 lg:px-10" data-reveal>
            <div>
                <p class="section-kicker">Bien choisir</p>
                <h2 class="section-title mt-4">Casablanca ou Marrakech : comment choisir ?</h2>
            </div>
            <div class="space-y-5 text-base leading-7 text-slate-600">
                <p>
                    Le bon choix dépend surtout de votre marché. Si vos clients sont principalement des entreprises, si vous travaillez avec des acteurs financiers ou si votre activité est tournée vers l’ensemble du pays, Casablanca est souvent l’option la plus cohérente.
                </p>
                <p>
                    Si votre activité est ancrée dans la région de Marrakech — tourisme, hôtellerie, artisanat, événementiel — une adresse sur place aura plus de sens. Découvrez notre offre de <a href="{{ route('domiciliation.marrakech') }}" class="font-semibold text-nesel-red underline underline-offset-4">domiciliation d’entreprise à Marrakech</a>.
                </p>
                <p>
                    Vous hésitez encore ? Un conseiller Nesel peut vous aider à trancher lors d’un <a href="{{ $contactUrl }}" class="font-semibold text-nesel-red underline underline-offset-4">premier échange</a>.
                </p>
            </div>
        </div>
    </section>

    <section class="bg-white py-24 sm:py-32">
        <div class="mx-auto max-w-4xl px-5 sm:px-8 lg:px-10">
            <p class="section-kicker">FAQ</p>
            <h2 class="section-title mt-4">Vos questions sur la domiciliation à Casablanca</h2>
            <x-faq :items="$faqs" class="mt-12" />
        </div>
    </section>

    <x-contact-section
        class="bg-nesel-ivory"
        kicker="Demande de rappel"
        title="Installez le siège de votre société à Casablanca"
        description="Un conseiller vous rappelle pour étudier votre projet et vous adresser une proposition adaptée."
        city="Casablanca"
    />
@endsection
