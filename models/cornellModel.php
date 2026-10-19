<?php
class Cornell {
    private $conn;
    private $table = "PI_Cornell";
    
    public $id_cornell;
    public $id_usuario;
    public $id_materia;
    public $titulo;
    public $pergunta;
    public $resposta;
    public $resumo;
    public $data_criacao;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function read($id_usuario) {
        $query = "SELECT c.*, m.nome as nome_materia, m.cor as cor_materia 
                  FROM " . $this->table . " c 
                  LEFT JOIN PI_Materia m ON c.id_materia = m.id_materia 
                  WHERE c.id_usuario = :id_usuario 
                  ORDER BY c.data_criacao DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }
    
    public function create() {
        $query = "INSERT INTO " . $this->table . " (id_usuario, id_materia, titulo, pergunta, resposta, resumo) 
                  VALUES (:id_usuario, :id_materia, :titulo, :pergunta, :resposta, :resumo)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        if(empty($this->id_materia)) {
            $stmt->bindValue(":id_materia", null, PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(":id_materia", (int)$this->id_materia, PDO::PARAM_INT);
        }
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":pergunta", $this->pergunta);
        $stmt->bindParam(":resposta", $this->resposta);
        $stmt->bindParam(":resumo", $this->resumo);
        
        if($stmt->execute()) {
            $this->id_cornell = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_cornell = :id AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_cornell);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        return $stmt->execute();
    }
}
?>