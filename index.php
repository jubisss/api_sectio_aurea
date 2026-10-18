<?php
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

require_once 'controllers/cornellController.php';
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

if (strpos($request_uri, 'adm/login') !== false && $method == 'POST') {
    $adminController = new AdminController($db);
    $adminController->login();
    exit();
}

if (strpos($request_uri, 'adm/cadastrar') !== false && $method == 'POST') {
    $adminController = new AdminController($db);
    $adminController->cadastrar();
    exit();
}

if (strpos($request_uri, 'adm/estatisticas') !== false && $method == 'GET') {
    $adminController = new AdminController($db);
    $adminController->estatisticas();
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

// Se não achou nenhuma rota, avisa
http_response_code(404);
echo json_encode(["message" => "Rota não encontrada na API", "endpoint_recebido" => $endpoint]);
?>