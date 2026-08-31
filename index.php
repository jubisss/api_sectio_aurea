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
require_once 'controllers/authController.php';
require_once 'controllers/usuarioController.php';
require_once 'controllers/materiaController.php';
require_once 'controllers/blocoAnotacoesController.php';
require_once 'controllers/cronogramaController.php';
require_once 'controllers/cronometroController.php';
require_once 'controllers/eventosController.php';
require_once 'controllers/flashcardController.php';
require_once 'controllers/tarefasController.php';
require_once 'middleware/AuthMiddleware.php';
require_once 'routes/api.php';

// Headers CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Authorization");
header("Content-Type: application/json");

// Responder OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Conectar ao banco
$database = new Database();
$db = $database->getConnection();

// Pegar método e endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base_path === '/') {
    $base_path = '';
}

$request_path = parse_url($request_uri, PHP_URL_PATH);
if (!empty($base_path) && strpos($request_path, $base_path) === 0) {
    $request_path = substr($request_path, strlen($base_path));
}

$endpoint = str_replace('/api/', '', $request_path);

// Roteador
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