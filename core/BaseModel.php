<?php

namespace core;

use PDO;
use PDOException;

class BaseModel
{
    private $host = 'localhost';
    private $db_name = 'society_benefits';
    private $username = 'root';
    private $password = '';

    protected static $_connection;

    protected $table;
    protected $id;
    protected $infos;

    public function getConnection()
    {
        self::$_connection = null;

        try {
            self::$_connection = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            self::$_connection->exec("set names utf8");
            self::$_connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }
    }

    public function startTransaction()
    {
        self::$_connection->beginTransaction();
    }
    public function commitTransaction()
    {
        self::$_connection->commit();
    }
    public function rollbackTransaction()
    {
        self::$_connection->rollback();
    }

    public function setId($value)
    {
        $this->id = $value;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getInfos()
    {
        return $this->infos;
    }

    public function getOne()
    {
        $sql = "SELECT * FROM " . $this->table . " WHERE id=:id";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(':id', $this->id, PDO::PARAM_INT);
        $query->execute();
        $this->infos = $query->fetch(PDO::FETCH_ASSOC);
    }
    public function getAll()
    {
        $sql = "SELECT * FROM " . $this->table;
        $query = self::$_connection->prepare($sql);
        $query->execute();
        $this->infos =  $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
