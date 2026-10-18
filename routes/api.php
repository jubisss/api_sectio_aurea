<?php
function rotear($method, $endpoint, $db) {
    $parts = explode('/', trim($endpoint, '/'));
    $resource = end($parts); // Se a URL for .../adm/login, ele pega 'login'
// Mas espera, a gente quer o 'adm'. 
$resource = $parts[0];
if ($resource == 'index.php' || $resource == 'api_sectio_aurea-main') {
    $resource = $parts[1] ?? '';
    $id = $parts[2] ?? null;
    $subresource = $parts[3] ?? null;
}
    $id = $parts[1] ?? null;
    $subresource = $parts[2] ?? null;
    
    // Rotas públicas
    switch($resource) {
        case 'login':
            if($method == 'POST') {
                $auth = new AuthController($db);
                $auth->login();
            }
            return;
            
        case 'cadastro':
        case 'register':
            if($method == 'POST') {
                $auth = new AuthController($db);
                $auth->register();
            }
            return;
            
        case 'recuperar-senha':
            if($method == 'POST') {
                $auth = new AuthController($db);
                $auth->recuperarSenha();
            }
            return;
            
        case 'resetar-senha':
            if($method == 'POST') {
                $auth = new AuthController($db);
                $auth->resetarSenha();
            }
            return;
            
            case 'inteligencias':
            if($method == 'GET') {
                $inteligencias = new Inteligencias($db);
                $stmt = $inteligencias->read();
                $result = [];
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row['metodo'] = json_decode($row['metodo']);
                    array_push($result, $row);
                }
                echo json_encode($result);
            }
            return; // 👈 IMPORTANTE: Isso fecha o case 'inteligencias' e impede que ele caia no case 'adm'!

        case 'adm':
            $adminController = new AdminController($db);
            
            if ($method == 'POST' && $id == 'login') {
                $adminController->login();
            } elseif ($method == 'POST' && $id == 'cadastrar') {
                $adminController->cadastrar();
            } elseif ($method == 'POST' && $id == 'editar') {
                $adminController->editar();
            } elseif ($method == 'POST' && $id == 'buscar') {
                $adminController->buscarAdm();
            } 
            // Rotas do painel
            elseif ($method == 'GET' && $id == 'usuarios') {
                $adminController->listarUsuarios();
            } elseif ($method == 'GET' && $id == 'estatisticas') {
                $adminController->estatisticas();
            } elseif ($method == 'PUT' && $id == 'status' && $subresource) {
                $dados = json_decode(file_get_contents("php://input"));
                $adminController->alterarStatus($subresource, $dados);
            }
            return; // 👈 IMPORTANTE: Fecha o case 'adm' e impede que ele caia no AuthMiddleware!

        // 👇 ADICIONE ESTE BLOCO DE DÚVIDAS AQUI (se você quiser que o Desktop gerencie as dúvidas)
        case 'duvidas':
            $adminController = new AdminController($db);
            if ($method == 'GET') {
                $adminController->listarDuvidas();
            } elseif ($method == 'PUT' && $id == 'responder') {
                $dados = json_decode(file_get_contents("php://input"));
                $adminController->responderDuvida($subresource, $dados->resposta);
            } elseif ($method == 'PUT' && $id == 'reabrir') {
                $adminController->reabrirDuvida($subresource);
            } elseif ($method == 'PUT' && $id == 'excluir') {
                $adminController->excluirDuvida($subresource);
            }
            return; 
    }


    
    
    // Rotas protegidas (precisam de token)
    $id_usuario_logado = AuthMiddleware::validarToken($db);
    
    switch($resource) {
        case 'perfil':
            $controller = new UsuarioController($db, $id_usuario_logado);
            switch($method) {
                case 'GET':
                    $controller->buscar($id_usuario_logado);
                    break;
                case 'PUT':
                    $controller->atualizar();
                    break;
            }
            break;
            
        case 'trocar-senha':
            $controller = new UsuarioController($db, $id_usuario_logado);
            if($method == 'POST' || $method == 'PUT') {
                $controller->trocarSenha();
            }
            break;
            
        case 'usuarios':
            $controller = new UsuarioController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    if($id) {
                        $controller->buscar($id);
                    } else {
                        $controller->listar();
                    }
                    break;
                case 'PUT':
                    $controller->atualizar();
                    break;
                case 'DELETE':
                    $controller->deletar();
                    break;
            }
            break;
            
        case 'materias':
            $controller = new MateriaController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    if($id) {
                        $controller->buscar($id);
                    } else {
                        $controller->listar();
                    }
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        case 'notas':
        case 'blocos':
            $controller = new BlocoController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    if($id) {
                        $controller->buscar($id);
                    } else {
                        $controller->listar();
                    }
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        case 'cronograma':
        case 'cronogramas':
            $controller = new CronogramaController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    $controller->listar();
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        case 'sessoes-estudo':
        case 'cronometros':
            $controller = new CronometroController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    $controller->listar();
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        case 'eventos':
            $controller = new EventoController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    if($id == 'data' && $subresource) {
                        $controller->listarPorData($subresource);
                    } elseif($id) {
                        $controller->buscar($id);
                    } else {
                        $controller->listar();
                    }
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        case 'flashcards':
            $controller = new FlashController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    if($subresource == 'materia' && $id) {
                        $controller->listarPorMateria($id);
                    } elseif($id) {
                        $controller->buscar($id);
                    } else {
                        $controller->listar();
                    }
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        case 'tarefas':
            $controller = new TarefaController($db, $id_usuario_logado);
            
            switch($method) {
                case 'GET':
                    if($id) {
                        $controller->buscar($id);
                    } else {
                        $controller->listar();
                    }
                    break;
                case 'POST':
                    $controller->criar();
                    break;
                case 'PUT':
                    if($id) $controller->atualizar($id);
                    break;
                case 'DELETE':
                    if($id) $controller->deletar($id);
                    break;
            }
            break;
            
        default:
            http_response_code(404);
            echo json_encode(["message" => "Rota não encontrada"]);
    }
}
?>