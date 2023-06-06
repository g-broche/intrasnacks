<?php

namespace src\models;

use core\BaseModel;
use PDO;
use src\models\Product as Product;

class Sale extends BaseModel
{
    public function __construct()
    {
        $this->table = "sales";
        $this->getConnection();
    }

    public function setId($id)
    {
        if (is_numeric($id) && $id > 0) {
            $this->id = $id;
        }
    }

    public function insertSale($userId, $totalQuantity, $totalCost)
    {
        $sql = "INSERT INTO " . $this->table . " (user_id, total_cost, total_quantity) VALUES (:userId, :cost, :quantity)";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->bindParam(':cost', $totalCost, PDO::PARAM_INT);
        $query->bindParam(':quantity', $totalQuantity, PDO::PARAM_INT);
        $query->execute();
        return ($query->rowCount() > 0);
    }

    public function getLastSaleId()
    {
        $sql = "SELECT LAST_INSERT_ID() FROM " . $this->table;
        $query = parent::$_connection->prepare($sql);
        $query->execute();
        $lastId = $query->fetch(PDO::FETCH_ASSOC)['LAST_INSERT_ID()'];
        return $lastId;
    }

    public function insertSaleRow(int $saleId, Product $product, int $quantity): bool
    {
        $cost = $product->getInfos()['credit_cost'] * $quantity;
        $sql = "INSERT INTO sale_rows (sale_id, product_id, quantity, unit_cost, cost) VALUES (:saleId, :productId, :quantity, :unitCost, :cost)";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':saleId', $saleId, PDO::PARAM_INT);
        $query->bindParam(':productId', $product->getInfos()['id'], PDO::PARAM_INT);
        $query->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $query->bindParam(':unitCost', $product->getInfos()['credit_cost'], PDO::PARAM_INT);
        $query->bindParam(':cost', $cost, PDO::PARAM_INT);
        $query->execute();
        return ($query->rowCount() > 0);
    }

    public function getSaleReturnData($saleId, $userId)
    {
        $sql = "SELECT TU.solde AS 'userSolde', TS.id AS 'saleId', TS.date,
        TS.total_cost, TP.id AS 'productId', TP.stock
        FROM sales TS, users TU, sale_rows TSR, products TP
        WHERE TU.id = TS.user_id AND TS.id = TSR.sale_id AND TSR.product_id = TP.id
        AND TS.id = :saleId AND TS.user_id=:userId";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(':saleId', $saleId, PDO::PARAM_INT);
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return ['success' => true, 'infos' => $result];
        } else {
            return ['success' => false, 'infos' => null];
        }
    }

    public function getHistory($userId): array
    {
        $sql = "SELECT TS.id, TS.date, TS.total_quantity, TS.total_cost, TP.name, TSR.quantity, TSR.unit_cost, TSR.cost
        FROM sales TS, sale_rows TSR, products TP 
        WHERE TS.id=TSR.sale_id AND TSR.product_id=TP.id AND TS.user_id = :userId
        ORDER BY TS.id";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(':userId', $userId, PDO::PARAM_INT);
        $query->execute();
        $result = $query->fetchAll(PDO::FETCH_ASSOC);
        if ($result) {
            return ['success' => true, 'infos' => $result];
        } else {
            return ['success' => false, 'infos' => null];
        }
    }
}
