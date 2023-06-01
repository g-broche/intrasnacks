<?php

namespace src\controllers;

use core\BaseController;
use src\models\Product;
use src\models\User;

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
}
