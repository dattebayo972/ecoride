<?php

// ── Sécurité ──────────────────────────────────────────────────────────────────

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('Token CSRF invalide.');
    }
}

// ── Validation mot de passe ──────────────────────────────────────────────────

function isStrongPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[\W_]/', $password);
}

// ── Redirections ─────────────────────────────────────────────────────────────

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function redirectBack(string $fallback = BASE_URL . '/index.php'): void {
    redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
}

// ── Flash messages ────────────────────────────────────────────────────────────

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $type = $flash['type'] === 'success' ? 'success' : 'danger';
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
        . h($flash['message'])
        . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
}

// ── Helpers affichage ────────────────────────────────────────────────────────

function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function formatTime(string $time): string {
    return substr($time, 0, 5);
}

function ecoLabel(string $energie): string {
    if ($energie === 'electrique') {
        return '<span class="badge bg-success"><i class="bi bi-lightning-charge-fill"></i> Écologique</span>';
    }
    return '<span class="badge bg-secondary">Non écologique</span>';
}

function starRating(float $note): string {
    $full  = (int) round($note);
    $html  = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $full
            ? '<i class="bi bi-star-fill text-warning"></i>'
            : '<i class="bi bi-star text-warning"></i>';
    }
    return $html;
}

function avatarUrl(?string $photo): string {
    if ($photo && file_exists(__DIR__ . '/../../public/assets/img/uploads/' . $photo)) {
        return BASE_URL . '/assets/img/uploads/' . $photo;
    }
    return BASE_URL . '/assets/img/avatar-default.png';
}
