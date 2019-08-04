<?php

function getRootDir()
{
    return dirname(__DIR__);
}

require dirname(__DIR__) . '/vendor/autoload.php';

//LOAD ENVIRONMENT VARIABLES
$dotenv = new Dotenv\Dotenv(dirname(__DIR__));
$dotenv->load();

$data = [
    'dbhost' => getenv('DB_HOST'),
    'dbport' => getenv('DB_PORT'),
    'dbuser' => getenv('DB_USER'),
    'dbpass' => getenv('DB_PASS'),
    'dbname' => getenv('DB_NAME')
];

//LOAD SESSION
$session_h = new session($data);

session_set_save_handler(
    array($session_h, 'open'),
    array($session_h, 'close'),
    array($session_h, 'read'),
    array($session_h, 'write'),
    array($session_h, 'destroy'),
    array($session_h, 'gc')
);
session_name("vcstats");
session_start();

$settings = require_once dirname(__DIR__) . '/inc/settings.php';
$app = new \Slim\App($settings);

//LOAD CONTROLLERS
require dirname(__DIR__) . '/App/dependencies.php';

/** @var \Slim\Container $container */
//Middleware
$app->add(new \App\Middleware\ErrorsMiddleware($container));

//LOAD ROUTES
require dirname(__DIR__) . '/App/Routes/routes.php';