<?php
class Tarefa {
    private $conn;
    private $table = "PI_Tarefas";
    
    public $id_tarefa;
    public $nome_tarefa;
    public $dificuldade;
    public $prazo;
    public $id_usuario;
    public $ativo;
    
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
        
        $query = "INSERT INTO " . $this->table . " 
                  (nome_tarefa, difficulty, prazo, id_usuario, ativo) 
                  VALUES (:nome_tarefa, :dificuldade, :prazo, :id_usuario, :ativo)";
        
        // Wait, the column in database is difficulty or dificuldade? Let's check:
        // Table structure showed: dificuldade (varchar(50)).
        // Ah, but in the query, it was: (nome_tarefa, dificuldade, prazo, id_usuario)
        // Let's use: (nome_tarefa, dificuldade, prazo, id_usuario, ativo)
        $query = "INSERT INTO " . $this->table . " 
                  (nome_tarefa, dificuldade, prazo, id_usuario, ativo) 
                  VALUES (:nome_tarefa, :dificuldade, :prazo, :id_usuario, :ativo)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome_tarefa = htmlspecialchars(strip_tags($this->nome_tarefa));
        $this->dificuldade = htmlspecialchars(strip_tags($this->dificuldade));
        $this->ativo = $this->ativo ?? 1;
        
        $stmt->bindParam(":nome_tarefa", $this->nome_tarefa);
        $stmt->bindParam(":dificuldade", $this->dificuldade);
        $stmt->bindParam(":prazo", $this->prazo);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":ativo", $this->ativo);
        
        if($stmt->execute()) {
            $this->id_tarefa = $this->conn->lastInsertId();
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
    
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_tarefa = ? AND id_usuario = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_tarefa);
        $stmt->bindParam(2, $this->id_usuario);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if($row) {
            $this->nome_tarefa = $row['nome_tarefa'];
            $this->dificuldade = $row['dificuldade'];
            $this->prazo = $row['prazo'];
            $this->ativo = $row['ativo'];
            return true;
        }
        return false;
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET nome_tarefa = :nome_tarefa, dificuldade = :dificuldade, prazo = :prazo, ativo = :ativo 
                  WHERE id_tarefa = :id AND id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nome_tarefa", $this->nome_tarefa);
        $stmt->bindParam(":dificuldade", $this->dificuldade);
        $stmt->bindParam(":prazo", $this->prazo);
        $stmt->bindParam(":ativo", $this->ativo);
        $stmt->bindParam(":id", $this->id_tarefa);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id_tarefa = :id AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_tarefa);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        return $stmt->execute();
    }
}
?>