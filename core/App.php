<?php

namespace core;

use core\lib\Utils;
use src\controllers\ProductController;
use src\controllers\ResupplyController;
use src\controllers\UserController;

class App
{
    public function __construct()
    {
        session_start();
    }
    public function run()
    {

        // var_dump($_SESSION);
        // die;
        $uri = strtok($_SERVER['REQUEST_URI'], '?');

        /* ***** API ***** */
        if ($uri == '/api/products') {
            $controller = new ProductController();
            header('Content-Type: application/json');
            header("Access-Control-Allow-Origin: *");
            $controller->apiGetProducts();
        } elseif ($uri == '/api/product/consume') {
            $controller = new ProductController();
            if (isset($_GET['id'])) {
                $result = $controller->consume($_GET['id']);
                if ($result['success']) {
                    header('Content-Type: application/json');
                    header("Access-Control-Allow-Origin: *");
                    echo $result['infos'];
                } else {
                    echo "error";
                }
            }
        } else {

            if (isset($_SESSION['username'])) {
                /* base product list page*/
                if ($uri == '/' || $uri == '/index') {
                    header("location: /products/index");
                } elseif ($uri == '/products/index') {
                    $controller = new ProductController();
                    $controller->index();

                    /* change product form page */
                } elseif ($uri == '/product/edit') {
                    if (isset($_GET['id'])) {
                        $controller = new ProductController();
                        $controller->edit($_GET['id']);
                    } else {
                        header("location: /products/index");
                    }

                    /* change product information in DB */
                } elseif ($uri == '/product/update') {
                    if (isset($_POST['modifyId']) && isset($_POST['newCost'])) {
                        $controller = new ProductController();
                        $controller->modifyProductCost($_POST['modifyId'], $_POST['newCost']);
                    } else {
                        header("location: /products/index");
                    }
                    /* restock product form page */
                } elseif ($uri == '/product/order') {
                    if (isset($_GET['id'])) {
                        $controller = new ProductController();
                        $controller->order($_GET['id']);
                    } else {
                        header("location: /products/index");
                    }
                    /* restock product in DB */
                } elseif ($uri == '/product/ordersupply') {
                    if (isset($_POST['orderId']) && isset($_POST['orderQuantity'])) {
                        $controller = new ResupplyController();
                        $controller->orderAProduct($_POST['orderId'], $_POST['orderQuantity']);
                        header("location: /products/index");
                    } else {
                        header("location: /products/index");
                    }
                } elseif ($uri == '/products/resupply') {
                    $controller = new ProductController();
                    $controller->resupply();

                    /* update product in DB */
                } elseif ($uri == '/product/disable') {
                    if (isset($_GET['id'])) {
                        $controller = new ProductController();
                        $controller->disable($_GET['id']);
                    } else {
                        header("location: /products/index");
                    }
                } elseif ($uri == '/product/enable') {
                    if (isset($_GET['id'])) {
                        $controller = new ProductController();
                        $controller->enable($_GET['id']);
                    } else {
                        header("location: /products/index");
                    }
                } elseif ($uri == '/product/create') {
                    $controller = new ProductController();
                    $controller->create();
                } elseif ($uri == '/product/create-new') {
                    $controller = new ProductController();
                    $controller->createNew($_POST);
                } elseif ($uri == '/users/index') {
                    $controller = new UserController();
                    $controller->index();
                } elseif ($uri == '/user/add') {
                    $controller = new UserController();
                    $controller->add();
                } elseif ($uri == '/user/add-new') {
                    $controller = new UserController();
                    $controller->addNew($_POST);
                } elseif ($uri == '/user/edit') {
                    $controller = new UserController();
                    $controller->edit($_GET['id']);
                } elseif ($uri == '/user/increaseSolde') {
                    $controller = new UserController();
                    $controller->increaseUserSolde($_POST);


                    /* ***** Logout and Login redirect if session true***** */
                } elseif ($uri == '/logout') {
                    session_destroy();
                    echo "<script>window.location.href='/login'</script>";
                } elseif ($uri == '/login') {
                    header("location: /products/index");
                } else {
                    echo "derp";
                }


                /* ***** Authentification ***** */
            } else if ($uri == '/auth') {
                $controller = new UserController();
                $isLoginProcessing = $controller->checkAuthForm($_POST);
                if ($isLoginProcessing) {
                    $controller->auth($_POST['loginEmail'], $_POST['loginPassword']);
                } else {
                    header("location: /login");
                }
            } elseif ($uri == '/login') {
                $controller = new UserController();
                $controller->login();
            } else {
                header("location: /login");
            }
        }
    }
}