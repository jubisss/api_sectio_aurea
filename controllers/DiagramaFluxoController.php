<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class DiagramaFluxoController {
    private $diagramaFluxo;
    private $id_usuario_logado;

    public function __construct($db, $id_usuario_logado = null) {
        $this->diagramaFluxo = new DiagramaFluxo($db);
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
        $stmt = $this->diagramaFluxo->read($id_usuario);

        $diagramas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['nos']) && is_string($row['nos'])) {
                $decoded = json_decode($row['nos'], true);
                $row['nos'] = $decoded !== null ? $decoded : [];
            }
            if (isset($row['conexoes']) && is_string($row['conexoes'])) {
                $decoded = json_decode($row['conexoes'], true);
                $row['conexoes'] = $decoded !== null ? $decoded : [];
            }
            array_push($diagramas, $row);
        }

        echo json_encode($diagramas);
    }

    public function buscar($id) {
        $id_usuario = $this->getUserId();

        $this->diagramaFluxo->id_diagrama = $id;
        $this->diagramaFluxo->id_usuario = $id_usuario;

        $row = $this->diagramaFluxo->readOne();
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
            echo json_encode(["message" => "Diagrama de fluxo não encontrado"]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo)) {
            http_response_code(400);
            echo json_encode(["message" => "O título do diagrama de fluxo é obrigatório"]);
            return;
        }

        $this->diagramaFluxo->id_usuario = $id_usuario;
        $this->diagramaFluxo->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->diagramaFluxo->titulo = $data->titulo;
        $this->diagramaFluxo->nos = $data->nos ?? [];
        $this->diagramaFluxo->conexoes = $data->conexoes ?? [];

        if ($this->diagramaFluxo->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Diagrama de fluxo criado com sucesso",
                "id_diagrama" => $this->diagramaFluxo->id_diagrama
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar diagrama de fluxo"]);
        }
    }

    public function atualizar($id) {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo)) {
            http_response_code(400);
            echo json_encode(["message" => "O título do diagrama de fluxo é obrigatório"]);
            return;
        }

        $this->diagramaFluxo->id_diagrama = $id;
        $this->diagramaFluxo->id_usuario = $id_usuario;
        $this->diagramaFluxo->id_materia = !empty($data->id_materia) ? $data->id_materia : null;
        $this->diagramaFluxo->titulo = $data->titulo;
        $this->diagramaFluxo->nos = $data->nos ?? [];
        $this->diagramaFluxo->conexoes = $data->conexoes ?? [];

        if ($this->diagramaFluxo->update()) {
            echo json_encode(["message" => "Diagrama de fluxo atualizado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar diagrama de fluxo"]);
        }
    }

    public function deletar($id) {
        $id_usuario = $this->getUserId();

        $this->diagramaFluxo->id_diagrama = $id;
        $this->diagramaFluxo->id_usuario = $id_usuario;

        if ($this->diagramaFluxo->delete()) {
            echo json_encode(["message" => "Diagrama de fluxo excluído com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao excluir diagrama de fluxo"]);
        }
    }
}
?>
