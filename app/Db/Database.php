<?php

namespace App\Db;

use \PDO;
use \PDOException;

class Database {

    private $table;

    private $connection;

    public function __construct($table = null) {
        $this->table = $table;
        $this->setConnection();
    }

    private static function loadEnv() {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $path = __DIR__.'/../../.env';
        if (!is_file($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if (getenv($key) === false) {
                putenv($key.'='.$value);
            }
        }
    }

    private static function env($key, $default) {
        self::loadEnv();
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    private function setConnection() {
        $host = self::env('DB_HOST', 'localhost');
        $name = self::env('DB_NAME', 'rbextensions');
        $user = self::env('DB_USER', 'root');
        $pass = self::env('DB_PASS', '');

        try {
            $this->connection = new PDO('mysql:host='.$host.';dbname='.$name, $user, $pass);
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log('Database connection error: '.$e->getMessage());
            die('Não foi possível conectar ao banco de dados. Tente novamente mais tarde.');
        }
    }

    public function execute($query, $params = []) {
        try {
            $stmt = $this->connection->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('Database query error: '.$e->getMessage().' | Query: '.$query);
            die('Ocorreu um erro ao processar sua solicitação. Tente novamente mais tarde.');
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
    public function select($where = null, $order = null, $limit = null, $fields = '*', $params = []) {
        $where = !empty($where) ? 'WHERE '.$where : '';
        $order = !empty($order) ? 'ORDER BY '.$order : '';
        $limit = !empty($limit) ? 'LIMIT '.$limit : '';

        $query = 'SELECT '.$fields.' FROM `'.$this->table.'` '.$where.' '.$order.' '.$limit;

        return $this->execute($query, $params);
    }

    public function update($where, $values, $whereParams = []) {
        $fields = array_keys($values);

        $query = 'UPDATE `'.$this->table.'` SET '.implode('=?,', $fields).'=? WHERE '.$where;

        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_merge(array_values($values), $whereParams));

        return true;
    }
    public function delete($where, $params = []) {
        $query = 'DELETE FROM `'.$this->table.'` WHERE '.$where;

        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);

        return true;
    }
}