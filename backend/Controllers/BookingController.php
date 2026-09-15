<?php
require_once __DIR__ . '/../Database.php';

class BookingController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handle($method, $uri) {
        switch ($method) {
            case 'GET':
                $this->getBookings();
                break;
            case 'POST':
                $this->createBooking();
                break;
            case 'DELETE':
                $this->deleteBooking();
                break;
            default:
                http_response_code(405);
                echo json_encode(["message" => "Método não permitido."]);
                break;
        }
    }

    private function getBookings() {
        $student_id = isset($_GET['student_id']) ? $_GET['student_id'] : null;

        if ($student_id) {
            $query = "SELECT b.id as booking_id, c.id as class_id, c.title, c.start_time, u.name as instructor_name 
                      FROM bookings b
                      JOIN classes c ON b.class_id = c.id
                      JOIN users u ON c.instructor_id = u.id
                      WHERE b.student_id = :student_id
                      ORDER BY c.start_time ASC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":student_id", $student_id);
            $stmt->execute();
            $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

            http_response_code(200);
            echo json_encode($bookings);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID do estudante não fornecido."]);
        }
    }

    private function createBooking() {
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->class_id) && !empty($data->student_id)) {
            // Verificar capacidade da turma
            $query_capacity = "SELECT capacity, (SELECT COUNT(*) FROM bookings WHERE class_id = :class_id) as current_bookings 
                               FROM classes WHERE id = :class_id";
            $stmt_cap = $this->db->prepare($query_capacity);
            $stmt_cap->bindParam(":class_id", $data->class_id);
            $stmt_cap->execute();
            $class_info = $stmt_cap->fetch(PDO::FETCH_ASSOC);

            if ($class_info) {
                if ($class_info['current_bookings'] >= $class_info['capacity']) {
                    http_response_code(400);
                    echo json_encode(["message" => "A aula já está com a capacidade máxima."]);
                    return;
                }
            } else {
                http_response_code(404);
                echo json_encode(["message" => "Aula não encontrada."]);
                return;
            }

            // Inserir agendamento
            $query = "INSERT INTO bookings SET class_id=:class_id, student_id=:student_id";
            $stmt = $this->db->prepare($query);

            $stmt->bindParam(":class_id", $data->class_id);
            $stmt->bindParam(":student_id", $data->student_id);

            try {
                if ($stmt->execute()) {
                    http_response_code(201);
                    echo json_encode(["message" => "Agendamento realizado com sucesso."]);
                } else {
                    http_response_code(503);
                    echo json_encode(["message" => "Não foi possível realizar o agendamento."]);
                }
            } catch (PDOException $e) {
                http_response_code(400);
                echo json_encode(["message" => "Você já está agendado nesta aula."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Dados incompletos."]);
        }
    }

    private function deleteBooking() {
        $id = isset($_GET['id']) ? $_GET['id'] : null;

        if ($id) {
            $query = "DELETE FROM bookings WHERE id = :id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id", $id);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(["message" => "Agendamento cancelado com sucesso."]);
            } else {
                http_response_code(503);
                echo json_encode(["message" => "Não foi possível cancelar o agendamento."]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["message" => "ID do agendamento não fornecido."]);
        }
    }
}
?>
