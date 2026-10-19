<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class SimuladoController {
    private $simulado;
    private $id_usuario_logado;

    public function __construct($db, $id_usuario_logado = null) {
        $this->simulado = new Simulado($db);
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
        $row['id'] = (int)$row['id_simulado'];
        $row['acertos'] = (int)$row['acertos'];
        $row['erros'] = (int)$row['erros'];
        $row['total'] = (int)$row['total'];
        $row['taxa'] = (float)$row['taxa'];
        $row['tempo_gasto'] = (int)$row['tempo_gasto'];

        // Decodificar gabarito
        $gabarito = [];
        if (!empty($row['gabarito'])) {
            if (is_string($row['gabarito'])) {
                $decoded = json_decode($row['gabarito'], true);
                $gabarito = is_array($decoded) ? $decoded : [];
            } elseif (is_array($row['gabarito'])) {
                $gabarito = $row['gabarito'];
            }
        }
        $row['gabarito'] = $gabarito;

        // Decodificar respostas do usuário
        $respostas = [];
        if (!empty($row['respostas_usuario'])) {
            if (is_string($row['respostas_usuario'])) {
                $decoded = json_decode($row['respostas_usuario'], true);
                $respostas = is_array($decoded) ? $decoded : [];
            } elseif (is_array($row['respostas_usuario'])) {
                $respostas = $row['respostas_usuario'];
            }
        }
        $row['respostas_usuario'] = $respostas;

        return $row;
    }

    public function listar() {
        $id_usuario = $this->getUserId();
        $materia = $_GET['materia'] ?? null;
        $stmt = $this->simulado->read($id_usuario, $materia);
        $simulados = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $simulados[] = $this->formatRow($row);
        }

        echo json_encode($simulados);
    }

    public function buscar($id) {
        $id_usuario = $this->getUserId();
        $row = $this->simulado->readOne($id, $id_usuario);

        if ($row) {
            echo json_encode($this->formatRow($row));
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Simulado não encontrado"]);
        }
    }

    public function upload() {
        $id_usuario = $this->getUserId();

        if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(["message" => "Nenhum arquivo enviado ou erro no upload"]);
            return;
        }

        $file = $_FILES['arquivo'];
        $nomeOriginal = $file['name'];
        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        $extensoesPermitidas = ['pdf', 'png', 'jpg', 'jpeg', 'doc', 'docx'];
        if (!in_array($ext, $extensoesPermitidas)) {
            http_response_code(400);
            echo json_encode(["message" => "Formato de arquivo não suportado. Envie arquivos PDF, imagens ou documentos."]);
            return;
        }

        $uploadDir = __DIR__ . '/../uploads/simulados/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $novoNome = "simulado_" . $id_usuario . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $destino = $uploadDir . $novoNome;

        if (move_uploaded_file($file['tmp_name'], $destino)) {
            // URL acessível
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $baseUrl = "$protocol://$host/api_sectio_aurea";
            $arquivoUrl = "$baseUrl/uploads/simulados/$novoNome";

            echo json_encode([
                "message" => "Arquivo enviado com sucesso",
                "arquivo_url" => $arquivoUrl,
                "arquivo_nome" => $nomeOriginal
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Falha ao mover arquivo salvo no servidor"]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (empty($data->titulo)) {
            http_response_code(400);
            echo json_encode(["message" => "O título da prova/simulado é obrigatório"]);
            return;
        }

        $this->simulado->id_usuario = $id_usuario;
        $this->simulado->titulo = $data->titulo;
        $this->simulado->materia = $data->materia ?? 'Geral';
        $this->simulado->data = !empty($data->data) ? date('Y-m-d H:i:s', strtotime($data->data)) : date('Y-m-d H:i:s');
        $this->simulado->arquivo_url = $data->arquivo_url ?? ($data->arquivoUrl ?? '');
        $this->simulado->arquivo_nome = $data->arquivo_nome ?? ($data->arquivoNome ?? '');

        // Gabarito e Respostas
        $gabarito = $data->gabarito ?? [];
        $this->simulado->gabarito = is_string($gabarito) ? $gabarito : json_encode($gabarito, JSON_UNESCAPED_UNICODE);

        $respostas = $data->respostas_usuario ?? ($data->respostas ?? []);
        $this->simulado->respostas_usuario = is_string($respostas) ? $respostas : json_encode($respostas, JSON_UNESCAPED_UNICODE);

        $this->simulado->acertos = $data->acertos ?? 0;
        $this->simulado->erros = $data->erros ?? 0;
        $this->simulado->total = $data->total ?? ($this->simulado->acertos + $this->simulado->erros);
        
        if ($this->simulado->total > 0) {
            $this->simulado->taxa = $data->taxa ?? round(($this->simulado->acertos / $this->simulado->total) * 100, 2);
        } else {
            $this->simulado->taxa = $data->taxa ?? 0;
        }

        $this->simulado->tempo_gasto = $data->tempo_gasto ?? ($data->tempoGasto ?? 0);
        $this->simulado->anotacoes = $data->anotacoes ?? '';

        if ($this->simulado->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Simulado cadastrado com sucesso",
                "id_simulado" => (int)$this->simulado->id_simulado,
                "id" => (int)$this->simulado->id_simulado
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao salvar simulado"]);
        }
    }

    public function atualizar($id) {
        $id_usuario = $this->getUserId();
        $data = json_decode(file_get_contents("php://input"));

        if (isset($data->titulo)) $this->simulado->titulo = $data->titulo;
        if (isset($data->materia)) $this->simulado->materia = $data->materia;
        if (isset($data->arquivo_url) || isset($data->arquivoUrl)) {
            $this->simulado->arquivo_url = $data->arquivo_url ?? $data->arquivoUrl;
        }
        if (isset($data->arquivo_nome) || isset($data->arquivoNome)) {
            $this->simulado->arquivo_nome = $data->arquivo_nome ?? $data->arquivoNome;
        }
        if (isset($data->gabarito)) {
            $this->simulado->gabarito = is_string($data->gabarito) ? $data->gabarito : json_encode($data->gabarito, JSON_UNESCAPED_UNICODE);
        }
        if (isset($data->respostas_usuario) || isset($data->respostas)) {
            $resp = $data->respostas_usuario ?? $data->respostas;
            $this->simulado->respostas_usuario = is_string($resp) ? $resp : json_encode($resp, JSON_UNESCAPED_UNICODE);
        }
        if (isset($data->acertos)) $this->simulado->acertos = (int)$data->acertos;
        if (isset($data->erros)) $this->simulado->erros = (int)$data->erros;
        if (isset($data->total)) $this->simulado->total = (int)$data->total;
        if (isset($data->taxa)) $this->simulado->taxa = (float)$data->taxa;
        if (isset($data->tempo_gasto) || isset($data->tempoGasto)) {
            $this->simulado->tempo_gasto = (int)($data->tempo_gasto ?? $data->tempoGasto);
        }
        if (isset($data->anotacoes)) $this->simulado->anotacoes = $data->anotacoes;

        if ($this->simulado->update($id, $id_usuario)) {
            echo json_encode(["message" => "Simulado atualizado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar simulado"]);
        }
    }

    public function deletar($id) {
        $id_usuario = $this->getUserId();

        // Buscar arquivo para apagar se existir localmente
        $item = $this->simulado->readOne($id, $id_usuario);
        if ($item && !empty($item['arquivo_url'])) {
            $filename = basename($item['arquivo_url']);
            $filepath = __DIR__ . '/../uploads/simulados/' . $filename;
            if (file_exists($filepath)) {
                @unlink($filepath);
            }
        }

        if ($this->simulado->delete($id, $id_usuario)) {
            echo json_encode(["message" => "Simulado excluído com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao excluir simulado"]);
        }
    }
}
?>
