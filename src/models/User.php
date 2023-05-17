<?php

namespace src\models;

use core\lib\Utils;
use core\BaseModel;
use PDO;

class User extends BaseModel
{
    const maxCreditAmount = 100;
    private $soldNameDB = "solde";
    private $creationInfos = [
        'last_name' => "",
        'first_name' => "",
        'email' => "",
        'telephone' => "",
        'password' => 0,
        'solde' => 0,
        'is_admin' => 0,
    ];

    public function __construct()
    {
        $this->table = "users";
        $this->getConnection();
    }

    public function setId($id)
    {
        if (is_numeric($id) && $id > 0) {
            $this->id = $id;
        } else {
            return false;
        }
    }

    public function setCreationInfos(
        string $lastName,
        string $firstName,
        string $email,
        string $phone,
        string $password,
        int $solde,
        int $isAdmin
    ): bool {
        if (
            Utils::isStringName($lastName) &&
            Utils::isStringName($firstName) &&
            Utils::isStringEmail($email) &&
            Utils::isStringPhone($phone) &&
            Utils::isStringPassword($password) &&
            Utils::isNumberValidInt($solde, 0, self::maxCreditAmount) &&
            Utils::isNumberValidInt($isAdmin, 0, 1)
        ) {
            $this->creationInfos['last_name'] = $lastName;
            $this->creationInfos['first_name'] = $firstName;
            $this->creationInfos['email'] = $email;
            $this->creationInfos['telephone'] = $phone;
            $this->creationInfos['password'] = password_hash($password, PASSWORD_DEFAULT);
            $this->creationInfos['solde'] = $solde;
            $this->creationInfos['is_admin'] = $isAdmin;

            return true;
        } else {
            return false;
        }
    }

    public function getLoggingUserPassword(string $email)
    {
        $sql = "SELECT `password` FROM " . $this->table . " WHERE email= :inputedMail";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(':inputedMail', $email, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            return ['mailExists' => true, 'password' => $result['password']];
        } else {
            return ['mailExists' => false, null];
        }
    }

    public function setLoggingUserInfos(string $email): bool
    {
        $sql = "SELECT * FROM " . $this->table . " WHERE email = :inputedMail";
        $query = self::$_connection->prepare($sql);
        $query->bindParam(':inputedMail', $email, PDO::PARAM_STR);
        $query->execute();
        $result = $query->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $this->infos = $result;
            return true;
        } else {
            return false;
        }
    }

    public function createUser()
    {
        $sql = "INSERT INTO " . $this->table . " (last_name, first_name, email, telephone, `password`, solde, is_admin ) 
        values (:lastName , :firstName, :email, :telephone, :pass, :solde, :isAdmin)";
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':lastName',  $this->creationInfos['last_name']);
        $query->bindParam(':firstName', $this->creationInfos['first_name']);
        $query->bindParam(':email', $this->creationInfos['email']);
        $query->bindParam(':telephone', $this->creationInfos['telephone']);
        $query->bindParam(':pass', $this->creationInfos['password']);
        $query->bindParam(':solde', $this->creationInfos['solde'], PDO::PARAM_INT);
        $query->bindParam(':isAdmin', $this->creationInfos['is_admin'], PDO::PARAM_INT);
        $query->execute();
        if ($query->rowCount() > 0) {
            $successState = true;
        } else {
            $successState = false;
        }
        return $successState;
    }

    public function increaseSolde($amount)
    {
        $successState = false;
        $sql = "UPDATE " . $this->table . " SET " . $this->soldNameDB . " = (" . $this->soldNameDB . " + :amount)
        WHERE id= " . $this->id . " and " . $this->soldNameDB . " + :amount <= " . self::maxCreditAmount;
        $query = parent::$_connection->prepare($sql);
        $query->bindParam(':amount', $amount, PDO::PARAM_INT);
        $query->execute();
        if ($query->rowCount() > 0) {
            $successState = true;
        } else {
            $successState = false;
        }
        return $successState;
    }
}
