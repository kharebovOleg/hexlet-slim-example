<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

use Slim\Psr7\Response;
use Slim\Psr7\Factory\ResponseFactory;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);


$users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    //return $response->write('Welcome to Slim!');
});

// $app->get('/users', function($request, $response) use ($users) {

//     $queryParams = $request->getQueryParams();
//     $term = $queryParams['term'] ?? null;

//     $filteredCourses = array_filter($users, function($name) use ($term) {
//         return str_starts_with($name, $term);
//     });
    
//     $params = ['users' => $filteredCourses, 'term' => $term];
//     return $this->get('renderer')->render($response, 'users/show.phtml', $params);
// });

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->get('/users', function($request, $response) use ($users) {
    return $this->get('renderer')->render($response, 'users/form.phtml');
});

$app->post('/users', function ($request, $response) {
    $data = $request->getParsedBody();
    $user = $data['user'] ?? null;

    if($user) {
        $jsonUser = json_encode($user);
        if(file_put_contents('date.txt', $jsonUser, FILE_APPEND)) {
            return $response->withHeader('Location', '/users')->withStatus(302);
        }
    }
    return $this->get('renderer')->render($response, "users/error.phtml", $params);
});

$app->run();
