<?php
require_once __DIR__ . '/../Database.php';

class UserController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function register($method, $uri) {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(["message" => "Método não permitido."]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->name) && !empty($data->email) && !empty($data->password) && !empty($data->role)) {
            $query = "INSERT INTO users SET name=:name, email=:email, password=:password, role=:role";
            $stmt = $this->db->prepare($query);

            $stmt->bindParam(":name", $data->name);
            $stmt->bindParam(":email", $data->email);
            // Hash da senha para segurança
            $password_hash = password_hash($data->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $password_hash);
            
            // Validar o papel (role)
            $role = in_array($data->role, ['student', 'instructor']) ? $data->role : 'student';
            $stmt->bindParam(":role", $role);

            try {
                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(["message" => "Usuário criado com sucesso."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Não foi possível criar o usuário."]);
                }
            } catch (PDOException $e) {
                http_response_code(400);
                echo json_encode(["message" => "Erro ao criar usuário, email pode já estar em uso."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
        }
    }

    public function login($method, $uri) {
        if ($method !== 'POST') {
            http_response_code(405);
            echo json_encode(["message" => "Método não permitido."]);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->email) && !empty($data->password)) {
            $query = "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 0,1";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(1, $data->email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($data->password, $row['password'])) {
                    http_response_code(200);
                    // Como não estamos usando JWT, retornamos os dados básicos para o frontend guardar na sessão
                    echo json_encode([
                        "message" => "Login realizado com sucesso.",
                        "user" => [
                            "id" => $row['id'],
                            "name" => $row['name'],
                            "email" => $row['email'],
                            "role" => $row['role']
                        ]
                    ]);
                } else {
                    http_response_code(401);
                    echo json_encode(["message" => "Senha incorreta."]);
                }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Usuário não encontrado."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
        }
    }
}
?>
