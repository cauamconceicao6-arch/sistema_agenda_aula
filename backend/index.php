<?php
// Permitir requisições de outras origens (CORS) - útil se frontend estiver em porta diferente
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Lidar com a requisição OPTIONS (Preflight do CORS)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obter a URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Ajustar base_path dinamicamente (Funciona para XAMPP e Laravel Herd)
$script_path = dirname($_SERVER['SCRIPT_NAME']);
$base_path = str_replace('\\', '/', $script_path);

// Remover o base_path da URI para facilitar o roteamento
if (strpos($uri, $base_path) === 0) {
    $uri = substr($uri, strlen($base_path));
}

// Remover '/index.php' caso a requisição venha diretamente pelo arquivo (sem .htaccess)
if (strpos($uri, '/index.php') === 0) {
    $uri = substr($uri, strlen('/index.php'));
}

// Definir rotas básicas
$routes = [
    '/api/users/register' => 'UserController@register',
    '/api/users/login' => 'UserController@login',
    '/api/classes' => 'ClassController@handle',
    '/api/bookings' => 'BookingController@handle'
];

$method = $_SERVER['REQUEST_METHOD'];

// Para fins de testes iniciais:
if ($uri === '/' || $uri === '' || $uri === '/api') {
    echo json_encode(["message" => "Bem-vindo à API do Sistema de Agendamento de Aulas."]);
    exit();
}

// Roteamento simples
$route_found = false;
foreach ($routes as $route => $handler) {
    // Verificamos se a URI começa com a rota ou é igual
    // Para um roteador mais avançado usaríamos Regex, mas assim resolvemos o básico
    if (strpos($uri, $route) === 0) {
        $route_found = true;
        list($controllerName, $actionName) = explode('@', $handler);
        
        $controllerFile = __DIR__ . '/Controllers/' . $controllerName . '.php';
        
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controller = new $controllerName();
            if (method_exists($controller, $actionName)) {
                $controller->$actionName($method, $uri);
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Método $actionName não encontrado no controlador."]);
            }
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Controlador $controllerName não encontrado."]);
        }
        break;
    }
}

if (!$route_found) {
    http_response_code(404);
    echo json_encode(["message" => "Rota não encontrada."]);
}
?>
