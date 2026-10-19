<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class MapaMentalController {
    private $mapaMental;
    private $id_usuario_logado;

    public function __construct($db, $id_usuario_logado = null) {
        $this->mapaMental = new MapaMental($db);
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
        $stmt = $this->mapaMental->read($id_usuario);

        $mapas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['nos']) && is_string($row['nos'])) {
                $decoded = json_decode($row['nos'], true);
                $row['nos'] = $decoded !== null ? $decoded : [];
            }
            if (isset($row['conexoes']) && is_string($row['conexoes'])) {
                $decoded = json_decode($row['conexoes'], true);
                $row['conexoes'] = $decoded !== null ? $decoded : [];
            }
            array_push($mapas, $row);
        }

        echo json_encode($mapas);
    }

    public function buscar($id) {
        $id_usuario = $this->getUserId();

        $this->mapaMental->id_mapa = $id;
        $this->mapaMental->id_usuario = $id_usuario;

        $row = $this->mapaMental->readOne();
        if ($row) {
            if (isset($row['nos']) && is_string($row['nos'])) {
                $decoded = json_decode($row['nos'], true);
                $row['nos'] = $decoded !== null ? $decoded : [];
            }
            if (isset($row['conexoes']) && is_string($row['conexoes'])) {
                $decoded = json_decode($row['conexoes'], true);
                $row['conexoes'] = $decoded !== null ? $decoded : [];
            }
            echo json_encode($row);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Mapa mental não encontrado"]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo)) {
            http_response_code(400);
            echo json_encode(["message" => "O título do mapa mental é obrigatório"]);
            return;
        }

        $this->mapaMental->id_usuario = $id_usuario;
        $this->mapaMental->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->mapaMental->titulo = $data->titulo;
        $this->mapaMental->nos = $data->nos ?? [];
        $this->mapaMental->conexoes = $data->conexoes ?? [];

        if ($this->mapaMental->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Mapa mental criado com sucesso",
                "id_mapa" => $this->mapaMental->id_mapa
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar mapa mental"]);
        }
    }

    public function atualizar($id) {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo)) {
            http_response_code(400);
            echo json_encode(["message" => "O título do mapa mental é obrigatório"]);
            return;
        }

        $this->mapaMental->id_mapa = $id;
        $this->mapaMental->id_usuario = $id_usuario;
        $this->mapaMental->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->mapaMental->titulo = $data->titulo;
        $this->mapaMental->nos = $data->nos ?? [];
        $this->mapaMental->conexoes = $data->conexoes ?? [];

        if ($this->mapaMental->update()) {
            echo json_encode(["message" => "Mapa mental atualizado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar mapa mental"]);
        }
    }

    public function deletar($id) {
        $id_usuario = $this->getUserId();

        $this->mapaMental->id_mapa = $id;
        $this->mapaMental->id_usuario = $id_usuario;

        if ($this->mapaMental->delete()) {
            echo json_encode(["message" => "Mapa mental excluído com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao excluir mapa mental"]);
        }
    }
}
?>
