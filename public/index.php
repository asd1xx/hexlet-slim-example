<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

const USER_LIST = 'users.json';
const COUNT_OF_ELEMENTS = 1;

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
$app->add(MethodOverrideMiddleware::class);
$router = $app->getRouteCollector()->getRouteParser();

$data = file_get_contents(USER_LIST);
$users = json_decode($data, true);

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
    $params = ['user' => ['nickname' => '', 'email' => ''], 'errors' => []];

    return $this->get('renderer')->render($response, "users/new.phtml");
});

$app->post('/users', function ($request, $response) use ($router) {
    $dataRequest = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($dataRequest);
    $id = ['id' => uniqid('user')];
    $user[] = array_merge($id, $dataRequest);
    $url = $router->urlFor('users');

    if (count($errors) === 0) {
        if (!file_exists(USER_LIST) || filesize(USER_LIST) === 0) {
            file_put_contents(USER_LIST, json_encode($user));
            return $response->withRedirect($url);
        } else {
            $dataFile = json_decode(file_get_contents(USER_LIST), true);
            $dataResult = array_merge($dataFile, $user);
            file_put_contents(USER_LIST, json_encode($dataResult));
            return $response->withRedirect($url);
        }
    }

    $params = ['user' => $user, 'errors' => $errors];
    $response = $response->withStatus(422);

    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$app->get('/users/{id}/edit', function ($request, $response, array $args) use ($users) {
    $id = $args['id'];

    foreach ($users as $user) {
        if ($user['id'] === $id) {
            $params = ['userData' => $user, 'user' => $user, 'errors' => []];
            // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
            // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
            // $this в Slim это контейнер зависимостей
            return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
        }
    }

    return $response->withStatus(404);
});

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router, $users) {
    $id = $args['id'];
    $dataRequest = $request->getParsedBodyParam('user');
    $validator = new App\Validator();
    $errors = $validator->validate($dataRequest);
    $url = $router->urlFor('users');

    foreach ($users as &$user) {
        if (count($errors) === 0 && $user['id'] === $id) {
            $user['nickname'] = $dataRequest['nickname'];
            $user['email'] = $dataRequest['email'];
            file_put_contents(USER_LIST, json_encode($users));
            return $response->withRedirect($url);
        }
    }

    $params = ['user' => $user, 'errors' => $errors];

    return $this->get('renderer')->render($response->withStatus(422), 'users/edit.phtml', $params);
})->setName('editUser');

$app->delete('/users/{id}', function ($request, $response, array $args) use ($router, $users) {
    $id = $args['id'];
    $url = $router->urlFor('users');
    $index = array_search($id, array_column($users, 'id'));

    foreach ($users as $user) {
        if ($user['id'] === $id) {
            $key = array_search($user, $users);
            array_splice($users, $key, COUNT_OF_ELEMENTS);
            file_put_contents(USER_LIST, json_encode($users));
            return $response->withRedirect($url);
        }
    }

    return $response->withStatus(422);
});

// $app->get('/courses/{id}', function ($request, $response, array $args) {
//     $id = $args['id'];
//     return $response->write("Course id: {$id}");
// })->setName('course');

$app->run();
