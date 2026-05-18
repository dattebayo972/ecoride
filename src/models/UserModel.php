<?php
require_once __DIR__ . '/../config/db_mysql.php';

class UserModel {

    public static function findByEmail(string $email): ?array {
        $stmt = getPDO()->prepare('SELECT * FROM utilisateur WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array {
        $stmt = getPDO()->prepare('SELECT * FROM utilisateur WHERE utilisateur_id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByPseudo(string $pseudo): ?array {
        $stmt = getPDO()->prepare('SELECT * FROM utilisateur WHERE pseudo = ? LIMIT 1');
        $stmt->execute([$pseudo]);
        return $stmt->fetch() ?: null;
    }

    public static function getRoles(int $userId): array {
        $stmt = getPDO()->prepare(
            'SELECT r.libelle FROM role r
             JOIN utilisateur_role ur ON ur.role_id = r.role_id
             WHERE ur.utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'libelle');
    }

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut)
             VALUES (?, ?, ?, ?, ?, 20, "actif")'
        );
        $stmt->execute([
            $data['nom']    ?? '',
            $data['prenom'] ?? '',
            $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['pseudo'],
        ]);
        $id = (int) $pdo->lastInsertId();
        // Attribuer le rôle passager par défaut (role_id=4)
        $pdo->prepare('INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (?, 4)')
            ->execute([$id]);
        return $id;
    }

    public static function updateProfile(int $id, array $data): void {
        getPDO()->prepare(
            'UPDATE utilisateur SET nom=?, prenom=?, telephone=?, adresse=?, date_naissance=?
             WHERE utilisateur_id=?'
        )->execute([
            $data['nom'], $data['prenom'], $data['telephone'],
            $data['adresse'], $data['date_naissance'], $id
        ]);
    }

    public static function updateCredits(int $id, int $delta): void {
        getPDO()->prepare(
            'UPDATE utilisateur SET credits = credits + ? WHERE utilisateur_id = ?'
        )->execute([$delta, $id]);
    }

    public static function setCredits(int $id, int $amount): void {
        getPDO()->prepare('UPDATE utilisateur SET credits = ? WHERE utilisateur_id = ?')
            ->execute([$amount, $id]);
    }

    public static function updatePhoto(int $id, string $filename): void {
        getPDO()->prepare('UPDATE utilisateur SET photo = ? WHERE utilisateur_id = ?')
            ->execute([$filename, $id]);
    }

    public static function addRole(int $userId, int $roleId): void {
        getPDO()->prepare(
            'INSERT IGNORE INTO utilisateur_role (utilisateur_id, role_id) VALUES (?, ?)'
        )->execute([$userId, $roleId]);
    }

    public static function removeRole(int $userId, int $roleId): void {
        getPDO()->prepare(
            'DELETE FROM utilisateur_role WHERE utilisateur_id = ? AND role_id = ?'
        )->execute([$userId, $roleId]);
    }

    public static function getRoleId(string $libelle): ?int {
        $stmt = getPDO()->prepare('SELECT role_id FROM role WHERE libelle = ? LIMIT 1');
        $stmt->execute([$libelle]);
        $row = $stmt->fetch();
        return $row ? (int) $row['role_id'] : null;
    }

    public static function suspend(int $id): void {
        getPDO()->prepare("UPDATE utilisateur SET statut = 'suspendu' WHERE utilisateur_id = ?")
            ->execute([$id]);
    }

    public static function activate(int $id): void {
        getPDO()->prepare("UPDATE utilisateur SET statut = 'actif' WHERE utilisateur_id = ?")
            ->execute([$id]);
    }

    public static function getAvgRating(int $chauffeurId): float {
        $stmt = getPDO()->prepare(
            "SELECT AVG(note) as avg FROM avis WHERE chauffeur_id = ? AND statut = 'valide'"
        );
        $stmt->execute([$chauffeurId]);
        return (float) ($stmt->fetch()['avg'] ?? 0);
    }

    public static function getAllUsers(): array {
        return getPDO()->query(
            "SELECT u.*, GROUP_CONCAT(r.libelle ORDER BY r.libelle SEPARATOR ', ') as roles
             FROM utilisateur u
             LEFT JOIN utilisateur_role ur ON ur.utilisateur_id = u.utilisateur_id
             LEFT JOIN role r ON r.role_id = ur.role_id
             GROUP BY u.utilisateur_id
             ORDER BY u.created_at DESC"
        )->fetchAll();
    }

    public static function createEmployee(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            'INSERT INTO utilisateur (nom, prenom, email, password, pseudo, credits, statut)
             VALUES (?, ?, ?, ?, ?, 0, "actif")'
        );
        $stmt->execute([
            $data['nom'], $data['prenom'], $data['email'],
            password_hash($data['password'], PASSWORD_BCRYPT),
            $data['pseudo'],
        ]);
        $id = (int) $pdo->lastInsertId();
        $roleId = self::getRoleId('employe');
        if ($roleId) {
            $pdo->prepare('INSERT INTO utilisateur_role (utilisateur_id, role_id) VALUES (?, ?)')
                ->execute([$id, $roleId]);
        }
        return $id;
    }

    // Préférences conducteur
    public static function getOrCreateConfig(int $userId): int {
        $stmt = getPDO()->prepare('SELECT id_configuration FROM configuration WHERE utilisateur_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row) return (int) $row['id_configuration'];
        $pdo = getPDO();
        $pdo->prepare('INSERT INTO configuration (utilisateur_id) VALUES (?)')->execute([$userId]);
        return (int) $pdo->lastInsertId();
    }

    public static function setPreferences(int $userId, array $parametreIds, array $libresTextes): void {
        $pdo    = getPDO();
        $confId = self::getOrCreateConfig($userId);
        $pdo->prepare('DELETE FROM dispose WHERE id_configuration = ?')->execute([$confId]);
        foreach ($parametreIds as $pid) {
            $pdo->prepare('INSERT IGNORE INTO dispose (id_configuration, parametre_id) VALUES (?,?)')
                ->execute([$confId, $pid]);
        }
        $pdo->prepare('DELETE FROM preference_libre WHERE utilisateur_id = ?')->execute([$userId]);
        foreach ($libresTextes as $texte) {
            if (trim($texte)) {
                $pdo->prepare('INSERT INTO preference_libre (utilisateur_id, texte) VALUES (?,?)')
                    ->execute([$userId, trim($texte)]);
            }
        }
    }

    public static function getPreferences(int $userId): array {
        $stmt = getPDO()->prepare(
            'SELECT p.propriete, p.valeur FROM parametre p
             JOIN dispose d ON d.parametre_id = p.parametre_id
             JOIN configuration c ON c.id_configuration = d.id_configuration
             WHERE c.utilisateur_id = ?'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function getPreferencesLibres(int $userId): array {
        $stmt = getPDO()->prepare('SELECT texte FROM preference_libre WHERE utilisateur_id = ?');
        $stmt->execute([$userId]);
        return array_column($stmt->fetchAll(), 'texte');
    }
}
