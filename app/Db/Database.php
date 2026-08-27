<?php

namespace App\Db;

use \PDO;
use \PDOException;

/**
 * Camada de acesso ao banco de dados (MySQL via PDO).
 *
 * Encapsula a conexão PDO e monta as queries SELECT/INSERT/UPDATE/DELETE
 * para uma tabela específica. Todos os valores fornecidos pelo chamador
 * (WHERE, SET, etc.) devem ser passados como parâmetros (`?`) e nunca
 * concatenados na string da query, para evitar SQL injection.
 */
class Database {

    /** @var string Nome da tabela que esta instância manipula. */
    private $table;

    /** @var PDO Conexão ativa com o banco. */
    private $connection;

    public function __construct($table = null) {
        $this->table = $table;
        $this->setConnection();
    }

    /**
     * Lê o arquivo .env (se existir) e registra suas variáveis via putenv(),
     * sem sobrescrever variáveis de ambiente já definidas fora do PHP.
     * Roda apenas uma vez por processo (cache em variável estática).
     */
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

    /**
     * Lê uma variável de ambiente (carregando o .env se necessário),
     * retornando $default caso ela não esteja definida.
     */
    private static function env($key, $default) {
        self::loadEnv();
        $value = getenv($key);
        return $value !== false ? $value : $default;
    }

    /**
     * Abre a conexão PDO usando as credenciais do .env (com valores
     * padrão para desenvolvimento local no XAMPP). Em caso de falha,
     * registra o erro real no log do servidor e mostra uma mensagem
     * genérica ao usuário, para não vazar detalhes internos do banco.
     */
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

    /**
     * Prepara e executa uma query com parâmetros vinculados (prepared
     * statement), retornando o PDOStatement resultante.
     */
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

    /**
     * Insere uma linha na tabela. $values é um array associativo
     * campo => valor; os valores são sempre vinculados via `?`.
     *
     * @return string Id gerado pelo AUTO_INCREMENT (lastInsertId).
     */
    public function insert($values) {
        $fields = array_keys($values);
        $binds = array_fill(0, count($fields), '?');



        $query = 'INSERT INTO `'.$this->table.'` ('.implode(',', $fields).') VALUES ('.implode(',', $binds).')';

        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_values($values));

        return $this->connection->lastInsertId();

    }

    /**
     * Monta e executa um SELECT. $where deve conter placeholders `?`
     * (ex: 'email = ?'), com os valores correspondentes em $params.
     */
    public function select($where = null, $order = null, $limit = null, $fields = '*', $params = []) {
        $where = !empty($where) ? 'WHERE '.$where : '';
        $order = !empty($order) ? 'ORDER BY '.$order : '';
        $limit = !empty($limit) ? 'LIMIT '.$limit : '';

        $query = 'SELECT '.$fields.' FROM `'.$this->table.'` '.$where.' '.$order.' '.$limit;

        return $this->execute($query, $params);
    }

    /**
     * Atualiza linhas que casem com $where. $values é campo => valor
     * (vira o SET); $whereParams são os valores dos placeholders `?`
     * usados dentro de $where (ex: ['id = ?', [$id]]).
     */
    public function update($where, $values, $whereParams = []) {
        $fields = array_keys($values);

        $query = 'UPDATE `'.$this->table.'` SET '.implode('=?,', $fields).'=? WHERE '.$where;

        $stmt = $this->connection->prepare($query);
        $stmt->execute(array_merge(array_values($values), $whereParams));

        return true;
    }

    /**
     * Remove linhas que casem com $where (placeholders `?` com
     * valores em $params).
     */
    public function delete($where, $params = []) {
        $query = 'DELETE FROM `'.$this->table.'` WHERE '.$where;

        $stmt = $this->connection->prepare($query);
        $stmt->execute($params);

        return true;
    }
}
