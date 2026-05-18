<?php
require_once __DIR__ . '/../config/db_mysql.php';

class VoitureModel {

    public static function getByUser(int $userId): array {
        $stmt = getPDO()->prepare(
            "SELECT v.*, m.libelle as marque
             FROM voiture v
             JOIN marque m ON m.marque_id = v.marque_id
             WHERE v.utilisateur_id = ?
             ORDER BY v.voiture_id DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findById(int $id): ?array {
        $stmt = getPDO()->prepare(
            "SELECT v.*, m.libelle as marque
             FROM voiture v
             JOIN marque m ON m.marque_id = v.marque_id
             WHERE v.voiture_id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            "INSERT INTO voiture
             (modele, immatriculation, energie, couleur, date_premiere_immatriculation, nb_place, utilisateur_id, marque_id)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $data['modele'], $data['immatriculation'], $data['energie'],
            $data['couleur'], $data['date_premiere_immatriculation'],
            $data['nb_place'], $data['utilisateur_id'], $data['marque_id'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function delete(int $id, int $userId): void {
        getPDO()->prepare('DELETE FROM voiture WHERE voiture_id = ? AND utilisateur_id = ?')
            ->execute([$id, $userId]);
    }
}
