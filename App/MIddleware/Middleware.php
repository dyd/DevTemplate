<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 27.6.2018 г.
 * Time: 14:58
 */

namespace App\Middleware;

class Middleware
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