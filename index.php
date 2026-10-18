<?php
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
require_once 'routes/api.php';

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
// 👇 INTERCEPTADOR DE ROTAS DO DESKTOP (ADM) 👇
// =======================================================

// LOGIN
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

// ROTAS DE DÚVIDAS
// ROTAS DE DÚVIDAS
if (strpos($request_uri, 'adm/duvidas') !== false) {
    $adminController = new AdminController($db);
    
    if ($method == 'GET') {
        $adminController->listarDuvidas();
    } 
    elseif ($method == 'PUT' && strpos($request_uri, 'responder') !== false) {
        $dados = json_decode(file_get_contents("php://input"));
        $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
        $id = end($partes);
        $adminController->responderDuvida($id, $dados->resposta);
    } 
    elseif ($method == 'PUT' && strpos($request_uri, 'reabrir') !== false) {
        $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
        $id = end($partes);
        $adminController->reabrirDuvida($id);
    } 
    elseif ($method == 'PUT' && strpos($request_uri, 'excluir') !== false) {
        $partes = explode('/', parse_url($request_uri, PHP_URL_PATH));
        $id = end($partes);
        $adminController->excluirDuvida($id);
    }
    exit();
}

// =======================================================
// 👆 FIM DO INTERCEPTADOR 👆
// =======================================================

// Se não for rota do ADM, segue o fluxo normal do roteador para o site/app
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base_path === '/') { $base_path = ''; }
$request_path = parse_url($request_uri, PHP_URL_PATH);
if (!empty($base_path) && strpos($request_path, $base_path) === 0) {
    $request_path = substr($request_path, strlen($base_path));
}
$endpoint = str_replace('/api/', '', $request_path);

try {
    rotear($method, $endpoint, $db);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "message" => "Erro interno no servidor",
        "error" => $e->getMessage()
    ]);
}
?>