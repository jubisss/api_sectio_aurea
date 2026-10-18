<?php
// models/adminModel.php
class AdminModel {
    private $conn;
    private $table = "PI_Adm"; // Nossa tabela específica

    public function __construct($db) {
        $this->conn = $db;
    }

    // --- MÉTODOS DO PAINEL (que já fizemos antes) ---
    public function listarTodosUsuarios() {
        $query = "SELECT id_usuario, nome, email, tipo_dom, ativo FROM PI_Usuario ORDER BY id_usuario DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function alterarStatusUsuario($id_usuario, $novoStatus) {
        $query = "UPDATE PI_Usuario SET ativo = :ativo WHERE id_usuario = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":ativo", $novoStatus);
        $stmt->bindParam(":id", $id_usuario);
        return $stmt->execute();
    }

        public function buscarEstatisticas() {
        $query = "
            SELECT 
              CASE 
                WHEN i.nome IN ('Corporal', 'Cinestésica') THEN 'Corporal-Cinestésica'
                ELSE i.nome 
              END AS tipo,
              CASE 
                WHEN i.nome IN ('Corporal', 'Cinestésica') THEN '#00bf63'
                ELSE i.cor 
              END AS cor,
              COUNT(u.id_usuario) AS total
            FROM PI_Inteligencias i
            LEFT JOIN PI_Usuario u ON u.tipo_dom = i.nome AND u.ativo = 1
            GROUP BY 
              CASE 
                WHEN i.nome IN ('Corporal', 'Cinestésica') THEN 'Corporal-Cinestésica'
                ELSE i.nome 
              END,
              CASE 
                WHEN i.nome IN ('Corporal', 'Cinestésica') THEN '#00bf63'
                ELSE i.cor 
              END
            HAVING total > 0
            ORDER BY total DESC
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // --- MÉTODOS DO ADM (Login, Cadastro e Edição) ---

    // 1. Login: Busca o Adm pelo e-mail
    public function buscarAdmPorEmail($email) {
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Verifica se já existe um Adm com esse e-mail
    public function emailAdmExiste($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // 3. Cadastrar novo Adm
    public function criarAdm($nome, $email, $senha, $fotoBuffer = null) {
        $query = "INSERT INTO " . $this->table . " (nome, email, senha, foto) VALUES (:nome, :email, :senha, :foto)";
        $stmt = $this->conn->prepare($query);
        
        // Limpeza básica
        $nome = htmlspecialchars(strip_tags($nome));
        $email = htmlspecialchars(strip_tags($email));
        
        // AQUI: Como você estava salvando a senha pura no seu código antigo, vamos manter assim para não quebrar o banco.
        // (O ideal seria usar password_hash, mas se o banco já tem senhas puras, o login não funcionaria).
        
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":senha", $senha);
        
        if ($fotoBuffer) {
            $stmt->bindParam(":foto", $fotoBuffer, PDO::PARAM_LOB);
        } else {
            $stmt->bindValue(":foto", null, PDO::PARAM_NULL);
        }
        
        return $stmt->execute();
    }

    // 4. Atualizar Adm
    public function atualizarAdm($email_antigo, $nome, $email_novo, $senha, $fotoBuffer = null) {
        if ($fotoBuffer) {
            $query = "UPDATE " . $this->table . " SET nome=:nome, email=:email_novo, senha=:senha, foto=:foto WHERE email=:email_antigo";
        } else {
            $query = "UPDATE " . $this->table . " SET nome=:nome, email=:email_novo, senha=:senha WHERE email=:email_antigo";
        }
        
        $stmt = $this->conn->prepare($query);
        
        $nome = htmlspecialchars(strip_tags($nome));
        $email_novo = htmlspecialchars(strip_tags($email_novo));
        
        $stmt->bindParam(":nome", $nome);
        $stmt->bindParam(":email_novo", $email_novo);
        $stmt->bindParam(":senha", $senha);
        $stmt->bindParam(":email_antigo", $email_antigo);
        
        if ($fotoBuffer) {
            $stmt->bindParam(":foto", $fotoBuffer, PDO::PARAM_LOB);
        }
        
        return $stmt->execute();
    }

public function listarDuvidas() {
    $stmt = $this->conn->prepare("SELECT id_duvida, nome, email, tipo, mensagem, status, resposta, DATE_FORMAT(data_envio, '%d/%m/%Y %H:%i') as data_formatada FROM PI_Duvidas WHERE ativo = 1 ORDER BY data_envio DESC");
    $stmt->execute();
    return $stmt;
}

    public function responderDuvida($id, $resposta) {
    $stmt = $this->conn->prepare("UPDATE PI_Duvidas SET resposta = :r, status = 'respondida' WHERE id_duvida = :id");
    return $stmt->execute([':r' => $resposta, ':id' => $id]);
}

public function reabrirDuvida($id) {
    $stmt = $this->conn->prepare("UPDATE PI_Duvidas SET resposta = NULL, status = 'pendente' WHERE id_duvida = :id");
    return $stmt->execute([':id' => $id]);
}

public function excluirDuvida($id) {
    $stmt = $this->conn->prepare("UPDATE PI_Duvidas SET ativo = 0 WHERE id_duvida = :id");
    return $stmt->execute([':id' => $id]);
}

}
?>