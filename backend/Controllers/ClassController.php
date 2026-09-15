<?php
require_once __DIR__ . '/../Database.php';

class ClassController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handle($method, $uri) {
        switch ($method) {
            case 'GET':
                $this->getClasses();
                break;
            case 'POST':
                $this->createClass();
                break;
            case 'DELETE':
                $this->deleteClass();
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Método não permitido."]);
                break;
        }
    }

    private function getClasses() {
        // Se houver um parâmetro instructor_id, filtramos por ele
        $instructor_id = isset($_GET['instructor_id']) ? $_GET['instructor_id'] : null;

        $query = "SELECT c.id, c.title, c.description, c.start_time, c.capacity, u.name as instructor_name 
                  FROM classes c 
                  JOIN users u ON c.instructor_id = u.id";
        
        if ($instructor_id) {
            $query .= " WHERE c.instructor_id = :instructor_id";
        }
        $query .= " ORDER BY c.start_time ASC";

        $stmt = $this->db->prepare($query);

        if ($instructor_id) {
            $stmt->bindParam(":instructor_id", $instructor_id);
        }

        $stmt->execute();
        $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($classes);
    }

    private function createClass() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->instructor_id) && !empty($data->title) && !empty($data->start_time) && isset($data->capacity)) {
            $query = "INSERT INTO classes SET instructor_id=:instructor_id, title=:title, description=:description, start_time=:start_time, capacity=:capacity";
            $stmt = $this->db->prepare($query);

            $stmt->bindParam(":instructor_id", $data->instructor_id);
            $stmt->bindParam(":title", $data->title);
            $description = isset($data->description) ? $data->description : '';
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":start_time", $data->start_time);
            $stmt->bindParam(":capacity", $data->capacity);

            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["message" => "Aula criada com sucesso."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Não foi possível criar a aula."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
        }
    }

    private function deleteClass() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if ($id) {
            $query = "DELETE FROM classes WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Aula deletada com sucesso."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Não foi possível deletar a aula."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID da aula não fornecido."]);
        }
    }
}
?>
