<?php
class GrupoEstudo {
    private $conn;
    private $table = "PI_GruposEstudo";

    public $id_grupo;
    public $id_usuario;
    public $nome;
    public $materia;
    public $codigo;
    public $link_meet;
    public $membros;
    public $temas;
    public $duvidas;
    public $reunioes;
    public $notas;
    public $flashcards_compartilhados;
    public $data_criacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    private function encodeJsonField($val) {
        if (is_null($val)) return json_encode([]);
        if (is_array($val) || is_object($val)) {
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        }
        return $val;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . "
                  (id_usuario, nome, materia, codigo, link_meet, membros, temas, duvidas, reunioes, notas, flashcards_compartilhados, data_criacao)
                  VALUES (:id_usuario, :nome, :materia, :codigo, :link_meet, :membros, :temas, :duvidas, :reunioes, :notas, :flashcards_compartilhados, NOW())";

        $stmt = $this->conn->prepare($query);

        $membrosJson = $this->encodeJsonField($this->membros);
        $temasJson = $this->encodeJsonField($this->temas);
        $duvidasJson = $this->encodeJsonField($this->duvidas);
        $reunioesJson = $this->encodeJsonField($this->reunioes);
        $flashcardsJson = $this->encodeJsonField($this->flashcards_compartilhados);

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":materia", $this->materia);
        $stmt->bindParam(":codigo", $this->codigo);
        $stmt->bindParam(":link_meet", $this->link_meet);
        $stmt->bindParam(":membros", $membrosJson);
        $stmt->bindParam(":temas", $temasJson);
        $stmt->bindParam(":duvidas", $duvidasJson);
        $stmt->bindParam(":reunioes", $reunioesJson);
        $stmt->bindParam(":notas", $this->notas);
        $stmt->bindParam(":flashcards_compartilhados", $flashcardsJson);

        if ($stmt->execute()) {
            $this->id_grupo = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readAllForUser($id_usuario, $email_usuario = null, $nome_usuario = null) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_usuario = :id_usuario 
                  OR membros LIKE :id_str ";
        if (!empty($email_usuario)) {
            $query .= " OR membros LIKE :email ";
        }
        if (!empty($nome_usuario)) {
            $query .= " OR membros LIKE :nome ";
        }
        $query .= " ORDER BY id_grupo DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $idSearch = '%"id_usuario":' . (int)$id_usuario . '%';
        $stmt->bindParam(":id_str", $idSearch);
        if (!empty($email_usuario)) {
            $emailSearch = "%" . $email_usuario . "%";
            $stmt->bindParam(":email", $emailSearch);
        }
        if (!empty($nome_usuario)) {
            $nomeSearch = "%" . $nome_usuario . "%";
            $stmt->bindParam(":nome", $nomeSearch);
        }
        $stmt->execute();
        return $stmt;
    }

    public function findByCode($codigo) {
        $query = "SELECT * FROM " . $this->table . " WHERE UPPER(codigo) = UPPER(:codigo) LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":codigo", $codigo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id_grupo) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_grupo = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id_grupo);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update() {
        $query = "UPDATE " . $this->table . " SET 
                  nome = COALESCE(:nome, nome),
                  materia = COALESCE(:materia, materia),
                  link_meet = COALESCE(:link_meet, link_meet),
                  membros = COALESCE(:membros, membros),
                  temas = COALESCE(:temas, temas),
                  duvidas = COALESCE(:duvidas, duvidas),
                  reunioes = COALESCE(:reunioes, reunioes),
                  notas = COALESCE(:notas, notas),
                  flashcards_compartilhados = COALESCE(:flashcards, flashcards_compartilhados)
                  WHERE id_grupo = :id_grupo";

        $stmt = $this->conn->prepare($query);

        $membrosJson = !is_null($this->membros) ? $this->encodeJsonField($this->membros) : null;
        $temasJson = !is_null($this->temas) ? $this->encodeJsonField($this->temas) : null;
        $duvidasJson = !is_null($this->duvidas) ? $this->encodeJsonField($this->duvidas) : null;
        $reunioesJson = !is_null($this->reunioes) ? $this->encodeJsonField($this->reunioes) : null;
        $flashcardsJson = !is_null($this->flashcards_compartilhados) ? $this->encodeJsonField($this->flashcards_compartilhados) : null;

        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":materia", $this->materia);
        $stmt->bindParam(":link_meet", $this->link_meet);
        $stmt->bindParam(":membros", $membrosJson);
        $stmt->bindParam(":temas", $temasJson);
        $stmt->bindParam(":duvidas", $duvidasJson);
        $stmt->bindParam(":reunioes", $reunioesJson);
        $stmt->bindParam(":notas", $this->notas);
        $stmt->bindParam(":flashcards", $flashcardsJson);
        $stmt->bindParam(":id_grupo", $this->id_grupo);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_grupo = :id_grupo";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_grupo", $this->id_grupo);
        return $stmt->execute();
    }
}
?>