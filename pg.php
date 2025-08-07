<?php

class Sync {

    public $url = 'http://51.91.251.252:8081/api/attendance/sync';
    public $school = 'kamo';
    public $pdo;

    /** @var array */
    protected $config;

    /** @var bool */
    protected $allowConnect = true;

    /** @var string */
    protected $failHandler;

    /** @var int */
    protected $updateCountsSuspended = 0;

    public function __construct() {
       // $this->connect(); 
        return $this->sendMessage('255714825469@c.us', 'Testing message with cron job at '.date('Y M d h:i'));
    }

    public function connect($failHandler = null) {

        $this->config = array(
            'driver' => 'pgsql',
            'host' => 'localhost',
            'username' => 'postgres',
            'password' => 'tabita',
            'database' => 'biotime',
            'schema' => 'public',
            'port' => '7496',
        );
        if (!$this->allowConnect) {
            qa_fatal_error('It appears that a plugin is trying to access the database, but this is not allowed until Q2A initialization is complete.');
            return;
        }
        if ($failHandler !== null) {
            // set this even if connection already opened
            $this->failHandler = $failHandler;
        }
        if ($this->pdo) {
            return;
        }

        //$dsn = "pgsql:host=$host;port=5432;dbname=$db;user=$username;password=$password";

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
            $this->pdo->exec('SET search_path TO forum');
        } catch (PDOException $ex) {
            $this->failError('connect', $ex->getCode(), $ex->getMessage());
        }
    }

    public function select($query) {

        return $this->pdo->query($query)->fetchAll();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    function addCreatedAt() {
        $tables = $this->select("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema= 'public'  AND table_type='BASE TABLE' AND table_name not like '%payrol%'");
        foreach ($tables as $table) {
            $sql = 'ALTER TABLE  ' . $table->table_name . '   ADD COLUMN IF NOT EXISTS created_at timestamp without time zone DEFAULT now(); ';
            $this->select($sql);
        }
        return TRUE;
    }

    function loadTables() {
        // $this->addCreatedAt();
        $tables = $this->select("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE table_schema= 'public'  AND table_type='BASE TABLE' AND table_name not like '%payrol%' ORDER BY table_name");
        $object = [];
        foreach ($tables as $table) {
            $table = (object) $table;
            array_push($object, [$table->table_name => $this->select("select * from public." . $table->table_name . " WHERE created_at >= NOW() - INTERVAL '10 minutes'")]);
        }

        return $this->curlPrivate($object);
    }

    public function curlPrivate($fields) {
        // Open connection
        $ch = curl_init();
// Set the url, number of POST vars, POST data

        curl_setopt($ch, CURLOPT_URL, $this->url . '?schema_name=' . $this->school);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'application/x-www-form-urlencoded'
        ));

        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    function gettable() {



        //then load all primary keys and add them
        //load data from these tables and add them
        //load all relationships and add them
        //close and create views manually
    }

    public function syncTable() {
        $master_table_name = request('table');
        $slave_schema = request('slave');
        $sql = \collect(DB::select("select show_create_table('" . $master_table_name . "','" . $slave_schema . "') as result"))->first();
        return DB::statement(str_replace('ARRAY', 'character varying[]', $sql->result));
    }

    public function sendMessage($chatId, $text) {
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

return new Sync();
