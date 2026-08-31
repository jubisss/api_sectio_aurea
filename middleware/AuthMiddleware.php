<?php
class AuthMiddleware {
    
    public static function validarToken($db) {
        $headers = getallheaders();
        $authHeader = null;

        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['X-Authorization'])) {
            $authHeader = $headers['X-Authorization'];
        } elseif (isset($headers['x-authorization'])) {
            $authHeader = $headers['x-authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_X_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        if (!$authHeader) {
            http_response_code(401);
            echo json_encode(["message" => "Token não fornecido"]);
            exit();
        }
        
        $token = str_replace('Bearer ', '', $authHeader);
        
        // Decodifica o token (formato: id_usuario:timestamp)
        $decoded = base64_decode($token);
        $parts = explode(':', $decoded);
        
        if(count($parts) != 2) {
            http_response_code(401);
            echo json_encode(["message" => "Token inválido"]);
            exit();
        }
        
        $id_usuario = $parts[0];
        
        // Verifica se o usuário existe e está ativo
        $usuario = new Usuario($db);
        $usuario->id_usuario = $id_usuario;
        
        if(!$usuario->readOne()) {
            http_response_code(401);
            echo json_encode(["message" => "Usuário não encontrado ou inativo"]);
            exit();
        }
        
        return $id_usuario;
    }
}
?>