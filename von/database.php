<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Database {

    public $pdo;

    /** @var array */
    protected $config;

    /** @var bool */
    protected $allowConnect = true;

    /** @var string */
    protected $failHandler;

    /** @var int */
    protected $updateCountsSuspended = 0;
    protected $param;

    public function __construct($connection) {
        $param = [
            'sqlite' => [
                'driver' => 'sqlite',
                'url' => 'url',
                'database' => 'database',
                'prefix' => '',
                'foreign_key_constraints' => TRUE,
            ],
            'stock' => [
                'driver' => 'mysql',
                'host' => 'pdb44.runhosting.com',
                'port' => '3306',
                'database' => '3737687_stock',
                'username' => '3737687_stock',
                'password' => 'cE6Bvk9l7^tV-IQB',
                'unix_socket' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'schema' => '',
                'engine' => null,
            ],
            'dikodiko' => [
                'driver' => 'pgsql',
                'url' => '', //env('DATABASE_URL'),
                'host' => 'pgdb1.runhosting.com',
                'port' => '5432',
                'database' => '3737687_dikodiko',
                'username' => '3737687_dikodiko',
                'password' => 'IP)}q8hN7C5onu/u',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => 'dikodiko',
                'sslmode' => 'prefer',
            ],
            'ibada' => [
                'driver' => 'pgsql',
                'url' => '',
                'host' => 'pgdb1.runhosting.com',
                'port' => '5432',
                'database' => '3737687_ibada',
                'username' => '3737687_ibada',
                'password' => 'J(baQ8Ir9y}R)W-C',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => 'gospel',
                'sslmode' => 'prefer',
            ],
            'sqlsrv' => [
                'driver' => 'pgsql',
                'url' => '127.0.0.1',
                'host' => '127.0.0.1',
                'port' => '5432',
                'database' => 'other_app',
                'username' => 'postgres',
                'password' => 'tabita',
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => 'dikodiko',
                'sslmode' => 'prefer',
            ],
        ];
        $this->param = (object) $param[strtolower(trim($connection))];
        return $this->connect();
    }

    public function reset($database,$username,$password) {
        $this->param = (object) [
                    'driver' => 'mysql',
                    'host' => 'pdb44.runhosting.com',
                    'port' => '3306',
                    'database' => $database,
                    'username' => $username,
                    'password' => $password,
                    'unix_socket' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'schema' => '',
                    'engine' => null,
        ];
        return $this->connect();
    }

    public function connect($failHandler = null) {
        $this->config = array(
            'driver' => $this->param->driver,
            'host' => $this->param->host,
            'username' => $this->param->username,
            'password' => $this->param->password,
            'database' => $this->param->database,
            'schema' => $this->param->schema,
            'port' => $this->param->port,
        );

        if (!$this->allowConnect) {
            qa_fatal_error('It appears that a plugin is trying to access the database, but this is not allowed until Q2A initialization is complete.');
            return;
        }
        if ($failHandler !== null) {
            $this->failHandler = $failHandler;
        }
        if ($this->pdo) {
            return;
        }
        $dsn = sprintf('%s:host=%s;dbname=%s', $this->config['driver'], $this->config['host'], $this->config['database']);
        if (isset($this->config['port'])) {
            $dsn .= ';port=' . $this->config['port'];
        }

        $options = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true, // required for queries like LOCK TABLES (also, slightly faster)
        );

        $options[PDO::ATTR_PERSISTENT] = true;

        try {
            $this->pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
            /// $this->param->driver=='pgsql'? $this->pdo->exec('SET search_path =' . $this->param->schema):'';
        } catch (PDOException $ex) {
            die('connect ' . $ex->getCode() . $ex->getMessage());
        }
    }

    public function select($query) {
        return $this->pdo->query($query)->fetchAll();
    }

    public function sendMessage($chatId, $text, $format = null) {
        $data = array('chatId' => $chatId, 'body' => $text);

        $method = 'message';
        $whatsapp_url = 'https://api.chat-api.com/instance269111/';
        $token = 'fztc8hvuc6lrwbyr';
        $url = $whatsapp_url . $method . '?token=' . $token;
        if (is_array($data)) {
            $data = json_encode($data);
        }
        $options = stream_context_create(['http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/json',
                'content' => $data]]);

        return file_get_contents($url, false, $options);
    }

}
