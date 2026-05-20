<?php
require_once __DIR__ . '/../config/db_mysql.php';
require_once __DIR__ . '/../config/db_mongo.php';

class AvisModel {

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            "INSERT INTO avis (commentaire, note, statut, chauffeur_id, passager_id, covoiturage_id)
             VALUES (?, ?, 'en_attente', ?, ?, ?)"
        );
        $stmt->execute([
            $data['commentaire'], $data['note'],
            $data['chauffeur_id'], $data['passager_id'], $data['covoiturage_id'],
        ]);
        $id = (int) $pdo->lastInsertId();

        // Dupliquer dans MongoDB si disponible
        $col = getMongoCollection('avis');
        if ($col) {
            $col->insertOne([
                'mysql_avis_id'    => $id,
                'covoiturage_id'   => $data['covoiturage_id'],
                'chauffeur_id'     => $data['chauffeur_id'],
                'passager_id'      => $data['passager_id'],
                'commentaire'      => $data['commentaire'],
                'note'             => (int) $data['note'],
                'statut'           => 'en_attente',
                'created_at'       => new \MongoDB\BSON\UTCDateTime(),
            ]);
        }
        return $id;
    }

    public static function getByChauffeur(int $chauffeurId, bool $validOnly = true): array {
        $sql = 'SELECT a.*, u.pseudo as passager_pseudo FROM avis a
                JOIN utilisateur u ON u.utilisateur_id = a.passager_id
                WHERE a.chauffeur_id = ?';
        if ($validOnly) $sql .= " AND a.statut = 'valide'";
        $sql .= ' ORDER BY a.created_at DESC';
        $stmt = getPDO()->prepare($sql);
        $stmt->execute([$chauffeurId]);
        return $stmt->fetchAll();
    }

    public static function getPending(): array {
        return getPDO()->query(
            "SELECT a.*, uc.pseudo as chauffeur_pseudo, up.pseudo as passager_pseudo
             FROM avis a
             JOIN utilisateur uc ON uc.utilisateur_id = a.chauffeur_id
             JOIN utilisateur up ON up.utilisateur_id = a.passager_id
             WHERE a.statut = 'en_attente'
             ORDER BY a.created_at ASC"
        )->fetchAll();
    }

    public static function validate(int $id): void {
        getPDO()->prepare("UPDATE avis SET statut = 'valide' WHERE avis_id = ?")
            ->execute([$id]);
        self::syncMongoStatut($id, 'valide');
    }

    public static function refuse(int $id): void {
        getPDO()->prepare("UPDATE avis SET statut = 'refuse' WHERE avis_id = ?")
            ->execute([$id]);
        self::syncMongoStatut($id, 'refuse');
    }

    private static function syncMongoStatut(int $mysqlId, string $statut): void {
        $col = getMongoCollection('avis');
        if (!$col) return;
        $col->updateOne(
            ['mysql_avis_id' => $mysqlId],
            ['$set' => ['statut' => $statut]]
        );
    }
}
