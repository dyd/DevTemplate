<?php
/**
 * Created by PhpStorm.
 * User: dnatzkin
 * Date: 26.6.2018 г.
 * Time: 13:05
 */

namespace App\TwigCustomExtensions;

class AddSettingsVars extends \Twig_Extension
{
    /**
     * @var array
     */
    protected $twig_settings;

    public function __construct($twig_settings)
    {
        $this->twig_settings = $twig_settings;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_Settings', array($this, 'getSettings')),
            new \Twig_SimpleFunction('get_Setting', array($this, 'getSetting')),
        ];
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        if (array_key_exists('twig', $this->twig_settings)) {
            return $this->twig_settings;
        }

        return [];
    }

    /**
     * @param string $index
     * @return string
     */
    public function getSetting($index)
    {
        if (array_key_exists($index, $this->twig_settings)) {
            return $this->twig_settings[$index];
        }

        return 'undefined';

    }
}