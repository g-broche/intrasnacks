<?php

namespace src\models;

use core\BaseModel;
use PDO;
use src\models\Product as Product;

class Resupply extends BaseModel
{
    public function __construct()
    {
        $this->table = "resupplies";
        $this->getConnection();
    }

    public function setId($id)
    {
        if (is_numeric($id) && $id > 0) {
            $this->id = $id;
        }
    }

    public function insertResupply($agentId, $totalQuantity, $totalCost)
    {
        $sql = "INSERT INTO " . $this->table . " (agent_id, total_cost, total_quantity) VALUES (:agentId, :cost, :quantity)";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':agentId', $agentId, PDO::PARAM_INT);
        $query->bindParam(':cost', $totalQuantity, PDO::PARAM_INT);
        $query->bindParam(':quantity', $totalCost, PDO::PARAM_STR);
        $query->execute();
        return ($query->rowCount() > 0);
    }

    public function getLastResupplyId()
    {
        $sql = "SELECT LAST_INSERT_ID() FROM " . $this->table;
        $query = parent::$_connection->prepare($sql);
        $query->execute();
        $lastId = $query->fetch(PDO::FETCH_ASSOC)['LAST_INSERT_ID()'];
        return $lastId;
    }

    public function insertResupplyLine(int $resupplyId, Product $product, int $quantity)
    {
        $cost = $product->getInfos()['price_restock'] * $quantity;
        $sql = "INSERT INTO resupply_lines (resupply_id, product_id, quantity, unit_price, cost) VALUES (:resupplyId, :productId, :quantity, :unit_price, :cost)";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':resupplyId', $resupplyId, PDO::PARAM_INT);
        $query->bindParam(':productId', $product->getInfos()['id'], PDO::PARAM_INT);
        $query->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $query->bindParam(':unit_price', $product->getInfos()['price_restock'], PDO::PARAM_STR);
        $query->bindParam(':cost', $cost, PDO::PARAM_STR);
        $query->execute();
        return ($query->rowCount() > 0);
    }
}
