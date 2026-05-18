<?php
// US9 — Saisir un voyage (chauffeur)
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/CovoiturageModel.php';
require_once __DIR__ . '/../src/models/VoitureModel.php';
require_once __DIR__ . '/../src/models/MarqueModel.php';

requireLogin();
requireRole('chauffeur');
requireActive();

$userId   = currentUserId();
$voitures = VoitureModel::getByUser($userId);
$marques  = MarqueModel::getAll();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $action = $_POST['action'] ?? 'create_trip';

    // Ajouter une nouvelle voiture dans le même formulaire
    if ($action === 'add_voiture_inline') {
        VoitureModel::create([
            'modele'                        => trim($_POST['modele']),
            'immatriculation'               => trim(strtoupper($_POST['immatriculation'])),
            'energie'                       => $_POST['energie'],
            'couleur'                       => trim($_POST['couleur'] ?? ''),
            'date_premiere_immatriculation' => $_POST['date_premiere_immatriculation'] ?? '',
            'nb_place'                      => (int)$_POST['nb_place_voiture'],
            'utilisateur_id'                => $userId,
            'marque_id'                     => (int)$_POST['marque_id'],
        ]);
        $voitures = VoitureModel::getByUser($userId);
        setFlash('success', 'Véhicule ajouté. Vous pouvez maintenant saisir votre trajet.');
        redirect(BASE_URL . '/saisir-voyage.php');
    }

    // Créer le trajet
    $depart    = trim($_POST['lieu_depart'] ?? '');
    $arrivee   = trim($_POST['lieu_arrivee'] ?? '');
    $dDepart   = $_POST['date_depart'] ?? '';
    $hDepart   = $_POST['heure_depart'] ?? '';
    $dArrivee  = $_POST['date_arrivee'] ?? '';
    $hArrivee  = $_POST['heure_arrivee'] ?? '';
    $prix      = (float)($_POST['prix_personne'] ?? 0);
    $nbPlace   = (int)($_POST['nb_place'] ?? 0);
    $voitureId = (int)($_POST['voiture_id'] ?? 0);

    if (!$depart)   $errors[] = 'Lieu de départ requis.';
    if (!$arrivee)  $errors[] = 'Lieu d\'arrivée requis.';
    if (!$dDepart)  $errors[] = 'Date de départ requise.';
    if (!$hDepart)  $errors[] = 'Heure de départ requise.';
    if (!$dArrivee) $errors[] = 'Date d\'arrivée requise.';
    if (!$hArrivee) $errors[] = 'Heure d\'arrivée requise.';
    if ($prix <= 0) $errors[] = 'Le prix doit être supérieur à 0.';
    if ($nbPlace < 1) $errors[] = 'Au moins 1 place requise.';
    if (!$voitureId) $errors[] = 'Sélectionnez un véhicule.';

    if (!$errors) {
        CovoiturageModel::create([
            'date_depart'   => $dDepart,
            'heure_depart'  => $hDepart,
            'lieu_depart'   => $depart,
            'date_arrivee'  => $dArrivee,
            'heure_arrivee' => $hArrivee,
            'lieu_arrivee'  => $arrivee,
            'nb_place'      => $nbPlace,
            'prix_personne' => $prix,
            'voiture_id'    => $voitureId,
            'chauffeur_id'  => $userId,
        ]);
        setFlash('success', 'Trajet publié avec succès !');
        redirect(BASE_URL . '/historique.php');
    }
}

$pageTitle = 'Proposer un trajet';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container" style="max-width:700px">
    <?= renderFlash() ?>
    <h3 class="fw-bold text-eco mb-4">
        <i class="bi bi-plus-circle me-2"></i>Proposer un trajet
    </h3>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".h($e)."</li>"; ?></ul>
        </div>
    <?php endif; ?>

    <!-- Note plateforme -->
    <div class="alert alert-info d-flex align-items-center mb-4">
        <i class="bi bi-info-circle-fill me-3 fs-4"></i>
        <div>
            <strong>Information :</strong> 2 crédits sont prélevés par EcoRide à chaque trajet effectué.
            Fixez votre prix en conséquence.
        </div>
    </div>

    <div class="card shadow border-0 rounded-3">
        <div class="card-body p-4">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_trip">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Lieu de départ <span class="text-danger">*</span></label>
                        <input type="text" name="lieu_depart" class="form-control"
                               value="<?= h($_POST['lieu_depart'] ?? '') ?>"
                               placeholder="Ville de départ" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Lieu d'arrivée <span class="text-danger">*</span></label>
                        <input type="text" name="lieu_arrivee" class="form-control"
                               value="<?= h($_POST['lieu_arrivee'] ?? '') ?>"
                               placeholder="Ville d'arrivée" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date de départ <span class="text-danger">*</span></label>
                        <input type="date" name="date_depart" class="form-control"
                               value="<?= h($_POST['date_depart'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Heure de départ <span class="text-danger">*</span></label>
                        <input type="time" name="heure_depart" class="form-control"
                               value="<?= h($_POST['heure_depart'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date d'arrivée <span class="text-danger">*</span></label>
                        <input type="date" name="date_arrivee" class="form-control"
                               value="<?= h($_POST['date_arrivee'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Heure d'arrivée <span class="text-danger">*</span></label>
                        <input type="time" name="heure_arrivee" class="form-control"
                               value="<?= h($_POST['heure_arrivee'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Prix par personne (crédits) <span class="text-danger">*</span></label>
                        <input type="number" name="prix_personne" class="form-control"
                               value="<?= h($_POST['prix_personne'] ?? '') ?>"
                               min="1" step="0.5" placeholder="Ex: 15" required>
                        <div class="form-text text-muted">2 crédits prélevés par la plateforme.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Nombre de places <span class="text-danger">*</span></label>
                        <input type="number" name="nb_place" class="form-control"
                               value="<?= h($_POST['nb_place'] ?? '') ?>"
                               min="1" max="8" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-semibold">Véhicule <span class="text-danger">*</span></label>
                        <?php if ($voitures): ?>
                        <select name="voiture_id" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                            <?php foreach ($voitures as $v): ?>
                                <option value="<?= $v['voiture_id'] ?>">
                                    <?= h($v['marque']) ?> <?= h($v['modele']) ?>
                                    (<?= h($v['immatriculation']) ?>) — <?= h($v['energie']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            Vous n'avez pas encore de véhicule.
                            <a href="<?= BASE_URL ?>/espace-utilisateur.php">Ajouter un véhicule</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-eco btn-lg fw-semibold"
                            <?= empty($voitures) ? 'disabled' : '' ?>>
                        <i class="bi bi-check-circle me-2"></i>Publier le trajet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Ajouter un véhicule inline si aucun -->
    <?php if (empty($voitures)): ?>
    <div class="card shadow-sm border-0 rounded-3 mt-4">
        <div class="card-header bg-eco-light text-eco fw-semibold">
            <i class="bi bi-plus-circle me-1"></i>Ajouter un véhicule
        </div>
        <div class="card-body">
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="add_voiture_inline">
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label">Marque</label>
                        <select name="marque_id" class="form-select" required>
                            <?php foreach ($marques as $m): ?>
                                <option value="<?= $m['marque_id'] ?>"><?= h($m['libelle']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modèle</label>
                        <input type="text" name="modele" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Immatriculation</label>
                        <input type="text" name="immatriculation" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Énergie</label>
                        <select name="energie" class="form-select">
                            <option value="electrique">Électrique</option>
                            <option value="essence">Essence</option>
                            <option value="diesel">Diesel</option>
                            <option value="hybride">Hybride</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Places</label>
                        <input type="number" name="nb_place_voiture" class="form-control" value="4" min="1" max="9">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Couleur</label>
                        <input type="text" name="couleur" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">1ère immatriculation</label>
                        <input type="date" name="date_premiere_immatriculation" class="form-control">
                    </div>
                </div>
                <button type="submit" class="btn btn-eco mt-3">
                    <i class="bi bi-plus me-1"></i>Ajouter ce véhicule
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
