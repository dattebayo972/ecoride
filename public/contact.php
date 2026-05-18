<?php
require_once __DIR__ . '/../src/helpers/auth.php';
require_once __DIR__ . '/../src/helpers/functions.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $nom     = h(trim($_POST['nom'] ?? ''));
    $email   = trim($_POST['email'] ?? '');
    $message = h(trim($_POST['message'] ?? ''));
    if ($nom && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
        mail('contact@ecoride.fr', '[EcoRide] Contact — ' . $nom,
            "De : {$nom} <{$email}>\n\n{$message}");
        $sent = true;
    }
}

$pageTitle = 'Contact';
include __DIR__ . '/../src/components/header.php';
include __DIR__ . '/../src/components/navbar.php';
?>
<main class="py-5">
<div class="container" style="max-width:600px">
    <h3 class="fw-bold text-eco mb-4"><i class="bi bi-envelope me-2"></i>Nous contacter</h3>

    <?php if ($sent): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle me-2"></i>Votre message a bien été envoyé. Nous vous répondrons rapidement.
        </div>
    <?php else: ?>
    <div class="card shadow border-0 rounded-3">
        <div class="card-body p-4">
            <p class="text-muted mb-4">
                <i class="bi bi-envelope me-2 text-eco"></i>contact@ecoride.fr<br>
                Vous pouvez également nous écrire via ce formulaire.
            </p>
            <form method="POST">
                <?= csrfField() ?>
                <div class="mb-3">
                    <label class="form-label">Nom complet</label>
                    <input type="text" name="nom" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-4">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="5" required
                              placeholder="Votre message..."></textarea>
                </div>
                <button type="submit" class="btn btn-eco w-100">
                    <i class="bi bi-send me-2"></i>Envoyer
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
</main>
<?php include __DIR__ . '/../src/components/footer.php'; ?>
