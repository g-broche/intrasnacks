<?php

namespace core;

require_once '../vendor/autoload.php';

class BaseController
{
    private $twig;

    public function __construct()
    {
        $loader = new \Twig\Loader\FilesystemLoader('../templates');
        $this->twig = new \Twig\Environment($loader);
        $this->twig->addGlobal('session', $_SESSION);
        unset($_SESSION["message"]);
        $this->twig->addExtension(new \Twig\Extension\DebugExtension());
    }

    public function render($name, $data = [])
    {
        echo $this->twig->render($name, $data);
    }
}
