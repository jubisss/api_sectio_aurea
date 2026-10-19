<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class GrupoEstudoController {
    private $db;
    private $grupo;
    private $id_usuario_logado;

    public function __construct($db, $id_usuario_logado = null) {
        $this->db = $db;
        $this->grupo = new GrupoEstudo($db);
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

    private function getUserInfo($id_usuario) {
        $stmt = $this->db->prepare("SELECT nome, email FROM PI_Usuario WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['nome' => '', 'email' => ''];
    }

    private function formatGrupoRow($row) {
        return [
            'id_grupo' => (int)$row['id_grupo'],
            'id' => (int)$row['id_grupo'],
            'id_usuario' => (int)$row['id_usuario'],
            'nome' => $row['nome'],
            'materia' => $row['materia'] ?: 'Geral',
            'codigo' => $row['codigo'],
            'link_meet' => $row['link_meet'] ?: '',
            'linkMeet' => $row['link_meet'] ?: '',
            'membros' => !empty($row['membros']) ? json_decode($row['membros'], true) : [],
            'temas' => !empty($row['temas']) ? json_decode($row['temas'], true) : [],
            'duvidas' => !empty($row['duvidas']) ? json_decode($row['duvidas'], true) : [],
            'reunioes' => !empty($row['reunioes']) ? json_decode($row['reunioes'], true) : [],
            'notas' => $row['notas'] ?: '',
            'flashcards_compartilhados' => !empty($row['flashcards_compartilhados']) ? json_decode($row['flashcards_compartilhados'], true) : [],
            'flashcardsCompartilhados' => !empty($row['flashcards_compartilhados']) ? json_decode($row['flashcards_compartilhados'], true) : [],
            'data_criacao' => $row['data_criacao']
        ];
    }

    public function listar() {
        $id_usuario = $this->getUserId();
        $uInfo = $this->getUserInfo($id_usuario);

        $stmt = $this->grupo->readAllForUser($id_usuario, $uInfo['email'], $uInfo['nome']);
        $grupos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $grupos[] = $this->formatGrupoRow($row);
        }
        echo json_encode($grupos);
    }

    public function buscar($identificador) {
        $this->getUserId();
        if (is_numeric($identificador)) {
            $row = $this->grupo->findById($identificador);
        } else {
            $row = $this->grupo->findByCode(strtoupper($identificador));
        }

        if ($row) {
            echo json_encode($this->formatGrupoRow($row));
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Grupo de estudo não encontrado"]);
        }
    }

    public function criar() {
        $id_usuario = $this->getUserId();
        $uInfo = $this->getUserInfo($id_usuario);
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['nome'])) {
            http_response_code(400);
            echo json_encode(["message" => "Nome do grupo é obrigatório"]);
            return;
        }

        $codigo = !empty($data['codigo']) ? strtoupper($data['codigo']) : strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        $membros = !empty($data['membros']) ? $data['membros'] : [
            ['nome' => $uInfo['nome'] ?: 'Líder', 'email' => $uInfo['email'] ?: '', 'papel' => 'Líder']
        ];

        $this->grupo->id_usuario = $id_usuario;
        $this->grupo->nome = $data['nome'];
        $this->grupo->materia = $data['materia'] ?? 'Geral';
        $this->grupo->codigo = $codigo;
        $this->grupo->link_meet = $data['linkMeet'] ?? $data['link_meet'] ?? '';
        $this->grupo->membros = $membros;
        $this->grupo->temas = $data['temas'] ?? [];
        $this->grupo->duvidas = $data['duvidas'] ?? [];
        $this->grupo->reunioes = $data['reunioes'] ?? [];
        $this->grupo->notas = $data['notas'] ?? '';
        $this->grupo->flashcards_compartilhados = $data['flashcardsCompartilhados'] ?? $data['flashcards_compartilhados'] ?? [];

        if ($this->grupo->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Grupo criado com sucesso",
                "id_grupo" => $this->grupo->id_grupo,
                "codigo" => $codigo
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar grupo"]);
        }
    }

    public function atualizar($id) {
        $this->getUserId();
        $data = json_decode(file_get_contents("php://input"), true);

        $this->grupo->id_grupo = $id;
        $this->grupo->nome = $data['nome'] ?? null;
        $this->grupo->materia = $data['materia'] ?? null;
        $this->grupo->link_meet = $data['linkMeet'] ?? $data['link_meet'] ?? null;
        $this->grupo->membros = $data['membros'] ?? null;
        $this->grupo->temas = $data['temas'] ?? null;
        $this->grupo->duvidas = $data['duvidas'] ?? null;
        $this->grupo->reunioes = $data['reunioes'] ?? null;
        $this->grupo->notas = $data['notas'] ?? null;
        $this->grupo->flashcards_compartilhados = $data['flashcardsCompartilhados'] ?? $data['flashcards_compartilhados'] ?? null;

        if ($this->grupo->update()) {
            echo json_encode(["message" => "Grupo atualizado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao atualizar grupo"]);
        }
    }

    public function deletar($id) {
        $this->getUserId();
        $this->grupo->id_grupo = $id;

        if ($this->grupo->delete()) {
            echo json_encode(["message" => "Grupo deletado com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao deletar grupo"]);
        }
    }
}
?>
