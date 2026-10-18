<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

class AuthController {
    private $usuario;
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
        $this->usuario = new Usuario($db);
    }
    
    public function login() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(empty($data->email) || empty($data->senha)) {
            http_response_code(400);
            echo json_encode(["message" => "Email e senha são obrigatórios"]);
            return;
        }
        
        if($this->usuario->findByEmail($data->email)) {
            if(password_verify($data->senha, $this->usuario->senha)) {
                // Gera token simples (em produção use JWT)
                $token = base64_encode($this->usuario->id_usuario . ":" . time());
                
                echo json_encode([
                    "message" => "Login realizado com sucesso",
                    "token" => $token,
                    "user" => [
                        "id" => $this->usuario->id_usuario,
                        "nome" => $this->usuario->nome,
                        "email" => $this->usuario->email,
                        "plano" => $this->usuario->plano,
                        "tipo_dom" => $this->usuario->tipo_dom,
                        "clas_inteli" => json_decode($this->usuario->clas_inteli),
                        "cor_dominante" => $this->usuario->cor_dominante,
                        "foto" => $this->usuario->foto
                    ]
                ]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Senha incorreta"]);
            }
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Usuário não encontrado"]);
        }
    }
    
    public function register() {
        $data = json_decode(file_get_contents("php://input"));
        
        if(empty($data->nome) || empty($data->email) || empty($data->senha)) {
            http_response_code(400);
            echo json_encode(["message" => "Nome, email e senha são obrigatórios"]);
            return;
        }
        
        if($this->usuario->emailExiste($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "E-mail já cadastrado"]);
            return;
        }
        
        $this->usuario->nome = $data->nome;
        $this->usuario->email = $data->email;
        $this->usuario->senha = $data->senha;
        $this->usuario->plano = $data->plano ?? 'Gratuito';
        $this->usuario->tipo_dom = $data->tipo_dom ?? null;
        $this->usuario->clas_inteli = json_encode($data->clas_inteli ?? []);
        
        if($this->usuario->create()) {
            http_response_code(201);
            echo json_encode([
                "message" => "Usuário criado com sucesso",
                "id_usuario" => $this->usuario->id_usuario
            ]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao criar usuário"]);
        }
    }
    
    public function recuperarSenha() {
        $data = json_decode(file_get_contents("php://input"));
        if(empty($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "E-mail é obrigatório"]);
            return;
        }
        
        if(!$this->usuario->findByEmail($data->email)) {
            http_response_code(404);
            echo json_encode(["message" => "E-mail não cadastrado"]);
            return;
        }
        
        $code = rand(100000, 999999);
        $tempFile = sys_get_temp_dir() . '/recovery_codes.json';
        $codes = [];
        if(file_exists($tempFile)) {
            $codes = json_decode(file_get_contents($tempFile), true) ?? [];
        }
        $codes[$data->email] = [
            "code" => $code,
            "expires" => time() + 3600
        ];
        file_put_contents($tempFile, json_encode($codes));
        
        // Envio do código por e-mail via EmailService (Gmail SMTP)
        $assunto = "Sectio Aurea - Código de Recuperação de Senha";
        $mensagem = "<h2>Recuperação de Senha - Sectio Aurea</h2>";
        $mensagem .= "<p>Você solicitou a redefinição de sua senha de acesso.</p>";
        $mensagem .= "<p>Seu código de verificação é: <strong style='font-size: 24px; color: #ff5f00;'>$code</strong></p>";
        $mensagem .= "<p>Este código expira em 1 hora.</p>";
        $mensagem .= "<br><p>Se você não solicitou esta alteração, por favor ignore esta mensagem.</p>";

        EmailService::enviar($data->email, $assunto, $mensagem);
        
        echo json_encode([
            "message" => "Código de recuperação enviado com sucesso",
            "code" => $code
        ]);
    }
    
    public function enviarCodigoCadastro() {
        $data = json_decode(file_get_contents("php://input"));
        if(empty($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "E-mail é obrigatório"]);
            return;
        }

        if($this->usuario->emailExiste($data->email)) {
            http_response_code(400);
            echo json_encode(["message" => "Este e-mail já está cadastrado"]);
            return;
        }

        $code = rand(100000, 999999);
        $tempFile = sys_get_temp_dir() . '/signup_codes.json';
        $codes = [];
        if(file_exists($tempFile)) {
            $codes = json_decode(file_get_contents($tempFile), true) ?? [];
        }
        $codes[$data->email] = [
            "code" => $code,
            "expires" => time() + 1800 // 30 minutos
        ];
        file_put_contents($tempFile, json_encode($codes));

        $assunto = "Sectio Aurea - Código de Validação de Cadastro";
        $mensagem = "<h2>Bem-vindo ao Sectio Aurea!</h2>";
        $mensagem .= "<p>Para confirmar a criação da sua conta, utilize o código de verificação abaixo:</p>";
        $mensagem .= "<p>Seu código é: <strong style='font-size: 24px; color: #ff5f00;'>$code</strong></p>";
        $mensagem .= "<p>Este código expira em 30 minutos.</p>";

        EmailService::enviar($data->email, $assunto, $mensagem);

        echo json_encode([
            "message" => "Código de validação enviado para o e-mail",
            "code" => $code
        ]);
    }
    
    public function resetarSenha() {
        $data = json_decode(file_get_contents("php://input"));
        if(empty($data->email) || empty($data->codigo) || empty($data->nova_senha)) {
            http_response_code(400);
            echo json_encode(["message" => "E-mail, código e nova senha são obrigatórios"]);
            return;
        }
        
        $tempFile = sys_get_temp_dir() . '/recovery_codes.json';
        if(!file_exists($tempFile)) {
            http_response_code(400);
            echo json_encode(["message" => "Nenhum código de recuperação foi gerado"]);
            return;
        }
        
        $codes = json_decode(file_get_contents($tempFile), true) ?? [];
        if(!isset($codes[$data->email])) {
            http_response_code(400);
            echo json_encode(["message" => "Código de recuperação não encontrado para este e-mail"]);
            return;
        }
        
        $record = $codes[$data->email];
        if(time() > $record["expires"]) {
            http_response_code(400);
            echo json_encode(["message" => "Código expirado, solicite um novo"]);
            return;
        }
        
        if($data->codigo != $record["code"]) {
            http_response_code(400);
            echo json_encode(["message" => "Código de recuperação inválido"]);
            return;
        }
        
        if(!$this->usuario->findByEmail($data->email)) {
            http_response_code(404);
            echo json_encode(["message" => "Usuário não encontrado"]);
            return;
        }
        
        $newPasswordHash = password_hash($data->nova_senha, PASSWORD_DEFAULT);
        if($this->usuario->updatePassword($newPasswordHash)) {
            unset($codes[$data->email]);
            file_put_contents($tempFile, json_encode($codes));
            echo json_encode(["message" => "Senha redefinida com sucesso"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Erro ao redefinir a senha"]);
        }
    }
}
?>