<?php
class Leitura {
    private $conn;
    private $table = "PI_HistoricoLeituras";
    
    public $id_leitura;
    public $id_usuario;
    public $titulo;
    public $data;
    public $paginas;
    public $anotacoes;
    public $resumo;
    public $texto_completo;
    
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
                  (id_usuario, titulo, data, paginas, anotacoes, resumo, texto_completo) 
                  VALUES (:id_usuario, :titulo, :data, :paginas, :anotacoes, :resumo, :texto_completo)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->data = $this->data ?? date('Y-m-d H:i:s');
        
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":paginas", $this->paginas);
        $stmt->bindParam(":anotacoes", $this->anotacoes);
        $stmt->bindParam(":resumo", $this->resumo);
        $stmt->bindParam(":texto_completo", $this->texto_completo);
        
        if($stmt->execute()) {
            $this->id_leitura = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function read($id_usuario) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_usuario = :id_usuario 
                  ORDER BY data DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }
    
    public function readOne($id_leitura, $id_usuario) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_leitura = :id_leitura AND id_usuario = :id_usuario 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_leitura", $id_leitura);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update($id_leitura, $id_usuario) {
        $fields = [];
        $params = [
            ":id_leitura" => $id_leitura,
            ":id_usuario" => $id_usuario
        ];
        
        if($this->titulo !== null) {
            $fields[] = "titulo = :titulo";
            $params[":titulo"] = htmlspecialchars(strip_tags($this->titulo));
        }
        if($this->paginas !== null) {
            $fields[] = "paginas = :paginas";
            $params[":paginas"] = $this->paginas;
        }
        if($this->anotacoes !== null) {
            $fields[] = "anotacoes = :anotacoes";
            $params[":anotacoes"] = $this->anotacoes;
        }
        if($this->resumo !== null) {
            $fields[] = "resumo = :resumo";
            $params[":resumo"] = $this->resumo;
        }
        if($this->texto_completo !== null) {
            $fields[] = "texto_completo = :texto_completo";
            $params[":texto_completo"] = $this->texto_completo;
        }
        
        if(empty($fields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " 
                  WHERE id_leitura = :id_leitura AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }
    
    public function delete($id_leitura, $id_usuario) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id_leitura = :id_leitura AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_leitura", $id_leitura);
        $stmt->bindParam(":id_usuario", $id_usuario);
        return $stmt->execute();
    }
}
?>
