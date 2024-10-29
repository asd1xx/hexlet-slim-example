<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

$users = [
    ['id' => 'user67212379cdf1a', 'nickname' => 'max', 'email' => '872122@mail.ru'],
    ['id' => 'user672124b492c4b', 'nickname' => 'roman', 'email' => 'fan@mail.ru']
];
$data = file_get_contents('users.json');
$toArray = json_decode($data, true);
$users[] = $toArray;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
    // Благодаря пакету slim/http этот же код можно записать короче
    // return $response->write('Welcome to Slim!');
});

$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $filteredUsers = array_filter($users, function($user) use ($term) {
        return str_contains($user['nickname'], $term) === true;
    });
    $params = ['users' => $filteredUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->post('/users', function ($request, $response) {
    $dataRequest = $request->getParsedBodyParam('user');
    $id = ['id' => uniqid('user')];
    $user = array_merge($id, $dataRequest);
    $file = 'users.json';
    // file_put_contents($file, json_encode($user), FILE_APPEND | LOCK_EX);
    file_put_contents($file, json_encode($user));
    return $response->withRedirect('/users', 302);
    $params = [
        'user' => $user
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users/{id}', function ($request, $response, array $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->run();
