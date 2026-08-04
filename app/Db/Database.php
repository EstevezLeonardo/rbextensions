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

        

        $query = 'INSERT INTO `'.$this->table.'` ('.implode(',', $fields).') VALUES ('.implode(',', $binds).')';

        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($values));

        return $this->connection->lastInsertId();
    
    }
    public function select($where = null, $order = null, $limit = null, $fields = '*') {
        $where = !empty($where) ? 'WHERE '.$where : '';
        $order = !empty($order) ? 'ORDER BY '.$order : '';
        $limit = !empty($limit) ? 'LIMIT '.$limit : '';

        $query = 'SELECT '.$fields.' FROM `'.$this->table.'` '.$where.' '.$order.' '.$limit;

        return $this->execute($query);
    }

    public function update($where, $values) {
        $fields = array_keys($values);

        $query = 'UPDATE `'.$this->table.'` SET '.implode('=?,', $fields).'=? WHERE '.$where;

        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($values));

        return true;
    }
    public function delete($where) {
        $query = 'DELETE FROM `'.$this->table.'` WHERE '.$where;

        $stmt = $this->connection->prepare($query);
        $stmt->execute();

        return true;
    }
}