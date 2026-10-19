<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class LeituraController {
    private $leitura;
    private $id_usuario_logado;

    public function __construct($db, $id_usuario_logado = null) {
        $this->leitura = new Leitura($db);
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

    private function formatRow($row) {
        $row['id'] = (int)$row['id_leitura'];
        
        // Decodificar trechos/paginas
        $trechos = [];
        if (!empty($row['paginas'])) {
            if (is_string($row['paginas'])) {
                $decoded = json_decode($row['paginas'], true);
                $trechos = is_array($decoded) ? $decoded : [];
            } elseif (is_array($row['paginas'])) {
                $trechos = $row['paginas'];
            }
        }
        $row['trechos'] = $trechos;
        $row['totalTrechos'] = count($trechos);

        // Decodificar anotações
        $anotacoes = [];
        if (!empty($row['anotacoes'])) {
            if (is_string($row['anotacoes'])) {
                $decoded = json_decode($row['anotacoes'], true);
                $anotacoes = is_array($decoded) ? $decoded : [];
            } elseif (is_array($row['anotacoes'])) {
                $anotacoes = $row['anotacoes'];
            }
        }
        $row['anotacoes'] = $anotacoes;
        $row['textoCompleto'] = $row['texto_completo'] ?? '';

        return $row;
    }

    public function listar() {
        $id_usuario = $this->getUserId();
        $stmt = $this->leitura->read($id_usuario);
        $leituras = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $leituras[] = $this->formatRow($row);
        }
        
        echo json_encode($leituras);
    }

    public function buscar($id) {
        $id_usuario = $this->getUserId();
        $row = $this->leitura->readOne($id, $id_usuario);
        
        if ($row) {
            echo json_encode($this->formatRow($row));
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Leitura não encontrada"]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo)) {
            http_response_code(400);
            echo json_encode(["message" => "O título da leitura é obrigatório"]);
            return;
        }

        $this->leitura->id_usuario = $id_usuario;
        $this->leitura->titulo = $data->titulo;
        
        if (!empty($data->data)) {
            $this->leitura->data = date('Y-m-d H:i:s', strtotime($data->data));
        } else {
            $this->leitura->data = date('Y-m-d H:i:s');
        }

        // Trechos / Páginas
        $trechos = $data->trechos ?? ($data->paginas ?? []);
        $this->leitura->paginas = is_string($trechos) ? $trechos : json_encode($trechos, JSON_UNESCAPED_UNICODE);

        // Anotações
        $anotacoes = $data->anotacoes ?? [];
        $this->leitura->anotacoes = is_string($anotacoes) ? $anotacoes : json_encode($anotacoes, JSON_UNESCAPED_UNICODE);

        // Resumo & Texto Completo
        $this->leitura->resumo = $data->resumo ?? '';
        $this->leitura->texto_completo = $data->textoCompleto ?? ($data->texto_completo ?? '');

        if ($this->leitura->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Leitura salva com sucesso",
                "id_leitura" => (int)$this->leitura->id_leitura,
                "id" => (int)$this->leitura->id_leitura
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao salvar leitura"]);
        }
    }

    public function atualizar($id) {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (isset($data->titulo)) {
            $this->leitura->titulo = $data->titulo;
        }
        if (isset($data->trechos) || isset($data->paginas)) {
            $trechos = $data->trechos ?? $data->paginas;
            $this->leitura->paginas = is_string($trechos) ? $trechos : json_encode($trechos, JSON_UNESCAPED_UNICODE);
        }
        if (isset($data->anotacoes)) {
            $this->leitura->anotacoes = is_string($data->anotacoes) ? $data->anotacoes : json_encode($data->anotacoes, JSON_UNESCAPED_UNICODE);
        }
        if (isset($data->resumo)) {
            $this->leitura->resumo = $data->resumo;
        }
        if (isset($data->textoCompleto) || isset($data->texto_completo)) {
            $this->leitura->texto_completo = $data->textoCompleto ?? $data->texto_completo;
        }

        if ($this->leitura->update($id, $id_usuario)) {
            echo json_encode(["message" => "Leitura atualizada com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar leitura"]);
        }
    }

    public function deletar($id) {
        $id_usuario = $this->getUserId();

        if ($this->leitura->delete($id, $id_usuario)) {
            echo json_encode(["message" => "Leitura excluída com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao excluir leitura"]);
        }
    }
}
?>
