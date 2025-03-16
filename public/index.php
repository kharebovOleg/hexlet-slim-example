<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();

$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);

$usersJson = file_get_contents('date.txt');
$usersJson = str_replace('}{', '};{', $usersJson);
$usersJsonArr = explode(';', $usersJson);
$users = array_map(fn($user) => json_decode($user, true), $usersJsonArr);

$app->get('/users', function($request, $response) use ($users) {
    $messages = $this->get('flash')->getMessages();
    $params = ['users' => $users, 'flash' => $messages];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users');

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/users/new', function($request, $response) use ($router) {
    $params = ['url' => $router->urlFor('users')];
    return $this->get('renderer')->render($response, 'users/form.phtml', $params);
});

$app->get('/users/{id}', function($request, $response, $args) use ($users) {
    $id = $args['id'];

    foreach($users as $user) {
        if($user['name'] == $id) {
            $params = ['user' => $user];
            return $this->get('renderer')->render($response, 'users/user.phtml', $params);
        }
    }
    return $this->get('renderer')->render($response, "users/404.phtml")->withStatus(404);
});

$app->post('/users', function ($request, $response) {
    $data = $request->getParsedBody();
    $user = $data['user'] ?? null;

    if($user) {
        $jsonUser = json_encode($user);
        if(file_put_contents('date.txt', $jsonUser, FILE_APPEND)) {
            $this->get('flash')->addMessage('success', 'This is a message');
            return $response->withHeader('Location', '/users')->withStatus(302);
        }
    }
    $this->get('flash')->addMessage('error', 'This is a message');
    return $this->get('renderer')->render($response, "users/error.phtml");
});

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    //return $response->write('Welcome to Slim!');
});

$app->get('/foo', function ($req, $res) {
    // Добавление флеш-сообщения. Оно станет доступным на следующий HTTP-запрос.
    // 'success' — тип флеш-сообщения. Используется при выводе для форматирования.
    // Например, можно ввести тип success и отражать его зеленым цветом (на Хекслете такого много)
    $this->get('flash')->addMessage('success', 'This is a message');

    return $res->withRedirect('/bar');
});

$app->get('/bar', function ($req, $res) {
    // Извлечение flash-сообщений, установленных на предыдущем запросе
    $messages = $this->get('flash')->getMessages();
    print_r($messages); // => ['success' => ['This is a message']]

    $params = ['flash' => $messages];
    return $this->get('renderer')->render($res, "users/form.phtml", $params);
});

$app->run();
