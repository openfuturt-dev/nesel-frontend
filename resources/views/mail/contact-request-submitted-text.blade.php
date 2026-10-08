Nouvelle demande de domiciliation — Nesel

Nom complet : {{ $name }}
E-mail : {{ $email }}
Téléphone : {{ $phone }}
Ville souhaitée : {{ $city }}
@if ($offer)
Offre souhaitée : {{ $offer === 'Conseil' ? 'Souhaite être conseillé' : $offer }}
@endif

Besoin exprimé :
{{ $details ?: 'Aucun détail supplémentaire.' }}
