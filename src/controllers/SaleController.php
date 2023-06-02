<?php

namespace src\controllers;

use core\BaseController;
use src\models\Sale;
use src\models\Product;
use src\models\User;

class SaleController extends BaseController
{
    private $model;
    private array $cartList;

    public function __construct()
    {
        parent::__construct();
        $this->model = new Sale;
    }

    private function calculateSingleSaleCost(Product $product, int $amount)
    {
        $cost = $amount * $product->getInfos()['credit_cost'];
        return $cost;
    }

    private function createSingleSaleReceipt(User $user, Product $product, int $amount, int $cost): array
    {
        $resultInsSale = $this->model->insertSale($user->getId(), $amount, $cost);
        $saleId = $this->model->getLastSaleId();
        $this->model->setId($saleId);
        $resultInsSaleRow = $this->model->insertSaleRow($saleId, $product, $amount);
        return ["success" => ($resultInsSale && $resultInsSaleRow), "lastestId" => $saleId];
    }

    private function compileSaleReturn($data)
    {
        $parsedData = [
            'product' => [
                'id' => $data['productId'],
                'stock' => $data['stock']
            ],
            'client' => [
                'solde' => $data['userSolde']
            ],
            'sale' => [
                'id' => $data['saleId'],
                'date' => $data['date'],
                'totalCost' => $data['total_cost'],
            ]
        ];
        return $parsedData;
    }

    public function orderAProduct(string $userToken, int $productId, int $amount)
    {

        if (is_numeric($amount) && $amount > 0) {
            $client = new User;
            $isClientFound = $client->getUserIdFromToken($userToken);
            $product = new Product;
            $product->setId($productId);
            $product->getOne();
            if ($isClientFound) {
                $cost = $this->calculateSingleSaleCost($product, $amount);
                $this->model->startTransaction();
                $resultDecreaseSolde = $client->decreaseSolde($cost);
                if ($resultDecreaseSolde) {
                    if ($product->isOrderedAmountAllowed($amount)) {
                        $resultCreateSale = $this->createSingleSaleReceipt($client, $product, $amount, $cost);
                        $resultConsumeProduct = $product->substractUnits($amount);
                        if ($resultCreateSale && $resultConsumeProduct) {
                            $this->model->commitTransaction();
                            $infosToParse = $this->model->getSaleReturnData($this->model->getId(), $client->getId())['infos'];
                            $parsedInfos = $this->compileSaleReturn($infosToParse);
                            return json_encode(['success' => true, 'infos' => $parsedInfos]);
                        } else {;
                            $this->model->rollbackTransaction();
                            return json_encode(['success' => false, 'infos' => 'erreur lors de la requête serveur']);
                        }
                    } else {
                        $this->model->rollbackTransaction();
                        return json_encode(['success' => false, 'infos' => 'erreur : pas assez de stock ou le maximum autorisé pour un produit(' . $product::maxQuantity . ') est dépassé']);
                    }
                } else {
                    $this->model->rollbackTransaction();
                    return json_encode(['success' => false, 'infos' => 'le solde actuel de l\'utilisateur ne permet pas cet achat.']);
                }
            }
        } else {
            return json_encode(['success' => false, 'infos' => 'erreur utilisateur indéterminé.']);
        }
    }
}
