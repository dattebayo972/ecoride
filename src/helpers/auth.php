<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détecte automatiquement le chemin de base (fonctionne quel que soit le dossier htdocs)
if (!defined('BASE_URL')) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#^(.*?/public)#', $script, $m)) {
        define('BASE_URL', $m[1]);
    } else {
        define('BASE_URL', '/ecoride/public');
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentUserPseudo(): string {
    return $_SESSION['pseudo'] ?? '';
}

function currentUserRoles(): array {
    return $_SESSION['roles'] ?? [];
}

function hasRole(string $role): bool {
    return in_array($role, currentUserRoles(), true);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/connexion.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (!hasRole($role)) {
        http_response_code(403);
        die('Accès refusé.');
    }
}

function requireActive(): void {
    if (($_SESSION['statut'] ?? '') === 'suspendu') {
        session_destroy();
        header('Location: ' . BASE_URL . '/connexion.php?error=compte_suspendu');
        exit;
    }
}

function loginUser(array $user, array $roles): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['utilisateur_id'];
    $_SESSION['pseudo']  = $user['pseudo'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['photo']   = $user['photo'];
    $_SESSION['credits'] = $user['credits'];
    $_SESSION['statut']  = $user['statut'];
    $_SESSION['roles']   = $roles;
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function refreshCredits(int $credits): void {
    $_SESSION['credits'] = $credits;
}
