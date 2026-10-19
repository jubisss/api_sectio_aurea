<?php
class DiagramaFluxo {
    private $conn;
    private $table = "PI_DiagramasFluxo";
    
    public $id_diagrama;
    public $id_usuario;
    public $id_materia;
    public $titulo;
    public $data;
    public $nos;
    public $conexoes;
    
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
        
        $query = "INSERT INTO " . $this->table . " (id_usuario, id_materia, titulo, data, nos, conexoes) 
                  VALUES (:id_usuario, :id_materia, :titulo, :data, :nos, :conexoes)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->data = $this->data ?? date('Y-m-d H:i:s');
        
        if (is_array($this->nos) || is_object($this->nos)) {
            $this->nos = json_encode($this->nos, JSON_UNESCAPED_UNICODE);
        }
        if (is_array($this->conexoes) || is_object($this->conexoes)) {
            $this->conexoes = json_encode($this->conexoes, JSON_UNESCAPED_UNICODE);
        }

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        if (!empty($this->id_materia)) {
            $stmt->bindParam(":id_materia", $this->id_materia);
        } else {
            $nullMat = null;
            $stmt->bindParam(":id_materia", $nullMat, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":nos", $this->nos);
        $stmt->bindParam(":conexoes", $this->conexoes);
        
        if($stmt->execute()) {
            $this->id_diagrama = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function read($id_usuario) {
        $query = "SELECT d.*, mat.nome as nome_materia, mat.cor as cor_materia 
                  FROM " . $this->table . " d
                  LEFT JOIN PI_Materia mat ON d.id_materia = mat.id_materia
                  WHERE d.id_usuario = :id_usuario 
                  ORDER BY d.data DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }
    
    public function readOne() {
        $query = "SELECT d.*, mat.nome as nome_materia, mat.cor as cor_materia 
                  FROM " . $this->table . " d
                  LEFT JOIN PI_Materia mat ON d.id_materia = mat.id_materia
                  WHERE d.id_diagrama = :id AND d.id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_diagrama);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET id_materia = :id_materia, 
                      titulo = :titulo, 
                      data = :data, 
                      nos = :nos, 
                      conexoes = :conexoes 
                  WHERE id_diagrama = :id AND id_usuario = :id_usuario";
        
        $stmt = $this->conn->prepare($query);
        
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->data = date('Y-m-d H:i:s');
        
        if (is_array($this->nos) || is_object($this->nos)) {
            $this->nos = json_encode($this->nos, JSON_UNESCAPED_UNICODE);
        }
        if (is_array($this->conexoes) || is_object($this->conexoes)) {
            $this->conexoes = json_encode($this->conexoes, JSON_UNESCAPED_UNICODE);
        }

        if (!empty($this->id_materia)) {
            $stmt->bindParam(":id_materia", $this->id_materia);
        } else {
            $nullMat = null;
            $stmt->bindParam(":id_materia", $nullMat, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":nos", $this->nos);
        $stmt->bindParam(":conexoes", $this->conexoes);
        $stmt->bindParam(":id", $this->id_diagrama);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_diagrama = :id AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_diagrama);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        return $stmt->execute();
    }
}
?>
