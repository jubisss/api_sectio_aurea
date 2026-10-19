<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class MnemonicaController {
    private $mnemonica;
    private $id_usuario_logado;

    public function __construct($db, $id_usuario_logado = null) {
        $this->mnemonica = new Mnemonica($db);
        $this->id_usuario_logado = $id_usuario_logado;
    }

    private function getUserId() {
        if (!$this->id_usuario_logado) {
            http_response_code(401);
            echo json_encode(["message" => "Usuário não autenticado"]);
            exit();
        }
        return $this->id_usuario_logado;
    }

    public function listar() {
        $id_usuario = $this->getUserId();
        $stmt = $this->mnemonica->read($id_usuario);

        $lista = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Decodifica itens de volta para array se for JSON string
            if (isset($row['itens']) && is_string($row['itens'])) {
                $decoded = json_decode($row['itens'], true);
                $row['itens'] = $decoded !== null ? $decoded : [];
            }
            array_push($lista, $row);
        }

        echo json_encode($lista);
    }

    public function buscar($id) {
        $id_usuario = $this->getUserId();

        $this->mnemonica->id_mnemonica = $id;
        $this->mnemonica->id_usuario = $id_usuario;

        $row = $this->mnemonica->readOne();
        if ($row) {
            if (isset($row['itens']) && is_string($row['itens'])) {
                $decoded = json_decode($row['itens'], true);
                $row['itens'] = $decoded !== null ? $decoded : [];
            }
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Sequência mnemônica não encontrada"]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo) || empty($data->itens)) {
            http_response_code(400);
            echo json_encode(["message" => "Título e itens da sequência são obrigatórios"]);
            return;
        }

        $this->mnemonica->id_usuario = $id_usuario;
        $this->mnemonica->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->mnemonica->titulo = $data->titulo;
        $this->mnemonica->descricao = $data->descricao ?? null;
        $this->mnemonica->itens = $data->itens;

        if ($this->mnemonica->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Sequência mnemônica criada com sucesso",
                "id_mnemonica" => $this->mnemonica->id_mnemonica
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao salvar sequência mnemônica"]);
        }
    }

    public function atualizar($id) {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo) || empty($data->itens)) {
            http_response_code(400);
            echo json_encode(["message" => "Título e itens da sequência são obrigatórios"]);
            return;
        }

        $this->mnemonica->id_mnemonica = $id;
        $this->mnemonica->id_usuario = $id_usuario;
        $this->mnemonica->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->mnemonica->titulo = $data->titulo;
        $this->mnemonica->descricao = $data->descricao ?? null;
        $this->mnemonica->itens = $data->itens;

        if ($this->mnemonica->update()) {
            echo json_encode(["message" => "Sequência mnemônica atualizada com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar sequência mnemônica"]);
        }
    }

    public function deletar($id) {
        $id_usuario = $this->getUserId();

        $this->mnemonica->id_mnemonica = $id;
        $this->mnemonica->id_usuario = $id_usuario;

        if ($this->mnemonica->delete()) {
            echo json_encode(["message" => "Sequência mnemônica deletada com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao deletar sequência mnemônica"]);
        }
    }
}
?>
