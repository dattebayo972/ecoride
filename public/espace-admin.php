<?php
// US13 — Espace Administrateur
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/UserModel.php';
require_once __DIR__ . '/../src/models/CovoiturageModel.php';

requireLogin();
requireRole('administrateur');
requireActive();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    // Créer un employé
    if ($action === 'create_employee') {
        $data = [
            'nom'      => trim($_POST['nom'] ?? ''),
            'prenom'   => trim($_POST['prenom'] ?? ''),
            'email'    => trim($_POST['email'] ?? ''),
            'pseudo'   => trim($_POST['pseudo'] ?? ''),
            'password' => $_POST['password'] ?? '',
        ];
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email invalide.';
        }
        if (!isStrongPassword($data['password'])) {
            $errors[] = 'Mot de passe non sécurisé.';
        }
        if (UserModel::findByEmail($data['email'])) {
            $errors[] = 'Email déjà utilisé.';
        }
        if (!$errors) {
            UserModel::createEmployee($data);
            setFlash('success', 'Compte employé créé.');
            redirect(BASE_URL . '/espace-admin.php');
        }
    }

    // Suspendre/activer un compte
    if ($action === 'suspend') {
        UserModel::suspend((int)$_POST['user_id']);
        setFlash('warning', 'Compte suspendu.');
        redirect(BASE_URL . '/espace-admin.php');
    }
    if ($action === 'activate') {
        UserModel::activate((int)$_POST['user_id']);
        setFlash('success', 'Compte réactivé.');
        redirect(BASE_URL . '/espace-admin.php');
    }
}

$users            = UserModel::getAllUsers();
$totalCredits     = CovoiturageModel::totalCreditsplateforme();
$statsJour        = CovoiturageModel::countPerDay();
$creditsJour      = CovoiturageModel::creditsPerDay();

// Préparer données JSON pour Chart.js
$labels       = array_reverse(array_column($statsJour, 'jour'));
$dataCov      = array_reverse(array_column($statsJour, 'total'));
$labelsC      = array_reverse(array_column($creditsJour, 'jour'));
$dataCredits  = array_reverse(array_column($creditsJour, 'credits_plateforme'));

$pageTitle = 'Administration';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container-fluid px-4">
    <?= renderFlash() ?>
    <h3 class="fw-bold text-eco mb-4">
        <i class="bi bi-shield-lock me-2"></i>Espace Administrateur
    </h3>

    <!-- ── Statistiques ─────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card dash-card text-center border-start border-4 border-eco">
                <div class="card-body">
                    <div class="stat-badge"><?= count($users) ?></div>
                    <div class="text-muted">Utilisateurs</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dash-card text-center border-start border-4 border-warning">
                <div class="card-body">
                    <div class="stat-badge text-warning"><?= $totalCredits ?></div>
                    <div class="text-muted">Crédits gagnés (total)</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dash-card text-center border-start border-4 border-info">
                <div class="card-body">
                    <div class="stat-badge text-info">
                        <?= array_sum($dataCov) ?>
                    </div>
                    <div class="text-muted">Trajets terminés</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card dash-card text-center border-start border-4 border-danger">
                <div class="card-body">
                    <div class="stat-badge text-danger">
                        <?= count(array_filter($users, fn($u) => $u['statut'] === 'suspendu')) ?>
                    </div>
                    <div class="text-muted">Comptes suspendus</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Graphiques ───────────────────────────────────────── -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card dash-card">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-bar-chart me-2"></i>Covoiturages terminés par jour (30 derniers jours)
                </div>
                <div class="card-body">
                    <canvas id="chartCov" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card dash-card">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-graph-up me-2"></i>Crédits gagnés par la plateforme par jour
                </div>
                <div class="card-body">
                    <canvas id="chartCredits" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── Créer un employé ──────────────────────────────── -->
        <div class="col-lg-4">
            <div class="card dash-card">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-person-plus me-2"></i>Créer un compte employé
                </div>
                <div class="card-body">
                    <?php if ($errors): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0"><?php foreach ($errors as $e) echo "<li>".h($e)."</li>"; ?></ul>
                        </div>
                    <?php endif; ?>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="create_employee">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label small">Nom</label>
                                <input type="text" name="nom" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small">Prénom</label>
                                <input type="text" name="prenom" class="form-control form-control-sm">
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Email</label>
                                <input type="email" name="email" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Pseudo</label>
                                <input type="text" name="pseudo" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small">Mot de passe</label>
                                <input type="password" name="password" id="password"
                                       class="form-control form-control-sm" required>
                                <div id="passwordHint" class="form-text text-muted small">
                                    8 car., majuscule, chiffre, spécial
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-eco btn-sm mt-3 w-100">
                            <i class="bi bi-plus me-1"></i>Créer
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ── Liste des utilisateurs ───────────────────────── -->
        <div class="col-lg-8">
            <div class="card dash-card">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-people me-2"></i>Gestion des comptes
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                        <table class="table table-eco table-hover table-sm mb-0">
                            <thead class="sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Pseudo</th>
                                    <th>Email</th>
                                    <th>Rôles</th>
                                    <th>Crédits</th>
                                    <th>Statut</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['utilisateur_id'] ?></td>
                                    <td><?= h($u['pseudo']) ?></td>
                                    <td class="text-muted small"><?= h($u['email']) ?></td>
                                    <td>
                                        <small><?= h($u['roles'] ?? '—') ?></small>
                                    </td>
                                    <td><?= $u['credits'] ?></td>
                                    <td>
                                        <span class="badge <?= $u['statut'] === 'actif' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= h($u['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ((int)$u['utilisateur_id'] !== currentUserId()): ?>
                                        <?php if ($u['statut'] === 'actif'): ?>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('Suspendre ce compte ?')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action"  value="suspend">
                                            <input type="hidden" name="user_id" value="<?= $u['utilisateur_id'] ?>">
                                            <button class="btn btn-danger btn-sm">
                                                <i class="bi bi-slash-circle"></i>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action"  value="activate">
                                            <input type="hidden" name="user_id" value="<?= $u['utilisateur_id'] ?>">
                                            <button class="btn btn-success btn-sm">
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="text-muted small">Vous</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const green = '#2d6a4f', light = '#74c69d';

// Graphique covoiturages
new Chart(document.getElementById('chartCov'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: 'Covoiturages',
            data: <?= json_encode($dataCov) ?>,
            backgroundColor: light,
            borderColor: green,
            borderWidth: 1,
            borderRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Graphique crédits
new Chart(document.getElementById('chartCredits'), {
    type: 'line',
    data: {
        labels: <?= json_encode($labelsC) ?>,
        datasets: [{
            label: 'Crédits gagnés',
            data: <?= json_encode($dataCredits) ?>,
            borderColor: green,
            backgroundColor: 'rgba(45,106,79,.15)',
            fill: true,
            tension: 0.4,
            pointRadius: 4,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include __DIR__ . '/../src/components/footer.php'; ?>
