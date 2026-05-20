<?php
// US7 — Création de compte
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';
require_once __DIR__ . '/../src/models/UserModel.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/espace-utilisateur.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $pseudo   = trim($_POST['pseudo'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!$pseudo || strlen($pseudo) < 3) {
        $errors[] = 'Le pseudo doit contenir au moins 3 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }
    if (!isStrongPassword($password)) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }
    if (!$errors && UserModel::findByEmail($email)) {
        $errors[] = 'Cette adresse email est déjà utilisée.';
    }
    if (!$errors && UserModel::findByPseudo($pseudo)) {
        $errors[] = 'Ce pseudo est déjà pris.';
    }

    if (!$errors) {
        UserModel::create(['pseudo' => $pseudo, 'email' => $email, 'password' => $password]);
        $newUser = UserModel::findByEmail($email);
        $roles   = UserModel::getRoles((int) $newUser['utilisateur_id']);
        loginUser($newUser, $roles);
        setFlash('success', 'Bienvenue ! Votre compte a été créé avec 20 crédits offerts.');
        redirect(BASE_URL . '/espace-utilisateur.php');
    }
}

$pageTitle = 'Inscription';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-5">
<div class="container" style="max-width:500px">
    <div class="card shadow border-0 rounded-3">
        <div class="card-header bg-eco text-white text-center py-3">
            <h4 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2"></i>Créer un compte</h4>
            <small class="opacity-75">20 crédits offerts à l'inscription</small>
        </div>
        <div class="card-body p-4">

            <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?>
                            <li><?= h($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label class="form-label">Pseudo <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <input type="text" name="pseudo" class="form-control"
                               value="<?= h($_POST['pseudo'] ?? '') ?>"
                               placeholder="votre_pseudo" required minlength="3">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control"
                               value="<?= h($_POST['email'] ?? '') ?>"
                               placeholder="vous@exemple.fr" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="password"
                               class="form-control" placeholder="Min. 8 car." required>
                    </div>
                    <div id="passwordHint" class="form-text text-muted">
                        8 car. min., une majuscule, un chiffre, un caractère spécial
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Répétez votre mot de passe" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-eco w-100 fw-semibold py-2">
                    <i class="bi bi-check-circle me-2"></i>Créer mon compte
                </button>
            </form>
        </div>
        <div class="card-footer text-center py-3">
            Déjà un compte ?
            <a href="<?= BASE_URL ?>/connexion.php" class="text-eco fw-semibold">Se connecter</a>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
