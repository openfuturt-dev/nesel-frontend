<?php

namespace App\Support;

/**
 * Nesel services and offers, as confirmed by the business document.
 *
 * Single source for the visible pages and their structured data, so both
 * always describe the same thing. No prices are defined: none are confirmed.
 */
class Catalog
{
    public const OFFER_NAMES = ['Silver', 'Golden', 'Diamond'];

    /**
     * @return list<array{id: string, title: string, summary: string, intro: string, items: list<string>, note: ?string}>
     */
    public static function services(): array
    {
        return [
            [
                'id' => 'domiciliation',
                'title' => 'Domiciliation du siège social au Maroc',
                'summary' => 'Une adresse professionnelle pour le siège social de votre entreprise, à Marrakech ou à Casablanca.',
                'intro' => 'Nesel met à votre disposition une adresse professionnelle à Marrakech ou à Casablanca, que vous pouvez utiliser comme siège social lors du lancement ou tout au long de la vie de votre entreprise.',
                'items' => [
                    'Adresse professionnelle pour votre siège social',
                    'Contrat de domiciliation',
                    'Attestation de domiciliation',
                    'Accompagnement dans les démarches administratives liées à votre domiciliation',
                ],
                'note' => null,
            ],
            [
                'id' => 'courrier',
                'title' => 'Gestion professionnelle de votre courrier',
                'summary' => 'Réception, suivi, notification, numérisation et archivage confidentiel de votre courrier.',
                'intro' => 'Votre courrier est pris en charge dès son arrivée. Si vous gérez votre société à distance ou êtes souvent en déplacement, vous savez ce qui vous est adressé sans avoir à passer à l’agence.',
                'items' => [
                    'Réception du courrier adressé à votre entreprise',
                    'Enregistrement et suivi de chaque pli',
                    'Notification à l’arrivée de votre courrier',
                    'Numérisation de vos documents',
                    'Archivage confidentiel',
                ],
                'note' => null,
            ],
            [
                'id' => 'reexpedition',
                'title' => 'Réexpédition nationale et internationale',
                'summary' => 'L’envoi de votre courrier et de vos documents au Maroc ou à l’étranger.',
                'intro' => 'Besoin de recevoir vos originaux ? Nous vous les réexpédions à l’adresse de votre choix, que vous soyez au Maroc ou à l’étranger.',
                'items' => [
                    'Envoi périodique de votre courrier',
                    'Envoi express sur demande',
                    'Transmission de documents sensibles',
                    'Expédition au Maroc et à l’international',
                ],
                'note' => 'Les modalités d’envoi sont précisées dans votre proposition.',
            ],
            [
                'id' => 'bureaux',
                'title' => 'Bureaux, coworking et salles de réunion',
                'summary' => 'Des espaces de travail pour vos rendez-vous, réunions et besoins ponctuels.',
                'intro' => 'Être domicilié ne vous empêche pas de recevoir. Nesel propose des espaces pour vos besoins ponctuels : rendez-vous clients, réunions d’équipe, assemblées ou journées de travail sur place.',
                'items' => [
                    'Bureaux privés équipés',
                    'Salles de réunion',
                    'Espaces de coworking',
                    'Accueil de vos visiteurs',
                ],
                'note' => 'Contactez-nous pour connaître les espaces disponibles dans votre ville.',
            ],
            [
                'id' => 'creation',
                'title' => 'Accompagnement à la constitution de votre société',
                'summary' => 'Un accompagnement pour organiser votre projet et suivre les différentes étapes du lancement de votre activité.',
                'intro' => 'Nesel accompagne les entrepreneurs dans l’organisation de leur projet, la préparation des informations utiles à la constitution de leur société et la coordination des différentes étapes de démarrage.',
                'items' => [
                    'Orientation sur la structure du projet',
                    'Préparation des informations et documents nécessaires',
                    'Coordination des différentes étapes',
                    'Suivi du dossier',
                    'Mise en relation avec des professionnels partenaires lorsque nécessaire',
                ],
                'note' => 'Les immatriculations et identifiants officiels sont délivrés exclusivement par les administrations compétentes.',
            ],
            [
                'id' => 'administratif',
                'title' => 'Secrétariat et accompagnement administratif',
                'summary' => 'Une assistance au quotidien pour vos tâches administratives et documentaires.',
                'intro' => 'Pour vous libérer des tâches administratives courantes, Nesel vous apporte une assistance au quotidien.',
                'items' => [
                    'Assistance administrative',
                    'Gestion documentaire',
                    'Support documentaire et organisationnel pour votre entreprise',
                    'Coordination avec les professionnels compétents lorsque nécessaire',
                ],
                'note' => 'Cet accompagnement ne remplace pas le conseil d’un avocat ou d’un expert-comptable.',
            ],
            [
                'id' => 'investisseurs',
                'title' => 'Services complémentaires pour investisseurs et entrepreneurs',
                'summary' => 'Investisseurs étrangers, compte bancaire, structuration, transfert ou modification de siège.',
                'intro' => 'Vous investissez au Maroc depuis l’étranger ou votre société évolue ? Nesel vous accompagne dans les étapes clés.',
                'items' => [
                    'Assistance aux investisseurs étrangers',
                    'Accompagnement pour l’ouverture d’un compte bancaire professionnel',
                    'Conseil en structuration d’entreprise',
                    'Modification ou transfert de siège social',
                    'Accompagnement lors de l’évolution de votre entreprise',
                ],
                'note' => 'L’ouverture d’un compte bancaire reste soumise à la décision de la banque.',
            ],
        ];
    }

    /**
     * @return list<array{name: string, subtitle: string, teaser: string, description: string, included: list<string>, optional: list<string>, optional_label: string}>
     */
    public static function offers(): array
    {
        return [
            [
                'name' => 'Silver',
                'subtitle' => 'Domiciliation administrative essentielle',
                'teaser' => 'L’essentiel pour installer votre siège social et démarrer votre activité.',
                'description' => 'La solution de domiciliation fondamentale, pensée pour les entrepreneurs qui lancent leur activité.',
                'included' => [
                    'Adresse de siège social professionnelle',
                    'Contrat de domiciliation',
                    'Attestation de domiciliation',
                    'Réception du courrier',
                    'Notification par email',
                    'Mise à disposition du courrier en agence',
                    'Accompagnement de base au lancement et à l’organisation de l’entreprise',
                    'Accompagnement administratif standard',
                ],
                'optional' => [
                    'Réexpédition du courrier',
                    'Scan numérique du courrier',
                    'Location de salle de réunion',
                    'Permanence téléphonique',
                    'Accompagnement lors de l’évolution de votre entreprise',
                ],
                'optional_label' => 'Services complémentaires',
            ],
            [
                'name' => 'Golden',
                'subtitle' => 'Domiciliation exécutive et gestion administrative renforcée',
                'teaser' => 'Pour les entreprises qui souhaitent déléguer une plus grande part de leur gestion administrative.',
                'description' => 'Pensée pour les entreprises qui veulent déléguer une plus grande part de leur gestion administrative.',
                'included' => [
                    'Tous les services de l’offre Silver',
                    'Scan et transmission numérique du courrier important',
                    'Réexpédition mensuelle du courrier',
                    'Numéro professionnel dédié',
                    'Permanence téléphonique avec prise de messages',
                    'Accès aux salles de réunion (quota mensuel selon les conditions de l’offre)',
                    'Accompagnement lors de l’évolution de votre entreprise',
                    'Accompagnement administratif de l’entreprise',
                    'Support prioritaire',
                ],
                'optional' => [
                    'Standard personnalisé au nom de votre société',
                    'Réexpédition illimitée',
                    'Gestion administrative externalisée',
                    'Coordination avec des professionnels partenaires pour les questions fiscales',
                    'Interlocuteur dédié',
                ],
                'optional_label' => 'Services complémentaires',
            ],
            [
                'name' => 'Diamond',
                'subtitle' => 'Domiciliation Corporate Premium et accompagnement renforcé',
                'teaser' => 'Notre niveau de service le plus complet, avec un interlocuteur dédié en permanence.',
                'description' => 'Le niveau de service le plus complet de Nesel, pour les entreprises qui veulent confier l’essentiel de leur gestion administrative.',
                'included' => [
                    'Tous les services de l’offre Golden',
                    'Réexpédition illimitée et prioritaire du courrier',
                    'Standard personnalisé au nom de votre société',
                    'Numéro dédié exclusif',
                    'Accueil physique de vos partenaires et clients',
                    'Accès prioritaire étendu aux salles de réunion',
                    'Suivi renforcé de votre projet de lancement d’activité',
                    'Coordination des démarches liées à l’évolution de l’entreprise',
                    'Coordination avec un expert-comptable partenaire',
                    'Support documentaire et coordination avec les professionnels compétents',
                    'Gestion administrative externalisée',
                    'Interlocuteur dédié permanent',
                ],
                'optional' => [
                    'Accompagnement administratif renforcé',
                    'Accompagnement bancaire',
                    'Support documentaire pour les appels d’offres',
                    'Secrétariat externalisé',
                    'Accompagnement administratif récurrent',
                ],
                'optional_label' => 'Services premium complémentaires',
            ],
        ];
    }

    /**
     * Comparison rows. Each cell is [state, detail]: state is "included",
     * "optional" or "none"; detail is an optional precision for the cell.
     *
     * @return list<array{label: string, cells: array{0: array{string, ?string}, 1: array{string, ?string}, 2: array{string, ?string}}}>
     */
    public static function comparison(): array
    {
        $included = fn (?string $detail = null): array => ['included', $detail];
        $optional = ['optional', null];
        $none = ['none', null];

        return [
            ['label' => 'Adresse professionnelle', 'cells' => [$included(), $included(), $included()]],
            ['label' => 'Contrat de domiciliation', 'cells' => [$included(), $included(), $included()]],
            ['label' => 'Attestation de domiciliation', 'cells' => [$included(), $included(), $included()]],
            ['label' => 'Réception du courrier', 'cells' => [$included(), $included(), $included()]],
            ['label' => 'Notification', 'cells' => [$included('Par email'), $included(), $included()]],
            ['label' => 'Scan du courrier', 'cells' => [$optional, $included('Courrier important'), $included()]],
            ['label' => 'Réexpédition', 'cells' => [$optional, $included('Mensuelle'), $included('Illimitée et prioritaire')]],
            ['label' => 'Numéro professionnel', 'cells' => [$none, $included('Dédié'), $included('Dédié exclusif')]],
            ['label' => 'Standard personnalisé', 'cells' => [$none, $optional, $included()]],
            ['label' => 'Permanence téléphonique', 'cells' => [$optional, $included('Avec prise de messages'), $included()]],
            ['label' => 'Salle de réunion', 'cells' => [$optional, $included('Quota mensuel selon les conditions de l’offre'), $included('Accès prioritaire étendu')]],
            ['label' => 'Évolution de l’entreprise', 'cells' => [$optional, $included('Accompagnement'), $included('Coordination renforcée')]],
            ['label' => 'Support prioritaire', 'cells' => [$none, $included(), $included()]],
            ['label' => 'Interlocuteur dédié', 'cells' => [$none, $optional, $included('Permanent')]],
            ['label' => 'Gestion administrative externalisée', 'cells' => [$none, $optional, $included()]],
            ['label' => 'Accueil des clients', 'cells' => [$none, $none, $included()]],
            ['label' => 'Accompagnement au lancement', 'cells' => [$included('Accompagnement de base'), $included('Accompagnement de base'), $included('Suivi renforcé')]],
        ];
    }
}
