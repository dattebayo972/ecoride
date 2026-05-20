<?php
// US12 — Espace Employé
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/AvisModel.php';
require_once __DIR__ . '/../src/models/CovoiturageModel.php';

requireLogin();
requireRole('employe');
requireActive();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $avisId = (int)($_POST['avis_id'] ?? 0);
    if ($action === 'valider_avis') {
        AvisModel::validate($avisId);
        setFlash('success', 'Avis validé et publié.');
    }
    if ($action === 'refuser_avis') {
        AvisModel::refuse($avisId);
        setFlash('success', 'Avis refusé.');
    }
    if ($action === 'traiter_litige') {
        $passagerId    = (int)($_POST['passager_id']    ?? 0);
        $covoiturageId = (int)($_POST['covoiturage_id'] ?? 0);
        if ($passagerId && $covoiturageId) {
            CovoiturageModel::resolveLitige($passagerId, $covoiturageId);
            setFlash('success', 'Litige marqué comme traité.');
        }
    }
    redirect(BASE_URL . '/espace-employe.php');
}

$avisEnAttente = AvisModel::getPending();
$litiges       = CovoiturageModel::getLitiges();

$pageTitle = 'Espace Employé';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container">
    <?= renderFlash() ?>
    <h3 class="fw-bold text-eco mb-4">
        <i class="bi bi-briefcase me-2"></i>Espace Employé
    </h3>

    <div class="row g-4">

        <!-- ── Statistiques rapides ──────────────────────────── -->
        <div class="col-md-4">
            <div class="card dash-card text-center border-start border-4 border-warning">
                <div class="card-body">
                    <div class="stat-badge text-warning"><?= count($avisEnAttente) ?></div>
                    <div class="text-muted">Avis en attente</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dash-card text-center border-start border-4 border-danger">
                <div class="card-body">
                    <div class="stat-badge text-danger"><?= count($litiges) ?></div>
                    <div class="text-muted">Litiges à traiter</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card dash-card text-center border-start border-4 border-eco">
                <div class="card-body">
                    <div class="stat-badge"><?= count($avisEnAttente) + count($litiges) ?></div>
                    <div class="text-muted">Actions requises</div>
                </div>
            </div>
        </div>

        <!-- ── Avis à valider ────────────────────────────────── -->
        <div class="col-12">
            <div class="card dash-card">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-chat-square-text me-2"></i>Avis en attente de modération
                    <span class="badge bg-warning text-dark ms-2"><?= count($avisEnAttente) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($avisEnAttente)): ?>
                        <p class="text-muted text-center py-4">Aucun avis en attente.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-eco table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Passager</th>
                                    <th>Chauffeur</th>
                                    <th>Note</th>
                                    <th>Commentaire</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($avisEnAttente as $a): ?>
                                <tr>
                                    <td><?= h($a['passager_pseudo']) ?></td>
                                    <td><?= h($a['chauffeur_pseudo']) ?></td>
                                    <td><?= starRating((int)$a['note']) ?></td>
                                    <td class="text-truncate" style="max-width:200px">
                                        <?= h($a['commentaire']) ?>
                                    </td>
                                    <td class="text-muted small">
                                        <?= formatDate(substr($a['created_at'],0,10)) ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action"  value="valider_avis">
                                            <input type="hidden" name="avis_id" value="<?= $a['avis_id'] ?>">
                                            <button class="btn btn-success btn-sm me-1">
                                                <i class="bi bi-check-lg"></i> Valider
                                            </button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action"  value="refuser_avis">
                                            <input type="hidden" name="avis_id" value="<?= $a['avis_id'] ?>">
                                            <button class="btn btn-danger btn-sm">
                                                <i class="bi bi-x-lg"></i> Refuser
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ── Litiges ───────────────────────────────────────── -->
        <div class="col-12">
            <div class="card dash-card">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-exclamation-triangle me-2"></i>Litiges signalés
                    <span class="badge bg-danger ms-2"><?= count($litiges) ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($litiges)): ?>
                        <p class="text-muted text-center py-4">Aucun litige en cours.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-eco table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Départ</th>
                                    <th>Arrivée</th>
                                    <th>Date</th>
                                    <th>Chauffeur</th>
                                    <th>Passager</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($litiges as $l): ?>
                                <tr>
                                    <td><?= $l['covoiturage_id'] ?></td>
                                    <td><?= h($l['lieu_depart']) ?></td>
                                    <td><?= h($l['lieu_arrivee']) ?></td>
                                    <td><?= formatDate($l['date_depart']) ?></td>
                                    <td>
                                        <strong><?= h($l['chauffeur_pseudo']) ?></strong><br>
                                        <a href="mailto:<?= h($l['chauffeur_email']) ?>" class="text-muted small">
                                            <?= h($l['chauffeur_email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong><?= h($l['passager_pseudo']) ?></strong><br>
                                        <a href="mailto:<?= h($l['passager_email']) ?>" class="text-muted small">
                                            <?= h($l['passager_email']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action"         value="traiter_litige">
                                            <input type="hidden" name="passager_id"    value="<?= $l['passager_id'] ?>">
                                            <input type="hidden" name="covoiturage_id" value="<?= $l['covoiturage_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning">
                                                <i class="bi bi-check2-circle me-1"></i>Traiter
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
