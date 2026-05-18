<?php
require_once __DIR__ . '/../helpers/auth.php';
$isLogged = isLoggedIn();
$roles    = $isLogged ? currentUserRoles() : [];
$pseudo   = $isLogged ? currentUserPseudo() : '';
$credits  = $_SESSION['credits'] ?? 0;
$current  = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-eco shadow-sm sticky-top">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/index.php">
            <i class="bi bi-tree-fill me-2"></i>EcoRide
        </a>

        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-house-fill me-1"></i>Accueil
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'covoiturages.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/covoiturages.php">
                        <i class="bi bi-car-front-fill me-1"></i>Covoiturages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current === 'contact.php' ? 'active' : '' ?>"
                       href="<?= BASE_URL ?>/contact.php">
                        <i class="bi bi-envelope-fill me-1"></i>Contact
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ms-auto align-items-center">
                <?php if ($isLogged): ?>
                    <!-- Crédits -->
                    <li class="nav-item me-2">
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                            <i class="bi bi-coin me-1"></i><?= $credits ?> crédit<?= $credits > 1 ? 's' : '' ?>
                        </span>
                    </li>

                    <!-- Espace utilisateur -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($pseudo) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/espace-utilisateur.php">
                                    <i class="bi bi-person me-2"></i>Mon espace
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/historique.php">
                                    <i class="bi bi-clock-history me-2"></i>Mes trajets
                                </a>
                            </li>
                            <?php if (in_array('chauffeur', $roles)): ?>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/saisir-voyage.php">
                                    <i class="bi bi-plus-circle me-2"></i>Proposer un trajet
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (in_array('employe', $roles)): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-primary" href="<?= BASE_URL ?>/espace-employe.php">
                                    <i class="bi bi-briefcase me-2"></i>Espace employé
                                </a>
                            </li>
                            <?php endif; ?>
                            <?php if (in_array('administrateur', $roles)): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/espace-admin.php">
                                    <i class="bi bi-shield-lock me-2"></i>Administration
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/deconnexion.php">
                                    <i class="bi bi-box-arrow-right me-2"></i>Se déconnecter
                                </a>
                            </li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === 'connexion.php' ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/connexion.php">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Connexion
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-eco-outline ms-2"
                           href="<?= BASE_URL ?>/inscription.php">
                            <i class="bi bi-person-plus me-1"></i>S'inscrire
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
