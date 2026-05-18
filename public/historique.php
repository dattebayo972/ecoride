<?php
// US10 — Historique | US11 — Démarrer/Arrêter
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/helpers/mail.php';
require_once __DIR__ . '/../src/models/CovoiturageModel.php';
require_once __DIR__ . '/../src/models/UserModel.php';
require_once __DIR__ . '/../src/models/AvisModel.php';

requireLogin();
requireActive();

$userId = currentUserId();
$user   = UserModel::findById($userId);
$roles  = UserModel::getRoles($userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $covId  = (int)($_POST['covoiturage_id'] ?? 0);
    $cov    = CovoiturageModel::findById($covId);

    // ── Annuler (passager)
    if ($action === 'annuler_participation' && $cov) {
        CovoiturageModel::cancelParticipation($userId, $covId);
        CovoiturageModel::incrementPlace($covId);
        UserModel::updateCredits($userId, (int)$cov['prix_personne']);
        refreshCredits($_SESSION['credits'] + (int)$cov['prix_personne']);
        setFlash('success', 'Participation annulée. Vos crédits ont été remboursés.');
    }

    // ── Annuler trajet (chauffeur)
    if ($action === 'annuler_trajet' && $cov && (int)$cov['chauffeur_id'] === $userId) {
        CovoiturageModel::updateStatut($covId, 'annule');
        $passagers = CovoiturageModel::getPassagers($covId);
        foreach ($passagers as $p) {
            UserModel::updateCredits((int)$p['utilisateur_id'], (int)$cov['prix_personne']);
            mailAnnulationPassager($p['email'], $p['pseudo'], $cov);
        }
        setFlash('success', 'Trajet annulé. Les passagers ont été remboursés et notifiés.');
    }

    // ── Démarrer trajet (chauffeur)
    if ($action === 'demarrer' && $cov && (int)$cov['chauffeur_id'] === $userId) {
        CovoiturageModel::updateStatut($covId, 'en_cours');
        setFlash('success', 'Trajet démarré !');
    }

    // ── Arrivée à destination (chauffeur)
    if ($action === 'terminer' && $cov && (int)$cov['chauffeur_id'] === $userId) {
        CovoiturageModel::updateStatut($covId, 'termine');
        // Créditer le chauffeur : (prix × passagers validés) - 2 crédits plateforme
        $passagers = getPDO()->prepare(
            "SELECT COUNT(*) as nb FROM participe WHERE covoiturage_id = ? AND statut = 'confirme'"
        );
        $passagers->execute([$covId]);
        $nbPass = (int) $passagers->fetch()['nb'];
        $gain   = (int)(($cov['prix_personne'] * $nbPass) - 2);
        if ($gain > 0) UserModel::updateCredits($userId, $gain);
        // Notifier passagers
        $listPass = CovoiturageModel::getPassagers($covId);
        foreach ($listPass as $p) {
            mailFinTrajet($p['email'], $p['pseudo'], $covId);
        }
        setFlash('success', 'Trajet terminé ! Les passagers ont été notifiés pour valider.');
    }

    // ── Valider trajet (passager)
    if ($action === 'valider_trajet') {
        CovoiturageModel::validateParticipation($userId, $covId);
        setFlash('success', 'Merci d\'avoir validé le trajet !');
    }

    // ── Signaler litige (passager)
    if ($action === 'litige') {
        $commentaire = trim($_POST['commentaire_litige'] ?? '');
        CovoiturageModel::setLitige($userId, $covId);
        $cov2 = CovoiturageModel::findById($covId);
        if ($cov2) {
            mailLitige($cov2['chauffeur_email'], $cov2['pseudo'], $covId, $commentaire);
        }
        setFlash('warning', 'Litige signalé. Un employé vous contactera prochainement.');
    }

    // ── Soumettre un avis (passager)
    if ($action === 'soumettre_avis') {
        $note        = (int)($_POST['note'] ?? 0);
        $commentaire = trim($_POST['commentaire'] ?? '');
        $cov3        = CovoiturageModel::findById($covId);
        if ($cov3 && $note >= 1 && $note <= 5) {
            AvisModel::create([
                'note'          => $note,
                'commentaire'   => $commentaire,
                'chauffeur_id'  => (int)$cov3['chauffeur_id'],
                'passager_id'   => $userId,
                'covoiturage_id' => $covId,
            ]);
            CovoiturageModel::markAvisEnvoye($userId, $covId);
            setFlash('success', 'Avis soumis. Il sera visible après validation par notre équipe.');
        }
    }

    redirect(BASE_URL . '/historique.php');
}


$trajets_chauffeur = in_array('chauffeur', $roles) ? CovoiturageModel::getByChauffeur($userId) : [];
$trajets_passager  = in_array('passager',  $roles) ? CovoiturageModel::getByPassager($userId)  : [];

$pageTitle = 'Mes trajets';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container">
    <?= renderFlash() ?>
    <h3 class="fw-bold text-eco mb-4"><i class="bi bi-clock-history me-2"></i>Mes trajets</h3>

    <!-- Onglets chauffeur / passager -->
    <ul class="nav nav-tabs mb-4">
        <?php if (in_array('chauffeur', $roles)): ?>
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabChauffeur">
                <i class="bi bi-car-front me-1"></i>En tant que chauffeur
            </button>
        </li>
        <?php endif; ?>
        <?php if (in_array('passager', $roles)): ?>
        <li class="nav-item">
            <button class="nav-link <?= !in_array('chauffeur', $roles) ? 'active' : '' ?>"
                    data-bs-toggle="tab" data-bs-target="#tabPassager">
                <i class="bi bi-person me-1"></i>En tant que passager
            </button>
        </li>
        <?php endif; ?>
    </ul>

    <div class="tab-content">

        <!-- ── Chauffeur ──────────────────────────────────────── -->
        <?php if (in_array('chauffeur', $roles)): ?>
        <div class="tab-pane fade show active" id="tabChauffeur">
            <?php if (empty($trajets_chauffeur)): ?>
                <div class="alert alert-info">Vous n'avez pas encore proposé de trajet.
                    <a href="<?= BASE_URL ?>/saisir-voyage.php">Proposer un trajet</a>
                </div>
            <?php else: ?>
                <?php foreach ($trajets_chauffeur as $t): ?>
                <div class="card dash-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= h($t['lieu_depart']) ?> → <?= h($t['lieu_arrivee']) ?></strong>
                            <small class="text-muted ms-2">
                                <?= formatDate($t['date_depart']) ?> · <?= formatTime($t['heure_depart']) ?>
                            </small>
                        </div>
                        <div>
                            <?php
                            $badges = [
                                'planifie'  => 'bg-info',
                                'en_cours'  => 'bg-warning text-dark',
                                'termine'   => 'bg-success',
                                'annule'    => 'bg-danger',
                            ];
                            $badge = $badges[$t['statut']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $badge ?>"><?= h($t['statut']) ?></span>
                        </div>
                    </div>
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <span class="me-3"><i class="bi bi-people me-1 text-muted"></i><?= $t['nb_place'] ?> place(s) restante(s)</span>
                            <span class="me-3"><i class="bi bi-coin me-1 text-muted"></i><?= $t['prix_personne'] ?> crédits/pers.</span>
                            <span><?= h($t['marque']) ?> <?= h($t['modele']) ?> — <?= h($t['energie']) ?></span>
                        </div>
                        <div class="d-flex gap-2">
                            <?php if ($t['statut'] === 'planifie'): ?>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"          value="demarrer">
                                    <input type="hidden" name="covoiturage_id"  value="<?= $t['covoiturage_id'] ?>">
                                    <button class="btn btn-success btn-sm">
                                        <i class="bi bi-play-fill me-1"></i>Démarrer
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('Annuler ce trajet ?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"         value="annuler_trajet">
                                    <input type="hidden" name="covoiturage_id" value="<?= $t['covoiturage_id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-x-circle me-1"></i>Annuler
                                    </button>
                                </form>
                            <?php elseif ($t['statut'] === 'en_cours'): ?>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"         value="terminer">
                                    <input type="hidden" name="covoiturage_id" value="<?= $t['covoiturage_id'] ?>">
                                    <button class="btn btn-primary btn-sm">
                                        <i class="bi bi-flag-fill me-1"></i>Arrivée à destination
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ── Passager ───────────────────────────────────────── -->
        <?php if (in_array('passager', $roles)): ?>
        <div class="tab-pane fade <?= !in_array('chauffeur', $roles) ? 'show active' : '' ?>"
             id="tabPassager">
            <?php if (empty($trajets_passager)): ?>
                <div class="alert alert-info">
                    Vous n'avez pas encore participé à un trajet.
                    <a href="<?= BASE_URL ?>/covoiturages.php">Trouver un trajet</a>
                </div>
            <?php else: ?>
                <?php foreach ($trajets_passager as $t): ?>
                <div class="card dash-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= h($t['lieu_depart']) ?> → <?= h($t['lieu_arrivee']) ?></strong>
                            <small class="text-muted ms-2">
                                <?= formatDate($t['date_depart']) ?> · <?= formatTime($t['heure_depart']) ?>
                            </small>
                        </div>
                        <span class="badge <?= $t['statut'] === 'termine' ? 'bg-success' : ($t['statut'] === 'annule' ? 'bg-danger' : 'bg-info') ?>">
                            <?= h($t['statut']) ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="me-3">
                                    <i class="bi bi-person-fill me-1 text-muted"></i>Chauffeur :
                                    <strong><?= h($t['chauffeur_pseudo']) ?></strong>
                                </span>
                                <span><i class="bi bi-coin me-1 text-muted"></i><?= $t['prix_personne'] ?> crédits</span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap justify-content-end">
                                <!-- Annuler si planifié -->
                                <?php if ($t['participe_statut'] === 'confirme' && $t['statut'] === 'planifie'): ?>
                                <form method="POST" onsubmit="return confirm('Annuler votre participation ?')">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"         value="annuler_participation">
                                    <input type="hidden" name="covoiturage_id" value="<?= $t['covoiturage_id'] ?>">
                                    <button class="btn btn-outline-danger btn-sm">Annuler</button>
                                </form>
                                <?php endif; ?>

                                <!-- Valider ou litige si trajet terminé -->
                                <?php if ($t['statut'] === 'termine' && $t['participe_statut'] === 'confirme'): ?>
                                <form method="POST" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action"         value="valider_trajet">
                                    <input type="hidden" name="covoiturage_id" value="<?= $t['covoiturage_id'] ?>">
                                    <button class="btn btn-success btn-sm">
                                        <i class="bi bi-check me-1"></i>Tout s'est bien passé
                                    </button>
                                </form>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalLitige<?= $t['covoiturage_id'] ?>">
                                    <i class="bi bi-exclamation-triangle me-1"></i>Signaler un problème
                                </button>
                                <?php endif; ?>

                                <!-- Soumettre un avis -->
                                <?php if ($t['statut'] === 'termine' && $t['participe_statut'] === 'valide' && !$t['avis_envoye']): ?>
                                <button class="btn btn-eco btn-sm" data-bs-toggle="modal"
                                        data-bs-target="#modalAvis<?= $t['covoiturage_id'] ?>">
                                    <i class="bi bi-star me-1"></i>Laisser un avis
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal litige -->
                <div class="modal fade" id="modalLitige<?= $t['covoiturage_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Signaler un problème</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action"         value="litige">
                                <input type="hidden" name="covoiturage_id" value="<?= $t['covoiturage_id'] ?>">
                                <div class="modal-body">
                                    <label class="form-label">Décrivez le problème</label>
                                    <textarea name="commentaire_litige" class="form-control" rows="4" required
                                              placeholder="Expliquez ce qui s'est passé..."></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-warning">Signaler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal avis -->
                <div class="modal fade" id="modalAvis<?= $t['covoiturage_id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Laisser un avis</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action"         value="soumettre_avis">
                                <input type="hidden" name="covoiturage_id" value="<?= $t['covoiturage_id'] ?>">
                                <div class="modal-body">
                                    <label class="form-label fw-semibold">Note (1-5)</label>
                                    <select name="note" class="form-select mb-3" required>
                                        <option value="">-- Note --</option>
                                        <?php for ($i=5;$i>=1;$i--): ?>
                                        <option value="<?= $i ?>"><?= str_repeat('★', $i) ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <label class="form-label fw-semibold">Commentaire</label>
                                    <textarea name="commentaire" class="form-control" rows="3"
                                              placeholder="Votre expérience..."></textarea>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-eco">Soumettre</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
