<?php

require '../vendor/autoload.php';

use App\Router;

// Middleware örneği
$authMiddleware = function($method, $path) {
    // Örneğin kullanıcı doğrulaması
    if (!isset($_GET['token']) || $_GET['token'] !== 'secret') {
        return json_encode(["error" => "Unauthorized"], JSON_PRETTY_PRINT);
    }
    return null;
};

// Router sınıfını başlatın ve base path'i ayarlayın
$router = new Router('/php-router-project/public');

// Root route tanımlayın
$router->get('/', function() {
    echo json_encode(["message" => "Welcome to the home page!"], JSON_PRETTY_PRINT);
});

$router->get('/index.php', function() {
    echo json_encode(["message" => "Welcome to the home page!"], JSON_PRETTY_PRINT);
});

$router->get('/users', function() {
    echo json_encode(["message" => "Get all users"], JSON_PRETTY_PRINT);
});

$router->get('/user/{id}', function($id) {
    echo json_encode(["message" => "Get user with ID: " . $id], JSON_PRETTY_PRINT);
}, [$authMiddleware]);

$router->post('/user', function() {
    echo json_encode(["message" => "Create new user"], JSON_PRETTY_PRINT);
});

$router->get('/example', 'App\Controllers\ExampleController@show');

// 404 ve 405 hata durumları için callback fonksiyonları tanımlayın
$router->setNotFoundCallback(function() {
    echo json_encode(["error" => "404 Not Found"], JSON_PRETTY_PRINT);
});

$router->setMethodNotAllowedCallback(function() {
    echo json_encode(["error" => "405 Method Not Allowed"], JSON_PRETTY_PRINT);
});

// Dispatcher'ı çalıştırın
$router->dispatch();
