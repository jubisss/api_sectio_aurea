<?php
// Timezone padrão do Brasil
date_default_timezone_set('America/Sao_Paulo');

// Ativa erros para a gente ver o que tá acontecendo
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carregar todos os arquivos
require_once 'config/database.php';
require_once 'models/usuarioModel.php';
require_once 'models/materiaModel.php';
require_once 'models/blocoAnotacoesModel.php';
require_once 'models/cronogramaModel.php';
require_once 'models/cronometroModel.php';
require_once 'models/eventosModel.php';
require_once 'models/flashcardModel.php';
require_once 'models/inteligenciasModel.php';
require_once 'models/tarefasModel.php';
require_once 'models/adminModel.php';
require_once 'models/cornellModel.php';
require_once 'models/mnemonicaModel.php';
require_once 'models/MapaMental.php';
require_once 'models/DiagramaFluxo.php';
require_once 'models/Feynman.php';
require_once 'models/gruposEstudoModel.php';
require_once 'models/leituraModel.php';
require_once 'models/simuladoModel.php';
require_once 'models/BibliotecaVideos.php';

require_once 'controllers/cornellController.php';
require_once 'controllers/mnemonicaController.php';
require_once 'controllers/mapaMentalController.php';
require_once 'controllers/diagramaFluxoController.php';
require_once 'controllers/feynmanController.php';
require_once 'controllers/gruposEstudoController.php';
require_once 'controllers/leituraController.php';
require_once 'controllers/simuladoController.php';
require_once 'controllers/BibliotecaVideosController.php';
require_once 'controllers/authController.php';
require_once 'controllers/usuarioController.php';
require_once 'controllers/materiaController.php';
require_once 'controllers/blocoAnotacoesController.php';
require_once 'controllers/cronogramaController.php';
require_once 'controllers/cronometroController.php';
require_once 'controllers/eventosController.php';
require_once 'controllers/flashcardController.php';
require_once 'controllers/tarefasController.php';
require_once 'controllers/adminController.php';

require_once 'middleware/AuthMiddleware.php';
// O arquivo routes/api.php foi IGNORADO de propósito para não dar conflito
// require_once 'routes/api.php';

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// =======================================================
// 👇 ROTAS DO DESKTOP (ADM) 👇
// =======================================================

// LOGIN ADM
if (strpos($request_uri, 'adm/login') !== false && $method == 'POST') {
    $adminController = new AdminController($db);
    $adminController->login();
    exit();
}

// CADASTRAR ADM
if (strpos($request_uri, 'adm/cadastrar') !== false && $method == 'POST') {
    $adminController = new AdminController($db);
    $adminController->cadastrar();
    exit();
}

// BUSCAR ADM (Para editar perfil)
if (strpos($request_uri, 'adm/buscar') !== false && $method == 'POST') {
    $adminController = new AdminController($db);
    $adminController->buscarAdm();
    exit();
}

// EDITAR ADM
if (strpos($request_uri, 'adm/editar') !== false && $method == 'POST') {
    $adminController = new AdminController($db);
    $adminController->editar();
    exit();
}

// ESTATÍSTICAS (Gráfico)
if (strpos($request_uri, 'adm/estatisticas') !== false && $method == 'GET') {
    $adminController = new AdminController($db);
    $adminController->estatisticas();
    exit();
}

// LISTAR USUÁRIOS
if (strpos($request_uri, 'adm/usuarios') !== false && $method == 'GET') {
    $adminController = new AdminController($db);
    $adminController->listarUsuarios();
    exit();
}

// ALTERAR STATUS DO USUÁRIO (Ativar/Desativar)
if (strpos($request_uri, 'adm/status') !== false && $method == 'PUT') {
    $adminController = new AdminController($db);
    $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
    $id = end($partes);
    $dados = json_decode(file_get_contents("php://input"));
    $adminController->alterarStatus($id, $dados);
    exit();
}

// ROTAS DE DÚVIDAS / FEEDBACKS
if (strpos($request_uri, 'adm/duvidas') !== false) {
    $adminController = new AdminController($db);

    if ($method == 'GET') {
        $adminController->listarDuvidas();
    } elseif ($method == 'PUT' && strpos($request_uri, 'responder') !== false) {
        $dados = json_decode(file_get_contents("php://input"));
        $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
        $id = end($partes);
        $adminController->responderDuvida($id, $dados->resposta ?? '');
    } elseif ($method == 'PUT' && strpos($request_uri, 'reabrir') !== false) {
        $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
        $id = end($partes);
        $adminController->reabrirDuvida($id);
    } elseif ($method == 'PUT' && strpos($request_uri, 'excluir') !== false) {
        $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
        $id = end($partes);
        $adminController->excluirDuvida($id);
    }
    exit();
}

// =======================================================
// 👇 ROTEADOR DO USUÁRIO (WEB REACT) 👇
// =======================================================

// Limpa a URL para pegar só o endpoint final (ex: "cadastro")
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base_path === '/') { $base_path = ''; }
$request_path = parse_url($request_uri, PHP_URL_PATH);
if (!empty($base_path) && strpos($request_path, $base_path) === 0) {
    $request_path = substr($request_path, strlen($base_path));
}
$endpoint = trim(str_replace('/api/', '', $request_path), '/');

// 1º: Rotas que NÃO precisam de Token (Login e Cadastro)
if ($endpoint == 'login' && $method == 'POST') {
    $authController = new AuthController($db);
    $authController->login();
    exit();
}

if ($endpoint == 'cadastro' && $method == 'POST') {
    $authController = new AuthController($db);
    $authController->register();
    exit();
}

// 2º: A PARTIR DAQUI, TUDO EXIGE TOKEN (O AuthMiddleware vai barrar se não tiver)
$id_usuario_logado = AuthMiddleware::validarToken($db);

// Rota de Perfil
if ($endpoint == 'perfil') {
    $controller = new UsuarioController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($id_usuario_logado);
    if ($method == 'PUT') $controller->atualizar();
    exit();
}

// Rota de Tarefas
if ($endpoint == 'tarefas') {
    $controller = new TarefaController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}

// Rota de Tarefas com ID (ex: tarefas/5)
if (preg_match('/^tarefas\/(\d+)$/', $endpoint, $matches) && in_array($method, ['PUT', 'DELETE'])) {
    $controller = new TarefaController($db, $id_usuario_logado);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Matérias
if ($endpoint == 'materias') {
    $controller = new MateriaController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^materias\/(\d+)$/', $endpoint, $matches) && in_array($method, ['PUT', 'DELETE'])) {
    $controller = new MateriaController($db, $id_usuario_logado);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}


if ($endpoint == 'cronogramas') {
    $controller = new CronogramaController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^cronogramas\/(\d+)$/', $endpoint, $matches) && in_array($method, ['PUT', 'DELETE'])) {
    $controller = new CronogramaController($db, $id_usuario_logado);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Anotações (Notas)
if ($endpoint == 'blocos') {
    $controller = new BlocoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^blocos\/(\d+)$/', $endpoint, $matches) && in_array($method, ['PUT', 'DELETE'])) {
    $controller = new BlocoController($db, $id_usuario_logado);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}
// Rota de Cornell
if ($endpoint == 'cornell') {
    $controller = new CornellController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^cornell\/(\d+)$/', $endpoint, $matches) && $method == 'DELETE') {
    $controller = new CornellController($db, $id_usuario_logado);
    $controller->deletar($matches[1]);
    exit();
}


// Rota de Cronômetros (para o Relógio de Estudos)
if ($endpoint == 'cronometros') {
    $controller = new CronometroController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^cronometros\/(\d+)$/', $endpoint, $matches) && in_array($method, ['PUT', 'DELETE'])) {
    $controller = new CronometroController($db, $id_usuario_logado);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Flashcards
if ($endpoint == 'flashcards') {
    $controller = new FlashController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^flashcards\/materia\/(\d+)$/', $endpoint, $matches) && $method == 'GET') {
    $controller = new FlashController($db, $id_usuario_logado);
    $controller->listarPorMateria($matches[1]);
    exit();
}
if (preg_match('/^flashcards\/(\d+)$/', $endpoint, $matches) && in_array($method, ['GET', 'PUT', 'DELETE'])) {
    $controller = new FlashController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Mnemônicas (Sequências e Listas Ordenadas)
if ($endpoint == 'mnemonicas') {
    $controller = new MnemonicaController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^mnemonicas\/(\d+)$/', $endpoint, $matches)) {
    $controller = new MnemonicaController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Mapas Mentais
if ($endpoint == 'mapasmentais') {
    $controller = new MapaMentalController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^mapasmentais\/(\d+)$/', $endpoint, $matches)) {
    $controller = new MapaMentalController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Diagramas de Fluxo (Fluxogramas)
if ($endpoint == 'diagramasfluxo') {
    $controller = new DiagramaFluxoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^diagramasfluxo\/(\d+)$/', $endpoint, $matches)) {
    $controller = new DiagramaFluxoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Técnica Feynman
if ($endpoint == 'feynman') {
    $controller = new FeynmanController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^feynman\/(\d+)$/', $endpoint, $matches)) {
    $controller = new FeynmanController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Podcasts
if ($endpoint == 'podcasts') {
    $controller = new FeynmanController($db, $id_usuario_logado, 'podcast');
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^podcasts\/(\d+)$/', $endpoint, $matches)) {
    $controller = new FeynmanController($db, $id_usuario_logado, 'podcast');
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Eventos (Calendário)
if ($endpoint == 'eventos') {
    $controller = new EventoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^eventos\/(\d+)$/', $endpoint, $matches)) {
    $controller = new EventoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Grupos de Estudo
if ($endpoint == 'gruposestudo') {
    $controller = new GrupoEstudoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^gruposestudo\/([a-zA-Z0-9]+)$/', $endpoint, $matches)) {
    $controller = new GrupoEstudoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Histórico de Leituras (Leitura Savoring)
if ($endpoint == 'historicoleituras' || $endpoint == 'leituras') {
    $controller = new LeituraController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^(?:historicoleituras|leituras)\/(\d+)$/', $endpoint, $matches)) {
    $controller = new LeituraController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Upload de Arquivos para Simulados/Provas
if ($endpoint == 'simulados/upload' && $method == 'POST') {
    $controller = new SimuladoController($db, $id_usuario_logado);
    $controller->upload();
    exit();
}

// Rota de Simulados / Testes Práticos (Provas Anteriores)
if ($endpoint == 'simulados') {
    $controller = new SimuladoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^simulados\/(\d+)$/', $endpoint, $matches)) {
    $controller = new SimuladoController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Rota de Upload de Áudio de Resumo dos Vídeos
if (($endpoint == 'videos/audio' || $endpoint == 'bibliotecavideos/audio') && $method == 'POST') {
    $controller = new BibliotecaVideosController($db, $id_usuario_logado);
    $controller->uploadAudio();
    exit();
}

// Rota de Estudo com Vídeos Educativos (Biblioteca de Vídeos)
if ($endpoint == 'videos' || $endpoint == 'bibliotecavideos') {
    $controller = new BibliotecaVideosController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->listar();
    if ($method == 'POST') $controller->criar();
    exit();
}
if (preg_match('/^(?:videos|bibliotecavideos)\/(\d+)$/', $endpoint, $matches)) {
    $controller = new BibliotecaVideosController($db, $id_usuario_logado);
    if ($method == 'GET') $controller->buscar($matches[1]);
    if ($method == 'PUT') $controller->atualizar($matches[1]);
    if ($method == 'DELETE') $controller->deletar($matches[1]);
    exit();
}

// Se não achou nenhuma rota, avisa
http_response_code(404);
echo json_encode(["message" => "Rota não encontrada na API", "endpoint_recebido" => $endpoint]);
?>