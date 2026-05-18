<?php
require_once __DIR__ . '/../config/db_mysql.php';

class MarqueModel {
    public static function getAll(): array {
        return getPDO()->query('SELECT * FROM marque ORDER BY libelle ASC')->fetchAll();
    }
}
