<?php
class Feynman {
    private $conn;
    private $table = "PI_GravacoesEstudo";
    
    public $id_gravacao;
    public $id_usuario;
    public $id_materia;
    public $data;
    public $url;
    public $modo = 'feynman';
    public $nome;
    public $check_palavras_simples = 0;
    public $check_analogias = 0;
    public $check_lacunas = 0;
    public $check_simplificado = 0;
    public $anotacoes;
    public $duracao = 0;
    
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
                  (id_usuario, id_materia, data, url, modo, nome, check_palavras_simples, check_analogias, check_lacunas, check_simplificado, anotacoes, duracao) 
                  VALUES (:id_usuario, :id_materia, :data, :url, :modo, :nome, :check_palavras_simples, :check_analogias, :check_lacunas, :check_simplificado, :anotacoes, :duracao)";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->data = $this->data ?? date('Y-m-d H:i:s');
        $this->url = $this->url ?? '';
        $this->modo = !empty($this->modo) ? $this->modo : 'feynman';
        $this->check_palavras_simples = !empty($this->check_palavras_simples) ? 1 : 0;
        $this->check_analogias = !empty($this->check_analogias) ? 1 : 0;
        $this->check_lacunas = !empty($this->check_lacunas) ? 1 : 0;
        $this->check_simplificado = !empty($this->check_simplificado) ? 1 : 0;
        $this->duracao = (int)($this->duracao ?? 0);

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        if (!empty($this->id_materia)) {
            $stmt->bindParam(":id_materia", $this->id_materia);
        } else {
            $nullMat = null;
            $stmt->bindParam(":id_materia", $nullMat, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":url", $this->url);
        $stmt->bindParam(":modo", $this->modo);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":check_palavras_simples", $this->check_palavras_simples);
        $stmt->bindParam(":check_analogias", $this->check_analogias);
        $stmt->bindParam(":check_lacunas", $this->check_lacunas);
        $stmt->bindParam(":check_simplificado", $this->check_simplificado);
        $stmt->bindParam(":anotacoes", $this->anotacoes);
        $stmt->bindParam(":duracao", $this->duracao);
        
        if($stmt->execute()) {
            $this->id_gravacao = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
    
    public function read($id_usuario) {
        $query = "SELECT g.*, mat.nome as nome_materia, mat.cor as cor_materia 
                  FROM " . $this->table . " g
                  LEFT JOIN PI_Materia mat ON g.id_materia = mat.id_materia
                  WHERE g.id_usuario = :id_usuario AND g.modo = :modo
                  ORDER BY g.data DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->bindParam(":modo", $this->modo);
        $stmt->execute();
        return $stmt;
    }
    
    public function readOne() {
        $query = "SELECT g.*, mat.nome as nome_materia, mat.cor as cor_materia 
                  FROM " . $this->table . " g
                  LEFT JOIN PI_Materia mat ON g.id_materia = mat.id_materia
                  WHERE g.id_gravacao = :id AND g.id_usuario = :id_usuario AND g.modo = :modo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_gravacao);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":modo", $this->modo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET id_materia = :id_materia, 
                      data = :data, 
                      url = :url, 
                      nome = :nome, 
                      check_palavras_simples = :check_palavras_simples, 
                      check_analogias = :check_analogias, 
                      check_lacunas = :check_lacunas, 
                      check_simplificado = :check_simplificado, 
                      anotacoes = :anotacoes, 
                      duracao = :duracao 
                  WHERE id_gravacao = :id AND id_usuario = :id_usuario AND modo = :modo";
        
        $stmt = $this->conn->prepare($query);
        
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->data = date('Y-m-d H:i:s');
        $this->url = $this->url ?? '';
        $this->check_palavras_simples = !empty($this->check_palavras_simples) ? 1 : 0;
        $this->check_analogias = !empty($this->check_analogias) ? 1 : 0;
        $this->check_lacunas = !empty($this->check_lacunas) ? 1 : 0;
        $this->check_simplificado = !empty($this->check_simplificado) ? 1 : 0;
        $this->duracao = (int)($this->duracao ?? 0);

        if (!empty($this->id_materia)) {
            $stmt->bindParam(":id_materia", $this->id_materia);
        } else {
            $nullMat = null;
            $stmt->bindParam(":id_materia", $nullMat, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":url", $this->url);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":check_palavras_simples", $this->check_palavras_simples);
        $stmt->bindParam(":check_analogias", $this->check_analogias);
        $stmt->bindParam(":check_lacunas", $this->check_lacunas);
        $stmt->bindParam(":check_simplificado", $this->check_simplificado);
        $stmt->bindParam(":anotacoes", $this->anotacoes);
        $stmt->bindParam(":duracao", $this->duracao);
        $stmt->bindParam(":id", $this->id_gravacao);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":modo", $this->modo);
        
        return $stmt->execute();
    }
    
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_gravacao = :id AND id_usuario = :id_usuario AND modo = :modo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_gravacao);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":modo", $this->modo);
        return $stmt->execute();
    }
}
?>
