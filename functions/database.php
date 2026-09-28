<?php

include_once __DIR__ . '/url.php';
include_once __DIR__ . '/config.php';

ini_set('display_errors', app_config('APP_DEBUG') === '1' ? '1' : '0');
ini_set('log_errors', 1);

class MySQLDatabase {

    private $host;
    private $db_user;
    private $pass;
    private $dbase;
    private $connection;

    function __construct() {
        $this->host = app_config('SHORTNY_DB_HOST');
        $this->db_user = app_config('SHORTNY_DB_USER');
        $this->pass = app_config('SHORTNY_DB_PASS');
        $this->dbase = app_config('SHORTNY_DB_NAME');
        $this->open_connection();
    }

    public function crud() { 
        try {
            $DB_con = new PDO("mysql:host={$this->host};dbname={$this->dbase}", $this->db_user, $this->pass);
            $DB_con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            error_log($e->getMessage());
        };
        return new crud($DB_con);
    }

    public function open_connection() {
        $this->connection = mysqli_connect($this->host, $this->db_user, $this->pass, $this->dbase);
        if (mysqli_connect_errno()) {
            error_log('Database connection failed: ' . mysqli_connect_error() . ' (' . mysqli_connect_errno() . ')');
            die('Database connection failed.');
        }
        mysqli_set_charset($this->connection, 'utf8mb4');
    }

    public function close_connection() {
        if (isset($this->connection)) {
            mysqli_close($this->connection);
            unset($this->connection);
        }
    }

    public function query($sql) {
        $result = mysqli_query($this->connection, $sql);
        $this->confirm_query($result);
        return $result;
    }

    private function confirm_query($result) {
        if (!$result) {
            error_log('Database query failed: ' . mysqli_error($this->connection));
            die('Database operation failed.');
        }
    }

    public function escape_value($string) {
        $escaped_string = mysqli_real_escape_string($this->connection, $string);
        return $escaped_string;
    }

    // "database neutral" functions

    public function fetch_array($result_set) {
        return mysqli_fetch_assoc($result_set);
    }

    public function num_rows($result_set) {
        return mysqli_num_rows($result_set);
    }

    public function insert_id() {
        // get the last id inserted over the current db connection
        return mysqli_insert_id($this->connection);
    }

    public function affected_rows() {
        return mysqli_affected_rows($this->connection);
    }

}

$database = new MySQLDatabase();
$db = & $database;
?>
