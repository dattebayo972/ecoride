<?php

function sendMail(string $to, string $subject, string $body): bool {
    $from     = $_ENV['MAIL_FROM']      ?? 'noreply@ecoride.fr';
    $fromName = $_ENV['MAIL_FROM_NAME'] ?? 'EcoRide';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";

    return mail($to, $subject, $body, $headers);
}

function mailAnnulationPassager(string $toEmail, string $pseudo, array $covoiturage): bool {
    $subject = '[EcoRide] Annulation de votre covoiturage';
    $body    = "
    <p>Bonjour {$pseudo},</p>
    <p>Le chauffeur a annulé le trajet suivant :</p>
    <ul>
        <li><strong>Départ :</strong> {$covoiturage['lieu_depart']} le {$covoiturage['date_depart']} à {$covoiturage['heure_depart']}</li>
        <li><strong>Arrivée :</strong> {$covoiturage['lieu_arrivee']}</li>
    </ul>
    <p>Vos crédits ont été remboursés.</p>
    <p>L'équipe EcoRide</p>";
    return sendMail($toEmail, $subject, $body);
}

function mailFinTrajet(string $toEmail, string $pseudo, int $covoiturageId): bool {
    $url     = ($_ENV['APP_URL'] ?? 'http://localhost/ecoride/public') . '/historique.php';
    $subject = '[EcoRide] Votre trajet est terminé — validez !';
    $body    = "
    <p>Bonjour {$pseudo},</p>
    <p>Votre trajet (n°{$covoiturageId}) est terminé.</p>
    <p>Merci de vous rendre sur votre espace pour valider le trajet et laisser un avis :</p>
    <p><a href='{$url}'>Accéder à mon historique</a></p>
    <p>L'équipe EcoRide</p>";
    return sendMail($toEmail, $subject, $body);
}

function mailLitige(string $toEmail, string $pseudo, int $covoiturageId, string $commentaire): bool {
    $subject = '[EcoRide] Litige signalé — trajet n°' . $covoiturageId;
    $body    = "
    <p>Bonjour {$pseudo},</p>
    <p>Un participant a signalé un problème sur le trajet n°{$covoiturageId}.</p>
    <p><strong>Commentaire :</strong> {$commentaire}</p>
    <p>Un employé EcoRide va vous contacter prochainement.</p>
    <p>L'équipe EcoRide</p>";
    return sendMail($toEmail, $subject, $body);
}
