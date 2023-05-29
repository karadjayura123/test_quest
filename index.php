<?php
require 'vendor/autoload.php';

require_once 'AltoRouter.php';
session_start();
$router = new AltoRouter();
$router->map('GET', '/', '\application\controllers\MainController@indexAction', 'indexAction');
$router->map('POST', '/login', '\application\controllers\auth\loginController@loginAction', 'loginAction');
$router->map('GET', '/Home', '\application\controllers\HomeController@getAllSurveysAction', 'getAllSurveysActions');
$router->map('GET', '/surveys', '\application\controllers\SurveyController@getAllSurveysAction', 'getAllSurveysAction');
$router->map('GET', '/logout', '\application\controllers\auth\LogoutController@logoutAction', 'logoutAction');
$router->map('POST', '/survey/submitAnswer', '\application\controllers\SurveyController@submitAnswerAction', 'submitAnswerAction');
$router->map('POST', '/SurveyCreate', '\application\controllers\SurveyController@createAction', 'createAction');
$router->map('GET', '/survey/delete/[i:id]', '\application\controllers\SurveyController@deleteAction', 'deleteAction');
$router->map('GET', '/survey/edit/[i:id]', '\application\controllers\SurveyController@editAction', 'editAction');
$router->map('POST', '/survey/edit/[i:id]', '\application\controllers\SurveyController@editAction', 'editActionPost');
$router->map('GET', '/surveys/sort/status', '\application\controllers\SurveyController@getAllSurveysAction', 'sortStatusAction');
$router->map('GET', '/surveys/sort/date_create', '\application\controllers\SurveyController@getAllSurveysAction', 'sortDateCreateAction');
$router->map('GET', '/surveys/sort/title', '\application\controllers\SurveyController@getAllSurveysAction', 'sortTitleAction');
$router->map('GET', '/home/sort/status', '\application\controllers\HomeController@getAllSurveysAction', 'sortStatusAction');
$router->map('GET', '/home/sort/date_create', '\application\controllers\HomeController@getAllSurveysAction', 'sortDateCreateAction');
$router->map('GET', '/home/sort/title', '\application\controllers\HomeController@getAllSurveysAction', 'sortTitleAction');

$match = $router->match();
if ($match) {
    list($controller, $action) = explode('@', $match['target']);
    $controllerObject = new $controller();
    $controllerObject->$action(isset($match['params']['id']) ? $match['params']['id'] : null);
} else {
    // Обработка ошибки 404
    header("HTTP/1.0 404 Not Found");
    echo 'Страница не найдена!';
}