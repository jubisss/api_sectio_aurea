<?php
class Mnemonica {
    private $conn;
    private $table = "PI_Mnemonica";

    public $id_mnemonica;
    public $id_usuario;
    public $id_materia;
    public $titulo;
    public $descricao;
    public $itens;
    public $criado_em;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " 
                  (id_usuario, id_materia, titulo, descricao, itens) 
                  VALUES (:id_usuario, :id_materia, :titulo, :descricao, :itens)";

        $stmt = $this->conn->prepare($query);

        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descricao = $this->descricao ? htmlspecialchars(strip_tags($this->descricao)) : null;

        // Se $this->itens já for string JSON válida, mantém. Se for array/objeto, converte.
        if (is_array($this->itens) || is_object($this->itens)) {
            $this->itens = json_encode($this->itens, JSON_UNESCAPED_UNICODE);
        }

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        if (!empty($this->id_materia)) {
            $stmt->bindParam(":id_materia", $this->id_materia);
        } else {
            $nullMat = null;
            $stmt->bindParam(":id_materia", $nullMat, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":itens", $this->itens);

        if ($stmt->execute()) {
            $this->id_mnemonica = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read($id_usuario) {
        $query = "SELECT mn.*, m.nome as nome_materia, m.cor as cor_materia 
                  FROM " . $this->table . " mn
                  LEFT JOIN PI_Materia m ON mn.id_materia = m.id_materia
                  WHERE mn.id_usuario = :id_usuario
                  ORDER BY mn.id_mnemonica DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT mn.*, m.nome as nome_materia, m.cor as cor_materia 
                  FROM " . $this->table . " mn
                  LEFT JOIN PI_Materia m ON mn.id_materia = m.id_materia
                  WHERE mn.id_mnemonica = :id AND mn.id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_mnemonica);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table . " 
                  SET id_materia = :id_materia, 
                      titulo = :titulo, 
                      descricao = :descricao, 
                      itens = :itens
                  WHERE id_mnemonica = :id AND id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);

        $this->titulo = htmlspecialchars(strip_tags($this->titulo));
        $this->descricao = $this->descricao ? htmlspecialchars(strip_tags($this->descricao)) : null;

        if (is_array($this->itens) || is_object($this->itens)) {
            $this->itens = json_encode($this->itens, JSON_UNESCAPED_UNICODE);
        }

        if (!empty($this->id_materia)) {
            $stmt->bindParam(":id_materia", $this->id_materia);
        } else {
            $nullMat = null;
            $stmt->bindParam(":id_materia", $nullMat, PDO::PARAM_NULL);
        }
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":itens", $this->itens);
        $stmt->bindParam(":id", $this->id_mnemonica);
        $stmt->bindParam(":id_usuario", $this->id_usuario);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id_mnemonica = :id AND id_usuario = :id_usuario";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id_mnemonica);
        $stmt->bindParam(":id_usuario", $this->id_usuario);
        return $stmt->execute();
    }
}
?>
