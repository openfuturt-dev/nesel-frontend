<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Nouvelle demande de domiciliation</title>
    </head>
    <body style="margin: 0; background: #f7f5f1; color: #061832; font-family: Arial, sans-serif;">
        <div style="padding: 32px 16px;">
            <div style="max-width: 640px; margin: 0 auto; background: #ffffff; border-top: 5px solid #e30613;">
                <div style="padding: 32px;">
                    <p style="margin: 0 0 8px; color: #e30613; font-size: 12px; font-weight: 700; letter-spacing: 1.4px; text-transform: uppercase;">Nesel · Nouvelle demande</p>
                    <h1 style="margin: 0 0 28px; color: #061832; font-size: 26px; line-height: 1.25;">Demande de domiciliation à {{ $city }}</h1>

                    <table role="presentation" style="width: 100%; border-collapse: collapse; font-size: 15px; line-height: 1.6;">
                        <tr>
                            <td style="width: 150px; padding: 10px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">Nom complet</td>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-weight: 700; vertical-align: top;">{{ $name }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">E-mail</td>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-weight: 700; vertical-align: top;">{{ $email }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">Téléphone</td>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-weight: 700; vertical-align: top;">{{ $phone }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">Ville souhaitée</td>
                            <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-weight: 700; vertical-align: top;">{{ $city }}</td>
                        </tr>
                        @if ($offer)
                            <tr>
                                <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; vertical-align: top;">Offre souhaitée</td>
                                <td style="padding: 10px 0; border-bottom: 1px solid #e2e8f0; font-weight: 700; vertical-align: top;">{{ $offer === 'Conseil' ? 'Souhaite être conseillé' : $offer }}</td>
                            </tr>
                        @endif
                    </table>

                    <h2 style="margin: 28px 0 8px; font-size: 16px;">Besoin exprimé</h2>
                    <p style="margin: 0; color: #475569; font-size: 15px; line-height: 1.7; white-space: pre-line;">{{ $details ?: 'Aucun détail supplémentaire.' }}</p>
                </div>
            </div>
        </div>
    </body>
</html>
