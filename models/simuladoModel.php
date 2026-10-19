<?php
class Simulado {
    private $conn;
    private $table = "PI_HistoricoSimulados";

    public $id_simulado;
    public $id_usuario;
    public $data;
    public $materia;
    public $acertos;
    public $erros;
    public $taxa;
    public $total;
    public $titulo;
    public $arquivo_url;
    public $arquivo_nome;
    public $gabarito;
    public $respostas_usuario;
    public $tempo_gasto;
    public $anotacoes;

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
        if (!$this->usuarioExiste($this->id_usuario)) {
            return false;
        }

        $query = "INSERT INTO " . $this->table . " 
                  (id_usuario, data, materia, acertos, erros, taxa, total, titulo, arquivo_url, arquivo_nome, gabarito, respostas_usuario, tempo_gasto, anotacoes) 
                  VALUES (:id_usuario, :data, :materia, :acertos, :erros, :taxa, :total, :titulo, :arquivo_url, :arquivo_nome, :gabarito, :respostas_usuario, :tempo_gasto, :anotacoes)";

        $stmt = $this->conn->prepare($query);

        $this->titulo = htmlspecialchars(strip_tags($this->titulo ?? 'Simulado Prático'));
        $this->materia = htmlspecialchars(strip_tags($this->materia ?? 'Geral'));
        $this->data = $this->data ?? date('Y-m-d H:i:s');
        $this->acertos = (int)($this->acertos ?? 0);
        $this->erros = (int)($this->erros ?? 0);
        $this->total = (int)($this->total ?? ($this->acertos + $this->erros));
        $this->taxa = (float)($this->taxa ?? ($this->total > 0 ? round(($this->acertos / $this->total) * 100, 2) : 0));
        $this->tempo_gasto = (int)($this->tempo_gasto ?? 0);

        $stmt->bindParam(":id_usuario", $this->id_usuario);
        $stmt->bindParam(":data", $this->data);
        $stmt->bindParam(":materia", $this->materia);
        $stmt->bindParam(":acertos", $this->acertos);
        $stmt->bindParam(":erros", $this->erros);
        $stmt->bindParam(":taxa", $this->taxa);
        $stmt->bindParam(":total", $this->total);
        $stmt->bindParam(":titulo", $this->titulo);
        $stmt->bindParam(":arquivo_url", $this->arquivo_url);
        $stmt->bindParam(":arquivo_nome", $this->arquivo_nome);
        $stmt->bindParam(":gabarito", $this->gabarito);
        $stmt->bindParam(":respostas_usuario", $this->respostas_usuario);
        $stmt->bindParam(":tempo_gasto", $this->tempo_gasto);
        $stmt->bindParam(":anotacoes", $this->anotacoes);

        if ($stmt->execute()) {
            $this->id_simulado = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function read($id_usuario, $materia = null) {
        $query = "SELECT * FROM " . $this->table . " WHERE id_usuario = :id_usuario";
        if (!empty($materia) && $materia !== 'todas') {
            $query .= " AND materia = :materia";
        }
        $query .= " ORDER BY data DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_usuario", $id_usuario);
        if (!empty($materia) && $materia !== 'todas') {
            $stmt->bindParam(":materia", $materia);
        }
        $stmt->execute();
        return $stmt;
    }

    public function readOne($id_simulado, $id_usuario) {
        $query = "SELECT * FROM " . $this->table . " 
                  WHERE id_simulado = :id_simulado AND id_usuario = :id_usuario 
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_simulado", $id_simulado);
        $stmt->bindParam(":id_usuario", $id_usuario);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id_simulado, $id_usuario) {
        $fields = [];
        $params = [
            ":id_simulado" => $id_simulado,
            ":id_usuario" => $id_usuario
        ];

        if ($this->titulo !== null) {
            $fields[] = "titulo = :titulo";
            $params[":titulo"] = htmlspecialchars(strip_tags($this->titulo));
        }
        if ($this->materia !== null) {
            $fields[] = "materia = :materia";
            $params[":materia"] = htmlspecialchars(strip_tags($this->materia));
        }
        if ($this->acertos !== null) {
            $fields[] = "acertos = :acertos";
            $params[":acertos"] = (int)$this->acertos;
        }
        if ($this->erros !== null) {
            $fields[] = "erros = :erros";
            $params[":erros"] = (int)$this->erros;
        }
        if ($this->total !== null) {
            $fields[] = "total = :total";
            $params[":total"] = (int)$this->total;
        }
        if ($this->taxa !== null) {
            $fields[] = "taxa = :taxa";
            $params[":taxa"] = (float)$this->taxa;
        }
        if ($this->arquivo_url !== null) {
            $fields[] = "arquivo_url = :arquivo_url";
            $params[":arquivo_url"] = $this->arquivo_url;
        }
        if ($this->arquivo_nome !== null) {
            $fields[] = "arquivo_nome = :arquivo_nome";
            $params[":arquivo_nome"] = $this->arquivo_nome;
        }
        if ($this->gabarito !== null) {
            $fields[] = "gabarito = :gabarito";
            $params[":gabarito"] = $this->gabarito;
        }
        if ($this->respostas_usuario !== null) {
            $fields[] = "respostas_usuario = :respostas_usuario";
            $params[":respostas_usuario"] = $this->respostas_usuario;
        }
        if ($this->tempo_gasto !== null) {
            $fields[] = "tempo_gasto = :tempo_gasto";
            $params[":tempo_gasto"] = (int)$this->tempo_gasto;
        }
        if ($this->anotacoes !== null) {
            $fields[] = "anotacoes = :anotacoes";
            $params[":anotacoes"] = $this->anotacoes;
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE " . $this->table . " SET " . implode(", ", $fields) . " 
                  WHERE id_simulado = :id_simulado AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute($params);
    }

    public function delete($id_simulado, $id_usuario) {
        $query = "DELETE FROM " . $this->table . " 
                  WHERE id_simulado = :id_simulado AND id_usuario = :id_usuario";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id_simulado", $id_simulado);
        $stmt->bindParam(":id_usuario", $id_usuario);
        return $stmt->execute();
    }
}
?>
