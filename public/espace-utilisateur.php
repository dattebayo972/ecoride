<?php
// US8 — Espace Utilisateur (profil, rôles, véhicules, préférences)
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/UserModel.php';
require_once __DIR__ . '/../src/models/VoitureModel.php';
require_once __DIR__ . '/../src/models/MarqueModel.php';

requireLogin();
requireActive();

$userId = currentUserId();
$user   = UserModel::findById($userId);
$roles  = UserModel::getRoles($userId);
$marques = MarqueModel::getAll();
$voitures = VoitureModel::getByUser($userId);
$prefs    = UserModel::getPreferences($userId);
$prefsLibre = UserModel::getPreferencesLibres($userId);
$parametres = getPDO()->query('SELECT * FROM parametre')->fetchAll();

$flash = '';

// ── Mise à jour du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profil') {
        UserModel::updateProfile($userId, [
            'nom'            => trim($_POST['nom'] ?? ''),
            'prenom'         => trim($_POST['prenom'] ?? ''),
            'telephone'      => trim($_POST['telephone'] ?? ''),
            'adresse'        => trim($_POST['adresse'] ?? ''),
            'date_naissance' => $_POST['date_naissance'] ?? null,
        ]);
        // Upload photo
        if (!empty($_FILES['photo']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $dest = __DIR__ . '/assets/img/uploads/';
                if (!is_dir($dest)) mkdir($dest, 0775, true);
                $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $dest . $filename);
                UserModel::updatePhoto($userId, $filename);
                $_SESSION['photo'] = $filename;
            }
        }
        $user = UserModel::findById($userId);
        setFlash('success', 'Profil mis à jour.');
        redirect(BASE_URL . '/espace-utilisateur.php');
    }

    if ($action === 'set_role') {
        $wantChauffeur = !empty($_POST['role_chauffeur']);
        $wantPassager  = !empty($_POST['role_passager']);
        $chauffeurId   = UserModel::getRoleId('chauffeur');
        $passagerId    = UserModel::getRoleId('passager');
        $wantChauffeur
            ? UserModel::addRole($userId, $chauffeurId)
            : UserModel::removeRole($userId, $chauffeurId);
        $wantPassager
            ? UserModel::addRole($userId, $passagerId)
            : UserModel::removeRole($userId, $passagerId);
        $roles = UserModel::getRoles($userId);
        $_SESSION['roles'] = $roles;
        setFlash('success', 'Rôle mis à jour.');
        redirect(BASE_URL . '/espace-utilisateur.php');
    }

    if ($action === 'add_voiture') {
        VoitureModel::create([
            'modele'                      => trim($_POST['modele']),
            'immatriculation'             => trim(strtoupper($_POST['immatriculation'])),
            'energie'                     => $_POST['energie'],
            'couleur'                     => trim($_POST['couleur']),
            'date_premiere_immatriculation' => $_POST['date_premiere_immatriculation'],
            'nb_place'                    => (int)$_POST['nb_place'],
            'utilisateur_id'              => $userId,
            'marque_id'                   => (int)$_POST['marque_id'],
        ]);
        setFlash('success', 'Véhicule ajouté.');
        redirect(BASE_URL . '/espace-utilisateur.php');
    }

    if ($action === 'delete_voiture') {
        VoitureModel::delete((int)$_POST['voiture_id'], $userId);
        setFlash('success', 'Véhicule supprimé.');
        redirect(BASE_URL . '/espace-utilisateur.php');
    }

    if ($action === 'set_prefs') {
        $parametreIds = array_map('intval', $_POST['parametres'] ?? []);
        $libres = explode("\n", $_POST['preferences_libres'] ?? '');
        UserModel::setPreferences($userId, $parametreIds, $libres);
        setFlash('success', 'Préférences enregistrées.');
        redirect(BASE_URL . '/espace-utilisateur.php');
    }
}

$pageTitle = 'Mon espace';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-4">
<div class="container">
    <?= renderFlash() ?>
    <h3 class="fw-bold text-eco mb-4"><i class="bi bi-person-circle me-2"></i>Mon espace</h3>

    <div class="row g-4">

        <!-- ── Profil ─────────────────────────────────────────── -->
        <div class="col-lg-4">
            <div class="card dash-card mb-4">
                <div class="card-header bg-eco-light fw-semibold text-eco">
                    <i class="bi bi-person me-2"></i>Mon profil
                </div>
                <div class="card-body text-center">
                    <img src="<?= h(avatarUrl($user['photo'])) ?>"
                         alt="Avatar" class="avatar-lg mb-3" id="photoPreview">
                    <h5 class="fw-bold"><?= h($user['pseudo']) ?></h5>
                    <p class="text-muted mb-1"><?= h($user['email']) ?></p>
                    <span class="badge bg-warning text-dark fs-6">
                        <i class="bi bi-coin me-1"></i><?= $user['credits'] ?> crédits
                    </span>
                    <div class="mt-2">
                        <?php foreach ($roles as $r): ?>
                            <span class="badge bg-eco me-1"><?= h(ucfirst($r)) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Crédits info -->
            <div class="card dash-card border-start border-4 border-eco">
                <div class="card-body text-center">
                    <div class="stat-badge"><?= $user['credits'] ?></div>
                    <div class="text-muted">Crédits disponibles</div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <!-- Tabs -->
            <ul class="nav nav-tabs mb-3" id="dashTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabProfil">
                        Profil
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRole">
                        Rôle
                    </button>
                </li>
                <?php if (in_array('chauffeur', $roles)): ?>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabVehicule">
                        Véhicules
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPrefs">
                        Préférences
                    </button>
                </li>
                <?php endif; ?>
            </ul>

            <div class="tab-content">

                <!-- Tab Profil -->
                <div class="tab-pane fade show active" id="tabProfil">
                    <div class="card dash-card">
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="update_profil">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nom</label>
                                        <input type="text" name="nom" class="form-control"
                                               value="<?= h($user['nom'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Prénom</label>
                                        <input type="text" name="prenom" class="form-control"
                                               value="<?= h($user['prenom'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Téléphone</label>
                                        <input type="tel" name="telephone" class="form-control"
                                               value="<?= h($user['telephone'] ?? '') ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Date de naissance</label>
                                        <input type="date" name="date_naissance" class="form-control"
                                               value="<?= h($user['date_naissance'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Adresse</label>
                                        <input type="text" name="adresse" class="form-control"
                                               value="<?= h($user['adresse'] ?? '') ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Photo de profil</label>
                                        <input type="file" name="photo" id="photoInput"
                                               class="form-control" accept="image/*">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-eco mt-3">
                                    <i class="bi bi-save me-1"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Rôle -->
                <div class="tab-pane fade" id="tabRole">
                    <div class="card dash-card">
                        <div class="card-body">
                            <p class="text-muted">Sélectionnez votre ou vos rôles sur la plateforme.</p>
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="set_role">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="role_chauffeur"
                                           id="roleChauffeur" value="1"
                                           <?= in_array('chauffeur', $roles) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="roleChauffeur">
                                        <i class="bi bi-car-front me-1"></i>
                                        <strong>Chauffeur</strong> — Je propose des trajets
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="role_passager"
                                           id="rolePassager" value="1"
                                           <?= in_array('passager', $roles) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="rolePassager">
                                        <i class="bi bi-person me-1"></i>
                                        <strong>Passager</strong> — Je recherche des trajets
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-eco">
                                    <i class="bi bi-save me-1"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Véhicules -->
                <?php if (in_array('chauffeur', $roles)): ?>
                <div class="tab-pane fade" id="tabVehicule">
                    <div class="card dash-card mb-3">
                        <div class="card-body">
                            <?php if ($voitures): ?>
                                <?php foreach ($voitures as $v): ?>
                                <div class="d-flex justify-content-between align-items-center border-bottom pb-2 mb-2">
                                    <div>
                                        <strong><?= h($v['marque']) ?> <?= h($v['modele']) ?></strong>
                                        <span class="text-muted ms-2"><?= h($v['immatriculation']) ?></span>
                                        <span class="badge ms-2 <?= $v['energie'] === 'electrique' ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= h($v['energie']) ?>
                                        </span>
                                    </div>
                                    <form method="POST" class="d-inline">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action"     value="delete_voiture">
                                        <input type="hidden" name="voiture_id" value="<?= $v['voiture_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Supprimer ce véhicule ?')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">Aucun véhicule enregistré.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Ajouter un véhicule -->
                    <div class="card dash-card">
                        <div class="card-header bg-eco-light text-eco fw-semibold">
                            <i class="bi bi-plus-circle me-1"></i>Ajouter un véhicule
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="add_voiture">
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
                                        <input type="text" name="immatriculation" class="form-control"
                                               placeholder="AB-123-CD" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Énergie</label>
                                        <select name="energie" class="form-select" required>
                                            <option value="electrique">Électrique</option>
                                            <option value="essence">Essence</option>
                                            <option value="diesel">Diesel</option>
                                            <option value="hybride">Hybride</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Couleur</label>
                                        <input type="text" name="couleur" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">1ère immatriculation</label>
                                        <input type="date" name="date_premiere_immatriculation" class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nombre de places</label>
                                        <input type="number" name="nb_place" class="form-control"
                                               value="4" min="1" max="9" required>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-eco mt-3">
                                    <i class="bi bi-plus me-1"></i>Ajouter
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Tab Préférences -->
                <div class="tab-pane fade" id="tabPrefs">
                    <div class="card dash-card">
                        <div class="card-body">
                            <form method="POST">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="set_prefs">

                                <h6 class="fw-semibold text-eco">Préférences prédéfinies</h6>
                                <?php
                                $grouped = [];
                                foreach ($parametres as $p) {
                                    $grouped[$p['propriete']][] = $p;
                                }
                                $checkedIds = array_column($prefs, null);
                                $checkedIds = array_map(fn($p) => $p['propriete'].'_'.$p['valeur'], $prefs);
                                ?>
                                <?php foreach ($grouped as $propriete => $opts): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <?= h(ucfirst($propriete)) ?>
                                    </label>
                                    <?php foreach ($opts as $opt): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="parametres[]"
                                               value="<?= $opt['parametre_id'] ?>"
                                               id="p<?= $opt['parametre_id'] ?>"
                                               <?php
                                               foreach ($prefs as $pr) {
                                                   if ($pr['propriete'] === $opt['propriete'] && $pr['valeur'] === $opt['valeur']) {
                                                       echo 'checked'; break;
                                                   }
                                               }
                                               ?>>
                                        <label class="form-check-label" for="p<?= $opt['parametre_id'] ?>">
                                            <?= h($opt['valeur']) ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endforeach; ?>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Préférences personnalisées</label>
                                    <textarea name="preferences_libres" class="form-control" rows="3"
                                              placeholder="Une préférence par ligne (ex: Musique classique acceptée)"
                                    ><?= h(implode("\n", $prefsLibre)) ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-eco">
                                    <i class="bi bi-save me-1"></i>Enregistrer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /tab-content -->
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
