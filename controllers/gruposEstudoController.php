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
        return (int)$this->id_usuario_logado;
    }

    private function getUserInfo($id_usuario) {
        $stmt = $this->db->prepare("SELECT id_usuario, nome, email FROM PI_Usuario WHERE id_usuario = ?");
        $stmt->execute([$id_usuario]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['id_usuario' => (int)$id_usuario, 'nome' => '', 'email' => ''];
    }

    private function cleanMemberName($nome) {
        if (!is_string($nome)) return '';
        $limpo = preg_replace('/\s*\((?:Você|Voce|Criador)\)\s*/iu', '', $nome);
        return trim($limpo);
    }

    private function normalizeMembers($membros, $id_criador = null) {
        if (!is_array($membros)) return [];
        $criadorInfo = null;
        $normalizados = [];

        foreach ($membros as $idx => $m) {
            if (!is_array($m)) continue;
            $nomeLimpo = $this->cleanMemberName($m['nome'] ?? '');
            $emailLimpo = trim($m['email'] ?? '');
            $papel = $m['papel'] ?? ($idx === 0 ? 'Líder' : 'Membro');
            $idMembro = isset($m['id_usuario']) ? (int)$m['id_usuario'] : null;

            if ($idx === 0 && $id_criador && (empty($nomeLimpo) || mb_strtolower($nomeLimpo) === 'você' || mb_strtolower($nomeLimpo) === 'estudante' || empty($emailLimpo))) {
                $criadorInfo = $criadorInfo ?: $this->getUserInfo($id_criador);
                if (empty($nomeLimpo) || mb_strtolower($nomeLimpo) === 'você' || mb_strtolower($nomeLimpo) === 'estudante') {
                    $nomeLimpo = $criadorInfo['nome'] ?: 'Líder';
                }
                if (empty($emailLimpo)) {
                    $emailLimpo = $criadorInfo['email'] ?: '';
                }
                $idMembro = (int)$id_criador;
            } elseif ($idx === 0 && $id_criador && !$idMembro) {
                $idMembro = (int)$id_criador;
            }

            $item = [
                'nome' => $nomeLimpo ?: 'Participante',
                'email' => $emailLimpo,
                'papel' => $papel
            ];
            if ($idMembro) {
                $item['id_usuario'] = $idMembro;
            }
            $normalizados[] = $item;
        }

        return $normalizados;
    }

    private function isUserInGroup($rowFormatted, $id_usuario, $email_usuario, $nome_usuario) {
        if ((int)($rowFormatted['id_usuario'] ?? 0) === (int)$id_usuario) {
            return true;
        }
        $emailTarget = mb_strtolower(trim($email_usuario ?? ''));
        $nomeTarget = mb_strtolower($this->cleanMemberName($nome_usuario ?? ''));

        foreach (($rowFormatted['membros'] ?? []) as $m) {
            if (!empty($m['id_usuario']) && (int)$m['id_usuario'] === (int)$id_usuario) {
                return true;
            }
            $mEmail = mb_strtolower(trim($m['email'] ?? ''));
            if (!empty($emailTarget) && !empty($mEmail) && $mEmail === $emailTarget) {
                return true;
            }
            $mNome = mb_strtolower($this->cleanMemberName($m['nome'] ?? ''));
            if (!empty($nomeTarget) && !empty($mNome) && $mNome === $nomeTarget) {
                return true;
            }
        }
        return false;
    }

    private function formatGrupoRow($row) {
        $idCriador = (int)$row['id_usuario'];
        $rawMembros = !empty($row['membros']) ? json_decode($row['membros'], true) : [];
        $membros = $this->normalizeMembers($rawMembros, $idCriador);
        $flashcards = !empty($row['flashcards_compartilhados']) ? json_decode($row['flashcards_compartilhados'], true) : [];
        if (!is_array($flashcards)) $flashcards = [];

        return [
            'id_grupo' => (int)$row['id_grupo'],
            'id' => (int)$row['id_grupo'],
            'id_usuario' => $idCriador,
            'nome' => $row['nome'],
            'materia' => $row['materia'] ?: 'Geral',
            'codigo' => $row['codigo'],
            'link_meet' => $row['link_meet'] ?: '',
            'linkMeet' => $row['link_meet'] ?: '',
            'membros' => $membros,
            'temas' => !empty($row['temas']) ? (json_decode($row['temas'], true) ?: []) : [],
            'duvidas' => !empty($row['duvidas']) ? (json_decode($row['duvidas'], true) ?: []) : [],
            'reunioes' => !empty($row['reunioes']) ? (json_decode($row['reunioes'], true) ?: []) : [],
            'notas' => $row['notas'] ?: '',
            'flashcards_compartilhados' => $flashcards,
            'flashcardsCompartilhados' => $flashcards,
            'data_criacao' => $row['data_criacao']
        ];
    }

    public function listar() {
        $id_usuario = $this->getUserId();
        $uInfo = $this->getUserInfo($id_usuario);

        $stmt = $this->grupo->readAllForUser($id_usuario, $uInfo['email'], $uInfo['nome']);
        $grupos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $formatted = $this->formatGrupoRow($row);
            if ($this->isUserInGroup($formatted, $id_usuario, $uInfo['email'], $uInfo['nome'])) {
                $grupos[] = $formatted;
            }
        }
        echo json_encode($grupos);
    }

    public function buscar($identificador) {
        $this->getUserId();
        if (is_numeric($identificador)) {
            $row = $this->grupo->findById($identificador);
        } else {
            $row = $this->grupo->findByCode(strtoupper(trim($identificador)));
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

        if (empty($data['nome']) || !trim($data['nome'])) {
            http_response_code(400);
            echo json_encode(["message" => "Nome do grupo é obrigatório"]);
            return;
        }

        $codigo = !empty($data['codigo']) ? strtoupper(trim($data['codigo'])) : strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));

        $liderPadrao = [
            'id_usuario' => (int)$id_usuario,
            'nome' => $uInfo['nome'] ?: 'Líder',
            'email' => $uInfo['email'] ?: '',
            'papel' => 'Líder'
        ];

        if (!empty($data['membros']) && is_array($data['membros'])) {
            $membros = $this->normalizeMembers($data['membros'], $id_usuario);
            $membros[0] = $liderPadrao;
        } else {
            $membros = [$liderPadrao];
        }

        $this->grupo->id_usuario = $id_usuario;
        $this->grupo->nome = trim($data['nome']);
        $this->grupo->materia = !empty($data['materia']) ? trim($data['materia']) : 'Geral';
        $this->grupo->codigo = $codigo;
        $this->grupo->link_meet = $data['linkMeet'] ?? $data['link_meet'] ?? '';
        $this->grupo->membros = $membros;
        $this->grupo->temas = $data['temas'] ?? [];
        $this->grupo->duvidas = $data['duvidas'] ?? [];
        $this->grupo->reunioes = $data['reunioes'] ?? [];
        $this->grupo->notas = $data['notas'] ?? '';
        $this->grupo->flashcards_compartilhados = $data['flashcardsCompartilhados'] ?? $data['flashcards_compartilhados'] ?? [];

        if ($this->grupo->create()) {
            $rowCriado = $this->grupo->findById($this->grupo->id_grupo);
            http_response_code(201);
            echo json_encode([
                "message" => "Grupo criado com sucesso",
                "id_grupo" => (int)$this->grupo->id_grupo,
                "codigo" => $codigo,
                "grupo" => $rowCriado ? $this->formatGrupoRow($rowCriado) : null
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar grupo"]);
        }
    }

    public function entrar() {
        $id_usuario = $this->getUserId();
        $uInfo = $this->getUserInfo($id_usuario);
        $data = json_decode(file_get_contents("php://input"), true);

        $codigo = strtoupper(trim($data['codigo'] ?? ''));
        if (empty($codigo)) {
            http_response_code(400);
            echo json_encode(["message" => "Código do grupo é obrigatório"]);
            return;
        }

        $row = $this->grupo->findByCode($codigo);
        if (!$row) {
            http_response_code(404);
            echo json_encode(["message" => "Grupo de estudo não encontrado. Verifique o código informado."]);
            return;
        }

        $grupoFormatado = $this->formatGrupoRow($row);
        $jaEhMembro = $this->isUserInGroup($grupoFormatado, $id_usuario, $uInfo['email'], $uInfo['nome']);

        if ($jaEhMembro) {
            echo json_encode([
                "message" => "Você já faz parte deste grupo!",
                "ja_membro" => true,
                "grupo" => $grupoFormatado
            ]);
            return;
        }

        $novosMembros = $grupoFormatado['membros'];
        $novosMembros[] = [
            'id_usuario' => (int)$id_usuario,
            'nome' => $uInfo['nome'] ?: 'Estudante',
            'email' => $uInfo['email'] ?: '',
            'papel' => 'Membro'
        ];

        $this->grupo->id_grupo = $grupoFormatado['id_grupo'];
        $this->grupo->nome = null;
        $this->grupo->materia = null;
        $this->grupo->link_meet = null;
        $this->grupo->membros = $novosMembros;
        $this->grupo->temas = null;
        $this->grupo->duvidas = null;
        $this->grupo->reunioes = null;
        $this->grupo->notas = null;
        $this->grupo->flashcards_compartilhados = null;

        if ($this->grupo->update()) {
            $rowAtualizado = $this->grupo->findById($grupoFormatado['id_grupo']);
            echo json_encode([
                "message" => "Você entrou no grupo com sucesso!",
                "ja_membro" => false,
                "grupo" => $rowAtualizado ? $this->formatGrupoRow($rowAtualizado) : $grupoFormatado
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao entrar no grupo"]);
        }
    }

    public function atualizar($id) {
        $this->getUserId();
        $data = json_decode(file_get_contents("php://input"), true);

        $rowExistente = $this->grupo->findById($id);
        $idCriador = $rowExistente ? (int)$rowExistente['id_usuario'] : null;

        $this->grupo->id_grupo = $id;
        $this->grupo->nome = $data['nome'] ?? null;
        $this->grupo->materia = $data['materia'] ?? null;
        $this->grupo->link_meet = $data['linkMeet'] ?? $data['link_meet'] ?? null;
        $this->grupo->membros = isset($data['membros']) ? $this->normalizeMembers($data['membros'], $idCriador) : null;
        $this->grupo->temas = $data['temas'] ?? null;
        $this->grupo->duvidas = $data['duvidas'] ?? null;
        $this->grupo->reunioes = $data['reunioes'] ?? null;
        $this->grupo->notas = $data['notas'] ?? null;
        $this->grupo->flashcards_compartilhados = $data['flashcardsCompartilhados'] ?? $data['flashcards_compartilhados'] ?? null;

        if ($this->grupo->update()) {
            $rowAtualizado = $this->grupo->findById($id);
            echo json_encode([
                "message" => "Grupo atualizado com sucesso",
                "grupo" => $rowAtualizado ? $this->formatGrupoRow($rowAtualizado) : null
            ]);
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