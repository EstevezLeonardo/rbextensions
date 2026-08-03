<?php

namespace App\Db;

use \PDO;
use \PDOException;

class Database {
    
const HOST = 'localhost';
    const NAME = 'rbextensions';
    const USER = 'root';
    const PASS = '';
    
    private $table;

    private $connection;

    public function __construct($table = null) {
        $this->table = $table;
        $this->setConnection();
    }
    private function setConnection() {
        try {
            $this->connection = new PDO('mysql:host='.self::HOST.';dbname='.self::NAME, self::USER, self::PASS);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            die('ERROR: '.$e->getMessage());
        }
    }

    public function insert($values) {
        $fields = array_keys($values);
        $binds = array_fill(0, count($fields), '?');

        $fieldList = implode(',', array_map(function($f){ return "`".$f."`"; }, $fields));
        $placeholders = implode(',', $binds);

        $query = 'INSERT INTO `'.$this->table.'` ('.$fieldList.') VALUES ('.$placeholders.')';

        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($values));

        return $this->connection->lastInsertId();
    
    }
}