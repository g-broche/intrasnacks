<?php

namespace src\controllers;

use core\BaseController;

class LoginController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login()
    {
        $this->render('login.html.twig');
    }
}
