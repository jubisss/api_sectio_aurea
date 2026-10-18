<?php

class Database {
    private $host = '143.106.241.4';
    private $dbname = 'cl204214';
    private $user = 'cl204214';
    private $pass = 'cl*19072008';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};port=3306;dbname={$this->dbname};charset=utf8",
                $this->user,
                $this->pass
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET session lock_wait_timeout = 5;");
            $this->conn->exec("SET time_zone = '-03:00';");
        } catch (PDOException $e) {
            die(json_encode(["erro" => "Falha na conexão: " . $e->getMessage()]));
        }
        return $this->conn;
    }
}
?>