<?php

namespace src\controllers;

use core\BaseController;
use src\models\Product;
use src\models\ServeUser;

class ProductController extends BaseController
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Product;
    }

    public function index()
    {

        $headerTitle = "Produits";
        $this->model->getAll();

        $this->render('products/index.html.twig', ['pageTitle' => $headerTitle, 'productList' => $this->model->getInfos()]);
    }
    public function edit($id)
    {
        $headerTitle = "modifier produit";
        $this->model->setId($id);
        $this->model->getOne();

        $this->render('products/edit.html.twig', ['pageTitle' => $headerTitle, 'product' => $this->model->getInfos()]);
    }
    public function availability($id)
    {
        $headerTitle = "disponibilité produit";
        $this->model->setId($id);
        $this->model->getOne();

        $this->render('products/availability.html.twig', ['pageTitle' => $headerTitle, 'product' => $this->model->getInfos()]);
    }

    public function order($id)
    {
        $headerTitle = "Recommander un produit";
        $this->model->setId($id);
        $this->model->getOne();

        $this->render('products/order.html.twig', ['pageTitle' => $headerTitle, 'maxAmount' => $this->model::maxQuantity, 'product' => $this->model->getInfos()]);
    }
    public function resupply()
    {
        $headerTitle = "recommander";
        $this->render('products/resupply.html.twig', ['pageTitle' => $headerTitle]);
    }

    public function create()
    {
        $headerTitle = "ajouter produit";
        $this->render('products/create.html.twig', ['pageTitle' => $headerTitle]);
    }

    public function modifyProductCost(int $id, int $value)
    {
        $message = "";
        if (is_numeric($value) && $value > 0) {
            $this->model->setId($id);
            if ($this->model->allowCreditChange($value)) {
                $this->model->updateCreditCost($value);
                $message = "le changement a bien été effectué";
            } else {
                $message = "une erreur a eu lieu";
            }
            $_SESSION["message"] = $message;
            header("location: /products/index");
        }
    }


    public function disable(int $id)
    {
        $this->model->setId($id);
        $this->model->setAvailability(0);
        header("location: /products/index");
    }
    public function enable(int $id)
    {
        $this->model->setId($id);
        $this->model->setAvailability(1);
        header("location: /products/index");
    }

    public function areCreateInputsSet(array $inputs)
    {
        return (isset($inputs['name']) && isset($inputs['stock']) && isset($inputs['creditCost']) &&
            isset($inputs['price']) && isset($inputs['availability']));
    }

    public function createNew(array $inputs)
    {

        $message = "";
        if ($this->areCreateInputsSet($inputs)) {
            $this->model = new Product;
            $insertSuccess = false;
            $successState = $this->model->setCreationInfos(
                $inputs['name'],
                $inputs['stock'],
                $inputs['creditCost'],
                $inputs['price'],
                $inputs['availability']
            );
            if ($successState) {

                $insertSuccess = $this->model->createProduct();
                $message = ($insertSuccess ? "Le produit " . $inputs['name'] . " a bien été ajouté !" : "erreur lors de l'insertion");
            } else {
                $message = "erreur : des infos du formulaires sont érronées";
            }
        } else {
            $message = "Certaines des informations requises n'ont pas été fournies";
        }
        $_SESSION['message'] = $message;
        if ($insertSuccess) {
            header("location: /products/index");
        } else {
            header("location: /product/create");
        }
    }

    /* ***** API RELATED ***** */

    public function apiSearchProducts(string $query)
    {

        $this->model->getAll();
        return json_encode($this->model->getInfos());
    }

    public function consume(int $id, int $quantity = 1)
    {
        if ($quantity >= 1) {
            $this->model->setId($id);
            $successState = false;
            $successState = $this->model->substractUnits($quantity);
            if ($successState) {
                $this->model->getOne();
                return json_encode(['success' => true, 'infos' => $this->model->getInfos()]);
            } else {
                return json_encode(['success' => false, 'infos' => null]);
            }
        }
    }
}
