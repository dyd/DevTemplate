<?php

/** @var array $data */
return [
    'settings' => [
        'displayErrorDetails' => true,
        'addContentLengthHeader' => false,

        //Database
        'database' => $data
    ],
    'twig' => [
        'fileTypes' => \App\Utils::returnAllowedFileTypes(),
    ],
];