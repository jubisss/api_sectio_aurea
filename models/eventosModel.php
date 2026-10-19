<?php
class Evento {
    public $conn;
    private $table = "PI_Eventos";
    
    public $id_evento;
    public $id_usuario;
    public $tipo;
    public $data;
    public $cor;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    private function usuarioExiste($id_usuario) {
        $query = "SELECT id_usuario FROM PI_Usuario WHERE id_usuario = :id AND ativo = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_usuario);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    
    public function create() {
        if(!$this->usuarioExiste($this->id_usuario)) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table . " (id_usuario, tipo, data, cor) 
                  VALUES (:id_usuario, :tipo, :data, :cor)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->tipo = strip_tags($this->tipo);
        $this->cor = strip_tags($this->cor);
        
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":cor", $this->cor);
        
        if($stmt->execute()) {
            $this->id_evento = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function read($id_usuario) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }
    
    public function readByDate($id_usuario, $data) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_usuario = :id_usuario AND data = :data";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":data", $data);
        $stmt->execute();
        return $stmt;
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET tipo = :tipo, data = :data, cor = :cor 
                  WHERE id_evento = :id AND id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":cor", $this->cor);
        $stmt->bindParam(":id", $this->id_evento);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id_evento = :id AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_evento);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        return $stmt->execute();
    }
}
?>