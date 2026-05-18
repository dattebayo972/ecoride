<?php
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
$pageTitle = 'Mentions légales';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-5">
<div class="container" style="max-width:800px">
    <h2 class="fw-bold text-eco mb-4">Mentions légales</h2>

    <h5 class="fw-semibold mt-4">Éditeur du site</h5>
    <p>EcoRide SAS — Startup française de covoiturage écologique<br>
    Email : contact@ecoride.fr</p>

    <h5 class="fw-semibold mt-4">Hébergement</h5>
    <p>Le site est hébergé sur une plateforme cloud (Railway / Heroku).</p>

    <h5 class="fw-semibold mt-4">Propriété intellectuelle</h5>
    <p>L'ensemble du contenu du site EcoRide (textes, images, logos) est protégé par le droit d'auteur.
    Toute reproduction sans autorisation est interdite.</p>

    <h5 class="fw-semibold mt-4">Protection des données personnelles (RGPD)</h5>
    <p>EcoRide collecte des données personnelles dans le cadre de la gestion des comptes et des trajets.
    Conformément au RGPD, vous disposez d'un droit d'accès, de rectification et de suppression de vos données.
    Pour exercer ces droits, contactez-nous à : contact@ecoride.fr</p>

    <h5 class="fw-semibold mt-4">Cookies</h5>
    <p>Ce site utilise uniquement des cookies de session nécessaires au bon fonctionnement de l'application.
    Aucun cookie de traçage publicitaire n'est utilisé.</p>

    <h5 class="fw-semibold mt-4">Responsabilité</h5>
    <p>EcoRide n'est pas responsable des dommages directs ou indirects liés à l'utilisation du site.
    Les trajets sont organisés entre particuliers, EcoRide agit uniquement comme intermédiaire.</p>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
