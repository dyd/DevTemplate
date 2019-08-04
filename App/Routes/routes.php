<?php

/** @var \Slim\Container $container */

$app->add(new \App\Middleware\StaticPathMiddleware($container));

//=============== OPEN PAGES ===============//
$app->get('/', 'HomeController:landingPage')->setName('landing.page');
$app->get('/changeLanguage/{id}', 'UserController:changeLanguage')->setName(('language.change'));
$app->get('/signout', 'AuthController:signOut')->setName('signOut');
//=============== AUTH PAGES ===============//
$app->get('/signin', 'AuthController:getSignIn')->setName('auth.signIn')
    ->add(new \App\Middleware\GuestMiddleware($container));
$app->post('/signin', 'AuthController:postSignIn');
$app->get('/auth/setPass/{id}', 'UserController:userResetPassPage')->setName('user.page.setPass');
$app->post('/auth/setPass', 'UserController:userAjaxResetPass')->setName('user.ajax.setPass');
$app->post('/auth/forgottenPass', 'UserController:userAjaxForgottenPass')->setName('user.ajax.forgottenPass');
//=============== END AUTH ===============//

//=============== END OPEN PAGES ===============//
// Logged
$app->group('', function () use ($container) {

    $this->map(['GET', 'POST'], '/auth/passExpire', 'UserController:userExpirePassPage')->setName('user.page.expirePass');

    $this->get('/dashboard', 'HomeController:home')->setName('home');

    //=============== TAB USER ===============//

    $this->group('/users', function () use ($container) {
        $this->group('', function () use ($container) {

            //User add new
            $this->get('/add', 'UserController:getUserAdd')->setName('user.add');
            $this->post('/add', 'UserController:userAjaxAdd');

            //User Delete
            $this->post('/delete', 'UserController:userAjaxDelete')->setName('user.ajax.delete');
        })->add(new \App\Middleware\RightsCRUDMiddleware($container));

        $this->group('', function () use ($container) {

            //User Reset Password
            $this->post('/resetPass', 'UserController:userAjaxResetPassByUser')->setName('user.ajax.resetPass');

            //User Edit
            $this->get('/edit/{id}', 'UserController:getUserEdit')->setName('user.edit');
            $this->map(['GET', 'POST'], '/editRecord/{id}', 'UserController:userAjaxEdit')->setName('user.ajax.edit');

        })->add(new \App\Middleware\RightsEDITMiddleware($container));

        $this->group('', function () use ($container) {

            //Events
            $this->get('/events', 'UserController:usersEvents')->setName('users.events');
            $this->post('/events', 'UserController:usersAjaxEvents');

            //User List Table
            $this->get('/', 'UserController:userList')->setName('user.list');
            $this->post('/', 'UserController:userAjaxList')->setName('user.ajax.list');

            //User page
            $this->get('/{id}', 'UserController:userPage')->setName('user.page');

        })->add(new \App\Middleware\RightsVIEWMiddleware($container));
    })->add(new \App\Middleware\UserModuleMiddleware($container));

    //=============== END USER ===============//

    //=============== TAB LANGUAGES ===============//

    $this->group('/translations', function () use ($container){
        $this->group('', function () use ($container) {
            $this->get('', 'LanguageController:languagePage')->setName('languages');
            $this->post('', 'LanguageController:getLanguages');
        })->add(new \App\Middleware\RightsVIEWMiddleware($container));

        $this->group('', function () use ($container) {
            $this->get('/edit/{id}', 'LanguageController:editLanguagePage')->setName('language.edit');
            $this->post('/edit/{id}', 'LanguageController:editLanguage');
        })->add(new \App\Middleware\RightsEDITMiddleware($container));

        $this->group('', function () use ($container) {
            $this->get('/add', 'LanguageController:addLanguagePage')->setName('language.add');
            $this->post('/add', 'LanguageController:addLanguage');
            $this->post('/delete', 'LanguageController:deleteLanguage')->setName('language.delete');
        })->add(new \App\Middleware\RightsCRUDMiddleware($container));
    })->add(new \App\Middleware\UserModuleMiddleware($container));

    //=============== END LANGUAGES ===============//

})->add(new \App\Middleware\AuthMiddleware($container));