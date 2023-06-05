<?php

namespace src\models;

use core\BaseModel;
use core\lib\Utils;
use PDO;

class Product extends BaseModel
{
    const maxCreditCost = 50;
    const maxOrderAmount = 3;
    const maxQuantity = 100;
    private $stockDBName = "stock";
    private $availabilityDBName = "is_available";
    private $availabilityValues = [0, 1];
    private $creationInfos = [
        'name' => null,
        'stock' => 0,
        'credit_cost' => null,
        'price_restock' => null,
        'is_available' => 0
    ];

    public function __construct()
    {
        $this->table = "products";
        $this->getConnection();
    }

    // public function setId($id)
    // {
    //     if (is_numeric($id) && $id > 0) {
    //         $this->id = $id;
    //     } else {
    //         return false;
    //     }
    // }

    public function getByName(string $searchPart)
    {
        $sql = "SELECT * FROM " . $this->table . " WHERE name LIKE %:namePart%";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(":namePart", $searchPart);
        $query->execute();
        $this->infos =  $query->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setCreationInfos(string $name, int $stock, int $creditCost, $restockPrice, int $isAvailable): bool
    {

        if (
            Utils::isStringName($name) &&
            Utils::isNumberValidInt($stock, 0, self::maxQuantity) &&
            Utils::isNumberValidInt($creditCost, 0, self::maxCreditCost) &&
            Utils::isNumberValid($restockPrice, 0) &&
            Utils::isNumberValidInt($isAvailable, 0, 1)
        ) {
            $this->creationInfos['name'] = $name;
            $this->creationInfos['stock'] = $stock;
            $this->creationInfos['credit_cost'] = $creditCost;
            $this->creationInfos['price_restock'] = $restockPrice;
            $this->creationInfos['is_available'] = $isAvailable;
            return true;
        } else {
            return false;
        }
    }

    public function getStockDBName()
    {
        return $this->stockDBName;
    }

    public function getStock()
    {
        return $this->getInfos()[$this->stockDBName];
    }
    public function setStock($newStock)
    {
        $this->infos[$this->stockDBName] = $newStock;
    }
    public function getAvailabilityValues()
    {
        return $this->availabilityValues;
    }

    public function allowCreditChange($value)
    {
        return ($value > 0 && $value <= self::maxCreditCost);
    }

    public function isResupplyAmountAllowed($amount)
    {
        if ($this->infos != null) {
            return (self::maxQuantity >= ($this->getStock() + $amount));
        } else {
            return false;
        }
    }

    public function isOrderedAmountAllowed($amount)
    {
        if ($this->infos != null) {
            $isInputValid = Utils::isNumberValidInt($amount, 1, self::maxOrderAmount);
            if ($isInputValid) {
                return ($this->getStock() > 0 && $this->getStock() >= $amount);
            } else {
                return false;
            }
        }
    }

    public function increaseStockQuantity($amount)
    {
        $newStock = $this->getStock() + $amount;
        return $this->updateStock($newStock);
    }

    /* ***** REQUESTS ***** */

    public function updateCreditCost($newValue)
    {
        if ($this->allowCreditChange($newValue)) {
            $sql = "UPDATE " . $this->table . " SET credit_cost = :newValue WHERE id= " . $this->id;
            $query = parent::$_connection->prepare($sql);
            $query->bindParam(':newValue', $newValue, PDO::PARAM_INT);
            $query->execute();
            if ($query->rowCount() > 0) {
                $successState = true;
            } else {
                $successState = false;
            }
            return $successState;
        }
    }

    public function updateStock(int $newQuantity)
    {
        $sql = "UPDATE " . $this->table . " SET " . $this->stockDBName . " = :newQuantity WHERE id= " . $this->id;
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':newQuantity', $newQuantity, PDO::PARAM_INT);
        $query->execute();
        return ($query->rowCount() > 0);
    }

    public function setAvailability(int $newValue)
    {
        $sql = "UPDATE " . $this->table . " SET " . $this->availabilityDBName . " = :newValue WHERE id= " . $this->id;
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':newValue', $newValue, PDO::PARAM_INT);
        $query->execute();
        if ($query->rowCount() > 0) {
            $successState = true;
        } else {
            $successState = false;
        }
        return $successState;
    }

    public function createProduct()
    {
        $sql = "INSERT INTO `products` (`name`, `stock`, credit_cost, price_restock, is_available ) values (:productName, " .
            $this->creationInfos['stock'] . " , " . $this->creationInfos['credit_cost'] . " , " .
            $this->creationInfos['price_restock'] . " , " . $this->creationInfos['is_available'] . ")";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':productName', $this->creationInfos['name']);
        $query->execute();
        if ($query->rowCount() > 0) {
            $successState = true;
        } else {
            $successState = false;
        }
        return $successState;
    }
    public function substractUnits($quantity)
    {
        $sql = "UPDATE " . $this->table . " SET " . $this->stockDBName . " = (" . $this->stockDBName . "-:quantity) WHERE id= " . $this->id . " AND " . $this->stockDBName . " >= :quantity";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $query->execute();
        if ($query->rowCount() > 0) {
            $successState = true;
        } else {
            $successState = false;
        }
        return $successState;
    }

    public function getProductsAndFavourites(int $userId)
    {
        $sql = "SELECT TP.*, CASE WHEN TF.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_favourite
        FROM products TP 
        LEFT JOIN  favourite_list TF ON TP.id = TF.product_id AND TF.user_id=:userId";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->execute();
        $this->infos =  $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
