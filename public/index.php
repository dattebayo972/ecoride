<?php
// US1 — Page d'accueil | US2 — Menu
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
$pageTitle = 'Accueil';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main>

    <!-- ── Hero ──────────────────────────────────────────────── -->
    <section class="hero">
        <div class="container text-center">
            <h1 class="mb-3 fw-bold">
                <i class="bi bi-tree-fill me-2"></i>Voyagez écologique avec EcoRide
            </h1>
            <p class="lead mb-4 opacity-75">
                La plateforme de covoiturage qui réduit votre empreinte carbone<br>
                et allège votre portefeuille.
            </p>

            <!-- Barre de recherche -->
            <div class="search-card mx-auto" style="max-width:700px">
                <form action="<?= BASE_URL ?>/covoiturages.php" method="GET">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label text-white small fw-semibold">Départ</label>
                            <input type="text" name="depart" class="form-control"
                                   placeholder="Ville de départ" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white small fw-semibold">Arrivée</label>
                            <input type="text" name="arrivee" class="form-control"
                                   placeholder="Ville d'arrivée" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-white small fw-semibold">Date</label>
                            <input type="date" name="date" class="form-control"
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-accent w-100 fw-semibold">
                                <i class="bi bi-search"></i> Chercher
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- ── Présentation ───────────────────────────────────────── -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold text-eco">Pourquoi choisir EcoRide ?</h2>
                <p class="text-muted">Une solution simple, économique et respectueuse de la planète</p>
            </div>
            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="feature-icon"><i class="bi bi-leaf-fill"></i></div>
                    <h5 class="fw-semibold">Écologique</h5>
                    <p class="text-muted">Réduisez vos émissions de CO₂ en partageant votre voiture.
                    Les trajets en véhicule électrique sont mis en avant.</p>
                </div>
                <div class="col-md-4">
                    <div class="feature-icon"><i class="bi bi-piggy-bank-fill"></i></div>
                    <h5 class="fw-semibold">Économique</h5>
                    <p class="text-muted">Partagez les frais de trajet et économisez chaque mois.
                    Les crédits EcoRide simplifient les échanges.</p>
                </div>
                <div class="col-md-4">
                    <div class="feature-icon"><i class="bi bi-people-fill"></i></div>
                    <h5 class="fw-semibold">Communauté</h5>
                    <p class="text-muted">Rejoignez une communauté de voyageurs engagés.
                    Avis vérifiés et conducteurs notés par nos équipes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Comment ça marche ──────────────────────────────────── -->
    <section class="py-5 bg-eco-light">
        <div class="container">
            <h2 class="fw-bold text-eco text-center mb-5">Comment ça marche ?</h2>
            <div class="row g-4 align-items-center">
                <div class="col-md-6">
                    <img src="<?= BASE_URL ?>/assets/img/covoiturage-illustration.svg"
                         alt="Illustration covoiturage" class="img-fluid rounded-3 shadow"
                         onerror="this.style.display='none'">
                </div>
                <div class="col-md-6">
                    <div class="d-flex mb-4">
                        <div class="feature-icon flex-shrink-0 me-3" style="width:48px;height:48px;font-size:1.2rem">1</div>
                        <div>
                            <h6 class="fw-bold">Recherchez un trajet</h6>
                            <p class="text-muted mb-0">Indiquez votre ville de départ, d'arrivée et la date souhaitée.</p>
                        </div>
                    </div>
                    <div class="d-flex mb-4">
                        <div class="feature-icon flex-shrink-0 me-3" style="width:48px;height:48px;font-size:1.2rem">2</div>
                        <div>
                            <h6 class="fw-bold">Choisissez votre conducteur</h6>
                            <p class="text-muted mb-0">Consultez les profils, avis et préférences des chauffeurs.</p>
                        </div>
                    </div>
                    <div class="d-flex">
                        <div class="feature-icon flex-shrink-0 me-3" style="width:48px;height:48px;font-size:1.2rem">3</div>
                        <div>
                            <h6 class="fw-bold">Voyagez et évaluez</h6>
                            <p class="text-muted mb-0">Participez au trajet et laissez un avis pour aider la communauté.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── CTA ────────────────────────────────────────────────── -->
    <section class="py-5 bg-eco text-white text-center">
        <div class="container">
            <h2 class="fw-bold mb-3">Prêt à partir ?</h2>
            <p class="mb-4 opacity-75">Rejoignez EcoRide et recevez 20 crédits offerts à l'inscription.</p>
            <?php if (!isLoggedIn()): ?>
            <a href="<?= BASE_URL ?>/inscription.php" class="btn btn-accent btn-lg px-5 me-2">
                <i class="bi bi-person-plus me-2"></i>Créer un compte
            </a>
            <a href="<?= BASE_URL ?>/covoiturages.php" class="btn btn-eco-outline btn-lg px-5">
                <i class="bi bi-car-front me-2"></i>Voir les trajets
            </a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/covoiturages.php" class="btn btn-accent btn-lg px-5">
                <i class="bi bi-search me-2"></i>Trouver un trajet
            </a>
            <?php endif; ?>
        </div>
    </section>

</main>

<?php include __DIR__ . '/../src/components/footer.php'; ?>
