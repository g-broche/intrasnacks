<?php

namespace src\controllers;

use core\BaseController;
use src\models\Resupply;
use src\models\Product;

class ResupplyController extends BaseController
{
    private $model;
    private array $commandList;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Resupply;
    }

    private function calculateSingleOrderCost(Product $product, int $amount)
    {
        return $amount * $product->getInfos()['price_restock'];
    }

    public function createSingleOrderReceipt(Product $product, int $amount)
    {

        $cost = $this->calculateSingleOrderCost($product, $amount);
        $resultInsResupply = $this->model->insertResupply(1, $amount, $cost);
        $commandId = $this->model->getLastResupplyId();
        $resultInsResupplyLine = $this->model->insertResupplyLine($commandId, $product, $amount);
        return ($resultInsResupply && $resultInsResupplyLine);
    }

    public function orderAProduct(int $productId, int $amount)
    {
        if (is_numeric($amount) && $amount > 0) {
            $product = new Product;
            $product->setId($productId);
            $product->getOne();
            if ($product->isResupplyAmountAllowed($amount)) {
                $this->model->startTransaction();
                $resultCreateReceipt = $this->createSingleOrderReceipt($product, $amount);
                $resultIncreaseProduct = $product->increaseStockQuantity($amount);
                if ($resultCreateReceipt && $resultIncreaseProduct) {
                    $this->model->commitTransaction();
                } else {
                    $this->model->rollbackTransaction();
                }
            }
        }
    }
}
