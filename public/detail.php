<?php
// US5 — Vue détaillée | US6 — Participer
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/CovoiturageModel.php';
require_once __DIR__ . '/../src/models/UserModel.php';
require_once __DIR__ . '/../src/models/AvisModel.php';

$id = (int)($_GET['id'] ?? 0);
$covoiturage = CovoiturageModel::findById($id);
if (!$covoiturage) {
    http_response_code(404);
    die('Covoiturage introuvable.');
}

$message = '';
$msgType = 'success';

// US6 — Action participer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'participer') {
    verifyCsrf();
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
    $userId = currentUserId();
    if ($covoiturage['nb_place'] < 1) {
        $message = 'Plus aucune place disponible.';
        $msgType = 'danger';
    } elseif ($_SESSION['credits'] < $covoiturage['prix_personne']) {
        $message = 'Crédits insuffisants pour participer à ce trajet.';
        $msgType = 'danger';
    } elseif (CovoiturageModel::isParticipant($userId, $id)) {
        $message = 'Vous participez déjà à ce trajet.';
        $msgType = 'warning';
    } elseif ($userId === $covoiturage['chauffeur_id']) {
        $message = 'Vous ne pouvez pas participer à votre propre trajet.';
        $msgType = 'warning';
    } else {
        // Débiter les crédits
        UserModel::updateCredits($userId, -(int)$covoiturage['prix_personne']);
        // Enregistrer participation
        CovoiturageModel::addParticipant($userId, $id);
        // Décrémenter place
        CovoiturageModel::decrementPlace($id);
        // Mettre à jour session crédits
        refreshCredits($_SESSION['credits'] - (int)$covoiturage['prix_personne']);
        // Recharger le covoiturage
        $covoiturage = CovoiturageModel::findById($id);
        $message = 'Participation confirmée ! Bon voyage 🌿';
        $msgType = 'success';
    }
}

$avis       = AvisModel::getByChauffeur((int)$covoiturage['chauffeur_id']);
$prefs      = UserModel::getPreferences((int)$covoiturage['chauffeur_id']);
$prefsLibre = UserModel::getPreferencesLibres((int)$covoiturage['chauffeur_id']);
$isParticipant = isLoggedIn() && CovoiturageModel::isParticipant(currentUserId(), $id);
$isChauffeur   = isLoggedIn() && (currentUserId() === (int)$covoiturage['chauffeur_id']);

$pageTitle = 'Détail du trajet';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container" style="max-width:900px">

    <!-- Retour -->
    <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm mb-4">
        <i class="bi bi-arrow-left me-1"></i>Retour
    </a>

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show">
            <?= h($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Card principale -->
    <div class="card shadow border-0 rounded-3 mb-4">
        <div class="card-header bg-eco text-white py-3">
            <div class="row align-items-center">
                <div class="col">
                    <h5 class="mb-0 fw-bold">
                        <i class="bi bi-geo-alt me-2"></i>
                        <?= h($covoiturage['lieu_depart']) ?> → <?= h($covoiturage['lieu_arrivee']) ?>
                    </h5>
                    <small class="opacity-75">
                        <?= formatDate($covoiturage['date_depart']) ?> ·
                        <?= formatTime($covoiturage['heure_depart']) ?> → <?= formatTime($covoiturage['heure_arrivee']) ?>
                    </small>
                </div>
                <div class="col-auto">
                    <?= ecoLabel($covoiturage['energie']) ?>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">

                <!-- Infos trajet -->
                <div class="col-md-7">
                    <h6 class="fw-bold text-eco mb-3">Informations du trajet</h6>
                    <dl class="row">
                        <dt class="col-sm-5 text-muted">Date départ</dt>
                        <dd class="col-sm-7"><?= formatDate($covoiturage['date_depart']) ?></dd>
                        <dt class="col-sm-5 text-muted">Heure départ</dt>
                        <dd class="col-sm-7"><?= formatTime($covoiturage['heure_depart']) ?></dd>
                        <dt class="col-sm-5 text-muted">Heure arrivée</dt>
                        <dd class="col-sm-7"><?= formatTime($covoiturage['heure_arrivee']) ?></dd>
                        <dt class="col-sm-5 text-muted">Places disponibles</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-eco-light text-eco">
                                <?= $covoiturage['nb_place'] ?> place(s)
                            </span>
                        </dd>
                        <dt class="col-sm-5 text-muted">Prix</dt>
                        <dd class="col-sm-7 fw-bold text-eco fs-5"><?= $covoiturage['prix_personne'] ?> crédits</dd>
                    </dl>

                    <!-- Véhicule -->
                    <h6 class="fw-bold text-eco mt-3 mb-2">Véhicule</h6>
                    <p class="mb-1">
                        <i class="bi bi-car-front me-2 text-muted"></i>
                        <?= h($covoiturage['marque']) ?> <?= h($covoiturage['modele']) ?>
                        <span class="text-muted">(<?= h($covoiturage['couleur']) ?>)</span>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-fuel-pump me-2 text-muted"></i>
                        <?= ucfirst(h($covoiturage['energie'])) ?>
                    </p>
                </div>

                <!-- Conducteur -->
                <div class="col-md-5">
                    <h6 class="fw-bold text-eco mb-3">Conducteur</h6>
                    <div class="text-center mb-3">
                        <img src="<?= h(avatarUrl($covoiturage['photo'])) ?>"
                             alt="Photo du chauffeur" class="avatar-lg mb-2">
                        <div class="fw-bold"><?= h($covoiturage['pseudo']) ?></div>
                        <div class="stars">
                            <?= starRating((float)($covoiturage['note_moy'] ?? 0)) ?>
                            <span class="text-muted ms-1">
                                <?= $covoiturage['note_moy'] ? number_format($covoiturage['note_moy'],1) : 'N/A' ?>
                            </span>
                        </div>
                    </div>

                    <!-- Préférences -->
                    <?php if ($prefs || $prefsLibre): ?>
                    <div class="mt-3">
                        <h6 class="fw-semibold text-eco small">Préférences</h6>
                        <ul class="list-unstyled small text-muted">
                            <?php foreach ($prefs as $p): ?>
                                <li><i class="bi bi-check2 me-1 text-eco"></i>
                                    <?= h(ucfirst($p['propriete'])) ?> : <?= h($p['valeur']) ?>
                                </li>
                            <?php endforeach; ?>
                            <?php foreach ($prefsLibre as $t): ?>
                                <li><i class="bi bi-check2 me-1 text-eco"></i><?= h($t) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bouton participer US6 -->
            <hr>
            <div class="text-center">
                <?php if ($covoiturage['statut'] !== 'planifie'): ?>
                    <span class="badge bg-secondary fs-6">Trajet <?= h($covoiturage['statut']) ?></span>
                <?php elseif ($isChauffeur): ?>
                    <span class="text-muted">Vous êtes le chauffeur de ce trajet.</span>
                <?php elseif ($isParticipant): ?>
                    <span class="badge bg-success fs-6"><i class="bi bi-check-circle me-1"></i>Vous participez à ce trajet</span>
                <?php elseif ($covoiturage['nb_place'] < 1): ?>
                    <span class="badge bg-danger fs-6">Complet</span>
                <?php elseif (!isLoggedIn()): ?>
                    <a href="<?= BASE_URL ?>/connexion.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>"
                       class="btn btn-eco btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter pour participer
                    </a>
                <?php else: ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="participer">
                        <p class="text-muted small mb-2">
                            Cette participation coûte <strong><?= $covoiturage['prix_personne'] ?> crédits</strong>.
                            Vous en avez <strong><?= $_SESSION['credits'] ?></strong>.
                        </p>
                        <button type="button" id="btnParticiper"
                                data-prix="<?= $covoiturage['prix_personne'] ?>"
                                class="btn btn-eco btn-lg px-5"
                                onclick="if(confirm('Confirmer la participation ? <?= $covoiturage['prix_personne'] ?> crédit(s) seront débités.')) this.form.submit()">
                            <i class="bi bi-car-front me-2"></i>Participer
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Avis du conducteur -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header bg-eco-light">
            <h6 class="mb-0 fw-bold text-eco">
                <i class="bi bi-chat-quote me-2"></i>Avis sur le conducteur
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($avis)): ?>
                <p class="text-muted text-center my-3">Aucun avis validé pour ce conducteur.</p>
            <?php else: ?>
                <?php foreach ($avis as $a): ?>
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <strong><?= h($a['passager_pseudo']) ?></strong>
                        <div class="stars"><?= starRating((int)$a['note']) ?></div>
                    </div>
                    <p class="text-muted small mb-0"><?= h($a['commentaire']) ?></p>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
