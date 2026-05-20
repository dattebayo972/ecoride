<?php
require_once __DIR__ . '/../config/db_mysql.php';

class CovoiturageModel {

    public static function search(string $depart, string $arrivee, string $date, array $filters = []): array {
        $sql = "SELECT c.*, u.pseudo, u.photo, v.energie,
                       (SELECT AVG(a.note) FROM avis a WHERE a.chauffeur_id = c.chauffeur_id AND a.statut='valide') as note_moy
                FROM covoiturage c
                JOIN utilisateur u ON u.utilisateur_id = c.chauffeur_id
                JOIN voiture v ON v.voiture_id = c.voiture_id
                WHERE c.lieu_depart LIKE ?
                  AND c.lieu_arrivee LIKE ?
                  AND c.date_depart = ?
                  AND c.nb_place >= 1
                  AND c.statut = 'planifie'";

        $params = ["%{$depart}%", "%{$arrivee}%", $date];

        if (!empty($filters['ecologique'])) {
            $sql .= " AND v.energie = 'electrique'";
        }
        if (!empty($filters['prix_max'])) {
            $sql .= ' AND c.prix_personne <= ?';
            $params[] = (float) $filters['prix_max'];
        }
        if (!empty($filters['duree_max'])) {
            $sql .= ' AND TIMESTAMPDIFF(MINUTE, CONCAT(c.date_depart," ",c.heure_depart), CONCAT(c.date_arrivee," ",c.heure_arrivee)) <= ?';
            $params[] = (int) $filters['duree_max'];
        }
        if (!empty($filters['note_min'])) {
            $sql .= ' HAVING note_moy >= ? OR note_moy IS NULL';
            $params[] = (float) $filters['note_min'];
        }

        $sql .= ' ORDER BY c.heure_depart ASC';

        $stmt = getPDO()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function nextAvailable(string $depart, string $arrivee, string $date): ?array {
        $stmt = getPDO()->prepare(
            "SELECT MIN(date_depart) as prochaine
             FROM covoiturage
             WHERE lieu_depart LIKE ? AND lieu_arrivee LIKE ?
               AND date_depart > ? AND nb_place >= 1 AND statut = 'planifie'"
        );
        $stmt->execute(["%{$depart}%", "%{$arrivee}%", $date]);
        $row = $stmt->fetch();
        return $row['prochaine'] ? $row : null;
    }

    public static function findById(int $id): ?array {
        $stmt = getPDO()->prepare(
            "SELECT c.*, u.pseudo, u.photo, u.email as chauffeur_email,
                    v.modele, v.energie, v.couleur, v.immatriculation, v.nb_place as voiture_nb_place,
                    m.libelle as marque,
                    (SELECT AVG(a.note) FROM avis a WHERE a.chauffeur_id = c.chauffeur_id AND a.statut='valide') as note_moy
             FROM covoiturage c
             JOIN utilisateur u ON u.utilisateur_id = c.chauffeur_id
             JOIN voiture v ON v.voiture_id = c.voiture_id
             JOIN marque m ON m.marque_id = v.marque_id
             WHERE c.covoiturage_id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo  = getPDO();
        $stmt = $pdo->prepare(
            "INSERT INTO covoiturage
             (date_depart, heure_depart, lieu_depart, date_arrivee, heure_arrivee, lieu_arrivee,
              nb_place, prix_personne, voiture_id, chauffeur_id, statut)
             VALUES (?,?,?,?,?,?,?,?,?,?,'planifie')"
        );
        $stmt->execute([
            $data['date_depart'], $data['heure_depart'], $data['lieu_depart'],
            $data['date_arrivee'], $data['heure_arrivee'], $data['lieu_arrivee'],
            $data['nb_place'], $data['prix_personne'],
            $data['voiture_id'], $data['chauffeur_id'],
        ]);
        return (int) $pdo->lastInsertId();
    }

    public static function updateStatut(int $id, string $statut): void {
        getPDO()->prepare("UPDATE covoiturage SET statut = ? WHERE covoiturage_id = ?")
            ->execute([$statut, $id]);
    }

    public static function decrementPlace(int $id): void {
        getPDO()->prepare("UPDATE covoiturage SET nb_place = nb_place - 1 WHERE covoiturage_id = ?")
            ->execute([$id]);
    }

    public static function incrementPlace(int $id): void {
        getPDO()->prepare("UPDATE covoiturage SET nb_place = nb_place + 1 WHERE covoiturage_id = ?")
            ->execute([$id]);
    }

    // Récupère les passagers d'un covoiturage (pour annulation chauffeur)
    public static function getPassagers(int $covoiturageId): array {
        $stmt = getPDO()->prepare(
            "SELECT u.utilisateur_id, u.email, u.pseudo, u.credits
             FROM utilisateur u
             JOIN participe p ON p.utilisateur_id = u.utilisateur_id
             WHERE p.covoiturage_id = ? AND p.statut = 'confirme'"
        );
        $stmt->execute([$covoiturageId]);
        return $stmt->fetchAll();
    }

    // Historique chauffeur
    public static function getByChauffeur(int $userId): array {
        $stmt = getPDO()->prepare(
            "SELECT c.*, v.modele, v.energie, m.libelle as marque
             FROM covoiturage c
             JOIN voiture v ON v.voiture_id = c.voiture_id
             JOIN marque m ON m.marque_id = v.marque_id
             WHERE c.chauffeur_id = ?
             ORDER BY c.date_depart DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Historique passager
    public static function getByPassager(int $userId): array {
        $stmt = getPDO()->prepare(
            "SELECT c.*, p.statut as participe_statut, p.avis_envoye,
                    u.pseudo as chauffeur_pseudo, v.energie, m.libelle as marque, v.modele
             FROM covoiturage c
             JOIN participe p ON p.covoiturage_id = c.covoiturage_id
             JOIN utilisateur u ON u.utilisateur_id = c.chauffeur_id
             JOIN voiture v ON v.voiture_id = c.voiture_id
             JOIN marque m ON m.marque_id = v.marque_id
             WHERE p.utilisateur_id = ?
             ORDER BY c.date_depart DESC"
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function isParticipant(int $userId, int $covoiturageId): bool {
        $stmt = getPDO()->prepare(
            "SELECT 1 FROM participe WHERE utilisateur_id = ? AND covoiturage_id = ? AND statut = 'confirme'"
        );
        $stmt->execute([$userId, $covoiturageId]);
        return (bool) $stmt->fetch();
    }

    public static function addParticipant(int $userId, int $covoiturageId): void {
        getPDO()->prepare(
            "INSERT INTO participe (utilisateur_id, covoiturage_id, statut) VALUES (?, ?, 'confirme')"
        )->execute([$userId, $covoiturageId]);
    }

    public static function cancelParticipation(int $userId, int $covoiturageId): void {
        getPDO()->prepare(
            "UPDATE participe SET statut = 'annule' WHERE utilisateur_id = ? AND covoiturage_id = ?"
        )->execute([$userId, $covoiturageId]);
    }

    public static function validateParticipation(int $userId, int $covoiturageId): void {
        getPDO()->prepare(
            "UPDATE participe SET statut = 'valide' WHERE utilisateur_id = ? AND covoiturage_id = ?"
        )->execute([$userId, $covoiturageId]);
    }

    public static function setLitige(int $userId, int $covoiturageId): void {
        getPDO()->prepare(
            "UPDATE participe SET statut = 'litige' WHERE utilisateur_id = ? AND covoiturage_id = ?"
        )->execute([$userId, $covoiturageId]);
    }

    public static function markAvisEnvoye(int $userId, int $covoiturageId): void {
        getPDO()->prepare(
            "UPDATE participe SET avis_envoye = 1 WHERE utilisateur_id = ? AND covoiturage_id = ?"
        )->execute([$userId, $covoiturageId]);
    }

    // Stats admin
    public static function countPerDay(): array {
        return getPDO()->query(
            "SELECT date_depart as jour, COUNT(*) as total
             FROM covoiturage
             WHERE statut = 'termine'
             GROUP BY date_depart
             ORDER BY date_depart DESC
             LIMIT 30"
        )->fetchAll();
    }

    public static function creditsPerDay(): array {
        return getPDO()->query(
            "SELECT date_depart as jour, SUM(2) as credits_plateforme
             FROM covoiturage
             WHERE statut = 'termine'
             GROUP BY date_depart
             ORDER BY date_depart DESC
             LIMIT 30"
        )->fetchAll();
    }

    public static function totalCreditsplateforme(): int {
        $row = getPDO()->query(
            "SELECT COUNT(*) * 2 as total FROM covoiturage WHERE statut = 'termine'"
        )->fetch();
        return (int) ($row['total'] ?? 0);
    }

    // Litiges pour espace employé
    public static function getLitiges(): array {
        return getPDO()->query(
            "SELECT c.covoiturage_id, c.date_depart, c.heure_depart, c.lieu_depart, c.lieu_arrivee,
                    uc.pseudo as chauffeur_pseudo, uc.email as chauffeur_email,
                    up.utilisateur_id as passager_id,
                    up.pseudo as passager_pseudo, up.email as passager_email,
                    p.statut as participe_statut
             FROM participe p
             JOIN covoiturage c ON c.covoiturage_id = p.covoiturage_id
             JOIN utilisateur uc ON uc.utilisateur_id = c.chauffeur_id
             JOIN utilisateur up ON up.utilisateur_id = p.utilisateur_id
             WHERE p.statut = 'litige'
             ORDER BY c.date_depart DESC"
        )->fetchAll();
    }

    public static function resolveLitige(int $passagerId, int $covoiturageId): void {
        getPDO()->prepare(
            "UPDATE participe SET statut = 'valide' WHERE utilisateur_id = ? AND covoiturage_id = ?"
        )->execute([$passagerId, $covoiturageId]);
    }
}
