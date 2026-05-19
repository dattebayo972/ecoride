<?php
/**
 * Script d'import SQL — usage unique en production (heroku run php import_db.php)
 * À supprimer après utilisation.
 */
require_once __DIR__ . '/src/config/db_mysql.php';

function runSqlFile(PDO $pdo, string $file): void {
    $sql = file_get_contents($file);

    // Supprimer CREATE DATABASE et USE (JawsDB gère sa propre DB)
    $sql = preg_replace('/^\s*CREATE\s+DATABASE\b[^;]+;/im', '', $sql);
    $sql = preg_replace('/^\s*USE\s+\w+\s*;/im', '', $sql);

    // Découper en statements individuels
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => $s !== ''
    );

    $ok = 0;
    $errors = 0;
    foreach ($statements as $stmt) {
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (PDOException $e) {
            echo "  [WARN] " . substr($stmt, 0, 80) . "\n";
            echo "         " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    echo "  → $ok statements OK, $errors warnings\n";
}

echo "=== Import EcoRide DB ===\n";

try {
    $pdo = getPDO();
    echo "Connexion OK\n\n";

    echo "1/2 create_tables.sql...\n";
    runSqlFile($pdo, __DIR__ . '/sql/create_tables.sql');

    echo "\n2/2 seed_data.sql...\n";
    runSqlFile($pdo, __DIR__ . '/sql/seed_data.sql');

    echo "\n=== Import terminé ===\n";
} catch (Exception $e) {
    echo "ERREUR : " . $e->getMessage() . "\n";
    exit(1);
}
