{{--
    Callback request section (#contact): introduction, public contact details and the
    contact form posted to contact-requests.store. Rendered once per page, on the homepage
    and on the city landing pages so visitors can ask to be called back without leaving them.
    - city: preselected city when neither old input nor ?ville= provides one.
--}}
@props([
    'kicker' => 'Prêt à avancer ?',
    'title' => 'Commençons par une conversation.',
    'description' => 'Laissez-nous vos coordonnées. Un conseiller Nesel vous recontactera pour comprendre votre besoin de domiciliation.',
    'city' => null,
])
@php
    $selectedCity = old('city', request()->query('ville', $city));
    $telephone = config('business.telephone');
    $email = config('business.email');
@endphp

<section id="contact" {{ $attributes->merge(['class' => 'scroll-mt-20 py-24 sm:py-32']) }}>
    <div class="mx-auto grid max-w-7xl gap-12 px-5 sm:px-8 lg:grid-cols-[0.85fr_1.15fr] lg:gap-20 lg:px-10">
        <div data-reveal>
            <p class="section-kicker">{{ $kicker }}</p>
            <h2 class="section-title mt-4">{{ $title }}</h2>
            <p class="mt-6 max-w-md text-base leading-7 text-slate-600">{{ $description }}</p>
            <div class="mt-10 border-l-4 border-nesel-red pl-5">
                <p class="font-bold text-nesel-navy">{{ $city ?? 'Marrakech ou Casablanca' }}</p>
                <p class="mt-1 text-sm text-slate-500">Une réponse claire, adaptée à votre projet.</p>
            </div>
            @if (filled($telephone) || filled($email))
                <div class="mt-8 space-y-2 text-sm text-slate-600">
                    <p class="font-bold text-nesel-navy">Vous préférez nous contacter directement ?</p>
                    @if (filled($telephone))
                        <p><a href="tel:{{ preg_replace('/[^\d+]/', '', $telephone) }}" class="font-semibold text-nesel-red underline underline-offset-4">{{ $telephone }}</a></p>
                    @endif
                    @if (filled($email))
                        <p><a href="mailto:{{ $email }}" class="font-semibold text-nesel-red underline underline-offset-4">{{ $email }}</a></p>
                    @endif
                </div>
            @endif
        </div>

        <form id="callback-form" method="POST" action="{{ route('contact-requests.store') }}" class="bg-white p-6 shadow-[0_24px_80px_rgba(6,24,50,0.12)] sm:p-10" data-reveal>
            @csrf
            <input type="hidden" name="submission_token" value="{{ old('submission_token', (string) \Illuminate\Support\Str::uuid()) }}">

            @if ($errors->any())
                <div class="mb-6 border-l-4 border-nesel-red bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    {{ $errors->first('throttle') ?: 'Veuillez corriger les informations indiquées ci-dessous.' }}
                </div>
            @endif

            <div class="grid gap-6 sm:grid-cols-2">
                <label class="field-label sm:col-span-2">
                    Nom complet
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="100" autocomplete="name" placeholder="Votre nom" class="field-input @error('name') border-nesel-red @enderror" aria-describedby="name-error">
                    @error('name')
                        <span id="name-error" class="normal-case tracking-normal text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="field-label">
                    Adresse e-mail
                    <input type="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email" placeholder="vous@exemple.com" class="field-input @error('email') border-nesel-red @enderror" aria-describedby="email-error">
                    @error('email')
                        <span id="email-error" class="normal-case tracking-normal text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="field-label">
                    Téléphone
                    <input type="tel" name="phone" value="{{ old('phone') }}" required maxlength="30" autocomplete="tel" placeholder="+212 6 00 00 00 00" class="field-input @error('phone') border-nesel-red @enderror" aria-describedby="phone-error">
                    @error('phone')
                        <span id="phone-error" class="normal-case tracking-normal text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="field-label">
                    Ville souhaitée
                    <select id="city-select" name="city" required class="field-input @error('city') border-nesel-red @enderror" aria-describedby="city-error">
                        <option value="">Sélectionner</option>
                        <option value="Marrakech" @selected($selectedCity === 'Marrakech')>Marrakech</option>
                        <option value="Casablanca" @selected($selectedCity === 'Casablanca')>Casablanca</option>
                    </select>
                    @error('city')
                        <span id="city-error" class="normal-case tracking-normal text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="field-label">
                    Offre souhaitée <span class="font-semibold normal-case tracking-normal text-slate-400">(facultatif)</span>
                    <select name="offer" class="field-input @error('offer') border-nesel-red @enderror" aria-describedby="offer-error">
                        <option value="">Pas de préférence</option>
                        @foreach (['Silver' => 'Silver', 'Golden' => 'Golden', 'Diamond' => 'Diamond', 'Conseil' => 'Je souhaite être conseillé'] as $offerValue => $offerLabel)
                            <option value="{{ $offerValue }}" @selected(old('offer', request()->query('offre')) === $offerValue)>{{ $offerLabel }}</option>
                        @endforeach
                    </select>
                    @error('offer')
                        <span id="offer-error" class="normal-case tracking-normal text-red-700">{{ $message }}</span>
                    @enderror
                </label>
                <label class="field-label sm:col-span-2">
                    Votre besoin
                    <textarea name="message" rows="3" maxlength="2000" placeholder="Domiciliation, lancement d’activité, transfert de siège…" class="field-input resize-none @error('message') border-nesel-red @enderror" aria-describedby="message-error">{{ old('message') }}</textarea>
                    @error('message')
                        <span id="message-error" class="normal-case tracking-normal text-red-700">{{ $message }}</span>
                    @enderror
                </label>
            </div>
            <button type="submit" class="mt-7 inline-flex min-h-13 w-full items-center justify-center gap-2 rounded-md bg-nesel-red px-7 text-sm font-bold text-white transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-nesel-red focus:ring-offset-2">
                <span data-submit-label aria-live="polite">Demander à être rappelé</span>
                <span aria-hidden="true">→</span>
            </button>
            <p class="mt-4 text-center text-xs leading-5 text-slate-400">En envoyant ce formulaire, vous acceptez d’être contacté par l’équipe Nesel.</p>
            <p class="mt-4 border-t border-slate-200 pt-4 text-sm leading-6 text-slate-600">{{ config('business.private_company_disclaimer') }}</p>
        </form>
    </div>
</section>
