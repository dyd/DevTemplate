<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 25.6.2018 г.
 * Time: 15:44
 */

namespace App\Controllers;

class Controller
{
    protected $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function __get($property)
    {
        if ($this->container->{$property}) {
            return $this->container->{$property};
        }
    }
}