<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 26.6.2018 г.
 * Time: 11:51
 */

$container = $app->getContainer();

$database = new dbaccess($container['settings']['database']);

$container['database'] = function ($container) use ($database) {
    return $database;
};

//-------- LOADS CURRENT BROWSING USER -------------//
$container['user'] = function ($container) use ($database) {
    return \App\Managers\User::getInstance($database)->loadUserFromSession(session_id());
};

//-------- TRANSLATION MANAGER -------------------//
/** @var $user \App\User\User */
$user = $container['user'];

$user->chooseLanguage($container->get('request'));

\App\Managers\Translation::initialize($user->getLanguage(), $database);
$translation = \App\Managers\Translation::getInstance($database);

$container['translation'] = function ($container) use ($translation) {
    return $translation;
};

//-------- Flash Messages -------------//
$container['flash'] = function ($container) {
    return new \Slim\Flash\Messages();
};

//-------- LOAD TWIG TEMPLATES -------------//
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(dirname(__DIR__) . '/html/', [
        'cache' => false,
        'debug' => true
    ]);

    $view->addExtension(new \Slim\Views\TwigExtension(
        $container->router,
        $container->request->getUri()
    ));

    $view->addExtension(new App\TwigCustomExtensions\AddSettingsVars($container['twig']));
    $view->addExtension(new App\TwigCustomExtensions\LoadCurrentLanguage($container));
    $view->addExtension(new Twig_Extension_Debug());

    $view->getEnvironment()->addGlobal('flash', $container->flash);

    return $view;
};


//-------- LOAD Manager UserModule -------------//
\App\Managers\UserModule::initialize($container);
/** @var \App\User\User $user */
$user = $container['user'];

$user_module = \App\Managers\UserModule::getInstance($user->getId(), $database);

$container['user_module'] = function ($container) use ($user_module) {
    return $user_module;
};

$container['manager_user'] = function ($container) {
    return \App\Managers\User::getInstance($container['database']);
};

$container['manager_mail'] = function ($container) {
    \App\Managers\Mail::initialize($container);
    return \App\Managers\Mail::getInstance();
};

require 'dependencies_controllers.php';