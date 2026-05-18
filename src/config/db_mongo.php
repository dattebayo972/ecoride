<?php
require_once __DIR__ . '/db_mysql.php'; // Pour charger loadEnv

function getMongo(): ?object {
    static $db = null;
    if ($db !== null) return $db;

    if (!class_exists('MongoDB\Client')) {
        // Extension MongoDB non disponible — on désactive silencieusement
        return null;
    }

    $uri    = $_ENV['MONGO_URI'] ?? 'mongodb://localhost:27017';
    $dbName = $_ENV['MONGO_DB']  ?? 'ecoride';

    try {
        $client = new MongoDB\Client($uri);
        $db = $client->$dbName;
    } catch (Exception $e) {
        $db = null;
    }
    return $db;
}

function getMongoCollection(string $name): ?object {
    $db = getMongo();
    if ($db === null) return null;
    return $db->$name;
}
