<?php
// US3 — Vue des covoiturages | US4 — Filtres
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/CovoiturageModel.php';

$depart  = trim($_GET['depart']  ?? '');
$arrivee = trim($_GET['arrivee'] ?? '');
$date    = $_GET['date'] ?? date('Y-m-d');

// Filtres US4
$filters = [
    'ecologique' => !empty($_GET['ecologique']),
    'prix_max'   => !empty($_GET['prix_max'])   ? (float)$_GET['prix_max']   : null,
    'duree_max'  => !empty($_GET['duree_max'])  ? (int)$_GET['duree_max']   : null,
    'note_min'   => !empty($_GET['note_min'])   ? (float)$_GET['note_min']  : null,
];

$results    = [];
$searched   = false;
$suggestion = null;

if ($depart && $arrivee) {
    $searched = true;
    $results  = CovoiturageModel::search($depart, $arrivee, $date, $filters);
    if (empty($results)) {
        $suggestion = CovoiturageModel::nextAvailable($depart, $arrivee, $date);
    }
}

$pageTitle = 'Covoiturages';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container">

    <!-- Barre de recherche -->
    <div class="card shadow-sm border-0 rounded-3 mb-4">
        <div class="card-body bg-eco-light rounded-3">
            <form method="GET" class="row g-2 align-items-end" id="searchForm">
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Départ</label>
                    <input type="text" name="depart" class="form-control"
                           value="<?= h($depart) ?>" placeholder="Ville de départ" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Arrivée</label>
                    <input type="text" name="arrivee" class="form-control"
                           value="<?= h($arrivee) ?>" placeholder="Ville d'arrivée" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Date</label>
                    <input type="date" name="date" class="form-control" value="<?= h($date) ?>">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-eco w-100 fw-semibold">
                        <i class="bi bi-search me-1"></i>Rechercher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($searched): ?>
    <div class="row g-3">

        <!-- Filtres US4 -->
        <div class="col-lg-3">
            <div class="sidebar-filters">
                <h6><i class="bi bi-funnel-fill me-2"></i>Filtres</h6>
                <form method="GET" id="filterForm">
                    <input type="hidden" name="depart"  value="<?= h($depart) ?>">
                    <input type="hidden" name="arrivee" value="<?= h($arrivee) ?>">
                    <input type="hidden" name="date"    value="<?= h($date) ?>">

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ecologique"
                                   id="eco" value="1" <?= !empty($filters['ecologique']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="eco">
                                <i class="bi bi-lightning-charge-fill text-success me-1"></i>Écologique uniquement
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Prix max (crédits)</label>
                        <input type="number" name="prix_max" class="form-control form-control-sm"
                               value="<?= h($_GET['prix_max'] ?? '') ?>"
                               min="0" placeholder="Ex : 20">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Durée max (minutes)</label>
                        <input type="number" name="duree_max" class="form-control form-control-sm"
                               value="<?= h($_GET['duree_max'] ?? '') ?>"
                               min="0" placeholder="Ex : 180">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Note min du chauffeur</label>
                        <select name="note_min" class="form-select form-select-sm">
                            <option value="">Toutes</option>
                            <?php foreach ([1,2,3,4,5] as $n): ?>
                                <option value="<?= $n ?>" <?= (($_GET['note_min'] ?? '') == $n) ? 'selected' : '' ?>>
                                    <?= str_repeat('★', $n) ?> et +
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-eco btn-sm w-100">Appliquer</button>
                    <a href="<?= BASE_URL ?>/covoiturages.php?depart=<?= urlencode($depart) ?>&arrivee=<?= urlencode($arrivee) ?>&date=<?= h($date) ?>"
                       class="btn btn-outline-secondary btn-sm w-100 mt-2">Réinitialiser</a>
                </form>
            </div>
        </div>

        <!-- Résultats -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">
                    <?= h($depart) ?> → <?= h($arrivee) ?>
                    <small class="text-muted fw-normal">— <?= formatDate($date) ?></small>
                </h5>
                <span class="badge bg-eco fs-6"><?= count($results) ?> trajet(s)</span>
            </div>

            <?php if (empty($results)): ?>
                <div class="alert alert-warning d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                    <div>
                        Aucun covoiturage disponible pour cette date.
                        <?php if ($suggestion): ?>
                            Prochain trajet disponible le
                            <a href="?depart=<?= urlencode($depart) ?>&arrivee=<?= urlencode($arrivee) ?>&date=<?= h($suggestion['prochaine']) ?>">
                                <strong><?= formatDate($suggestion['prochaine']) ?></strong>
                            </a> — cliquez pour afficher.
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($results as $c): ?>
                <div class="card card-cov mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <img src="<?= h(avatarUrl($c['photo'])) ?>"
                                 alt="Photo" class="avatar me-3">
                            <div>
                                <strong><?= h($c['pseudo']) ?></strong>
                                <div class="stars small">
                                    <?= starRating((float)($c['note_moy'] ?? 0)) ?>
                                    <span class="text-muted ms-1">
                                        <?= $c['note_moy'] ? number_format($c['note_moy'],1) : 'N/A' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-end">
                            <?= ecoLabel($c['energie']) ?>
                            <div class="mt-1">
                                <span class="fw-bold text-eco fs-5"><?= $c['prix_personne'] ?></span>
                                <small class="text-muted"> crédits</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <div class="d-flex align-items-center">
                                    <div class="text-center me-3">
                                        <div class="fw-bold"><?= formatTime($c['heure_depart']) ?></div>
                                        <small class="text-muted"><?= h($c['lieu_depart']) ?></small>
                                    </div>
                                    <div class="flex-grow-1 text-center">
                                        <hr class="my-0 border-2 border-eco">
                                        <small class="text-muted">
                                            <?php
                                            $debut = new DateTime($c['date_depart'].' '.$c['heure_depart']);
                                            $fin   = new DateTime($c['date_arrivee'].' '.$c['heure_arrivee']);
                                            $diff  = $debut->diff($fin);
                                            echo $diff->h.'h'.($diff->i ? $diff->i.'m' : '');
                                            ?>
                                        </small>
                                    </div>
                                    <div class="text-center ms-3">
                                        <div class="fw-bold"><?= formatTime($c['heure_arrivee']) ?></div>
                                        <small class="text-muted"><?= h($c['lieu_arrivee']) ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <span class="badge bg-eco-light text-eco fs-6">
                                    <i class="bi bi-people-fill me-1"></i>
                                    <?= $c['nb_place'] ?> place(s) disponible(s)
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="<?= BASE_URL ?>/detail.php?id=<?= $c['covoiturage_id'] ?>"
                                   class="btn btn-eco px-4">
                                    Détail <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php else: ?>
        <!-- Pas encore de recherche -->
        <div class="text-center py-5">
            <i class="bi bi-car-front-fill text-eco" style="font-size:4rem"></i>
            <h4 class="mt-3 fw-bold">Trouvez votre trajet</h4>
            <p class="text-muted">Renseignez votre ville de départ, d'arrivée et la date souhaitée.</p>
        </div>
    <?php endif; ?>

</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
