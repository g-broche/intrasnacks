<?php

namespace src\controllers;

use core\BaseController;
use src\models\Product;
use src\models\User;
use src\models\Sale;

class ServeUserController extends BaseController
{
    private $userModel;
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User;
        $this->productModel = new Product;
    }

    public function apiGetProductsForUser(int $userId)
    {
        $this->productModel->getProductsAndFavourites($userId);
        return json_encode($this->productModel->getInfos());
    }

    public function toggleFavorite(string $userToken, int $productId, bool $isNowFavourite)
    {
        $userExist = $this->userModel->getUserIdFromToken($userToken);
        if ($userExist) {
            $this->productModel->setId($productId);
            if ($isNowFavourite) {
                $this->userModel->addFavourite($this->productModel->getId());
                return json_encode(['success' => true, 'productId' => $productId, 'isNowFavourite' => true, 'message' => 'article ajouté aux favoris']);
            } else {
                $this->userModel->removeFavourite($this->productModel->getId());
                return json_encode(['success' => true, 'productId' => $productId, 'isNowFavourite' => false, 'message' => 'article retiré des favoris']);
            }
        } else {
            return json_encode(['success' => false, 'message' => 'jeton de connection invalide, merci de vous reconnecter']);
        }
    }

    private function formatHistoryData(array $data)
    {
        $sortedArray = [];
        $lastId = null;
        $index = 0;
        foreach ($data as $rowEntry) {
            if ($rowEntry['id'] != $lastId) {
                $index++;
                $lastId = $rowEntry['id'];
                $sortedArray[$index] = ['summary' => $this->getSaleGlobalSummary($rowEntry), 'content' => []];
                array_push($sortedArray[$index]['content'], $this->getSaleRow($rowEntry));
            } else {
                array_push($sortedArray[$index]['content'], $this->getSaleRow($rowEntry));
            }
        }
        return $sortedArray;
    }

    private function getSaleGlobalSummary(array $rawRow)
    {
        return [
            'id' => $rawRow['id'],
            'date' => $rawRow['date'],
            'total_quantity' => $rawRow['total_quantity'],
            'total_cost' => $rawRow['total_cost'],
        ];
    }

    private function getSaleRow(array $rawRow)
    {
        return [
            'name' => $rawRow['name'],
            'quantity' => $rawRow['quantity'],
            'unit_cost' => $rawRow['unit_cost'],
            'cost' => $rawRow['cost'],
            'image_name' => $rawRow['image_name']
        ];
    }

    public function getUserHistory(string $userToken)
    {
        $data = null;
        $userExist = $this->userModel->getUserIdFromToken($userToken);
        if ($userExist) {
            $sale = new Sale;
            $rawData = $sale->getHistory($this->userModel->getId());
            if ($rawData) {
                $data = $this->formatHistoryData($rawData['infos']);
                return json_encode(['success' => true, 'infos' => $data]);
            } else {
                return json_encode(['success' => false, 'error' => 'aucun historique pour cet utilisateur ou une erreur a eu lieu']);
            }
        } else {
            return json_encode(['success' => false, 'error' => 'erreur serveur']);
        }
    }
}
