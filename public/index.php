<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

const USER_LIST = 'users.json';

$data = file_get_contents(USER_LIST);
$users = json_decode($data, true);

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {
    $router->urlFor('users');
    $router->urlFor('new_users');
    $router->urlFor('create');
    $router->urlFor('course', ['id' => 1]);
    $router->urlFor('user', ['id' => 1]);
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
    // $messages = $this->get('flash')->getMessages();
    // $params = ['users' => $filteredUsers, 'term' => $term, 'flash' => $messages];
    $params = ['users' => $filteredUsers, 'term' => $term];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => '']
    ];
    return $this->get('renderer')->render($response, "users/new.phtml");
})->setName('new_users');

$app->post('/users', function ($request, $response) {
    $dataRequest = $request->getParsedBodyParam('user');
    $id = ['id' => uniqid('user')];
    $user[] = array_merge($id, $dataRequest);

    if (!file_exists(USER_LIST) || filesize(USER_LIST) === 0) {
        file_put_contents(USER_LIST, json_encode($user));
    } else {
        $dataFile = json_decode(file_get_contents(USER_LIST), true);
        $dataResult = array_merge($dataFile, $user);
        file_put_contents(USER_LIST, json_encode($dataResult));
    }

    // $this->get('flash')->addMessage('success', 'OK');
    // return $response->withRedirect('/users');
    $params = [
        'user' => $user
    ];
    return $this->get('renderer')->render($response->withRedirect('/users'), "users/new.phtml", $params);
})->setName('create');

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/users/{id}', function ($request, $response, array $args) use ($users) {
    $id = $args['id'];

    foreach ($users as $user) {
        if ($user['id'] === $id) {
            $params = ['id' => $args['id'], 'nickname' => $user['nickname'], 'email' => $user['email']];
            // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
            // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
            // $this в Slim это контейнер зависимостей
            return $this->get('renderer')->render($response, 'users/show.phtml', $params);
        }
    }
    
    return $response->withStatus(404);
})->setName('user');

$app->run();
