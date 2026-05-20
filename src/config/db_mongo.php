<?php
require_once __DIR__ . '/db_mysql.php';

/**
 * Wrapper minimal autour de MongoDB\Driver\Manager (ext-mongodb).
 * Pas besoin du package Composer mongodb/mongodb.
 */
class MongoCollection {
    public function __construct(
        private \MongoDB\Driver\Manager $manager,
        private string $namespace
    ) {}

    public function insertOne(array $doc): void {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->insert($doc);
        $this->manager->executeBulkWrite($this->namespace, $bulk);
    }

    public function updateOne(array $filter, array $update): void {
        $bulk = new \MongoDB\Driver\BulkWrite();
        $bulk->update($filter, $update, ['multi' => false, 'upsert' => false]);
        $this->manager->executeBulkWrite($this->namespace, $bulk);
    }

    public function countDocuments(array $filter = []): int {
        $cmd = new \MongoDB\Driver\Command(['count' => explode('.', $this->namespace)[1], 'query' => (object) $filter]);
        [$db] = explode('.', $this->namespace);
        $result = $this->manager->executeCommand($db, $cmd)->toArray();
        return (int) ($result[0]->n ?? 0);
    }
}

function getMongo(): ?\MongoDB\Driver\Manager {
    static $manager = null;
    if ($manager !== null) return $manager;

    if (!extension_loaded('mongodb')) return null;

    $uri    = env('MONGO_URI', 'mongodb://localhost:27017');
    $dbName = env('MONGO_DB', 'ecoride');

    try {
        $manager = new \MongoDB\Driver\Manager($uri);
        $manager->executeCommand($dbName, new \MongoDB\Driver\Command(['ping' => 1]));
    } catch (\Exception $e) {
        $manager = null;
    }
    return $manager;
}

function getMongoCollection(string $name): ?MongoCollection {
    $manager = getMongo();
    if ($manager === null) return null;
    $dbName = env('MONGO_DB', 'ecoride');
    return new MongoCollection($manager, "$dbName.$name");
}
