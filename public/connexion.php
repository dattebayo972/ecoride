<?php
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/UserModel.php';

if (isLoggedIn()) redirect(BASE_URL . '/espace-utilisateur.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $user = UserModel::findByEmail($email);
    if ($user && password_verify($password, $user['password'])) {
        if ($user['statut'] === 'suspendu') {
            $error = 'Votre compte est suspendu. Contactez l\'administrateur.';
        } else {
            $roles = UserModel::getRoles((int) $user['utilisateur_id']);
            loginUser($user, $roles);
            $redirect = $_GET['redirect'] ?? BASE_URL . '/espace-utilisateur.php';
            redirect($redirect);
        }
    } else {
        $error = 'Email ou mot de passe incorrect.';
    }
}

$pageTitle = 'Connexion';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-5">
<div class="container" style="max-width:450px">
    <div class="card shadow border-0 rounded-3">
        <div class="card-header bg-eco text-white text-center py-3">
            <h4 class="mb-0 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Connexion</h4>
        </div>
        <div class="card-body p-4">
            <?= renderFlash() ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= h($error) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['error']) && $_GET['error'] === 'compte_suspendu'): ?>
                <div class="alert alert-warning">Votre compte a été suspendu.</div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                               value="<?= h($_POST['email'] ?? '') ?>"
                               placeholder="vous@exemple.fr" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control"
                               placeholder="Votre mot de passe" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-eco w-100 fw-semibold py-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>
            </form>
        </div>
        <div class="card-footer text-center py-3">
            Pas encore de compte ?
            <a href="<?= BASE_URL ?>/inscription.php" class="text-eco fw-semibold">S'inscrire gratuitement</a>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
