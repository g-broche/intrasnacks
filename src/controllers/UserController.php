<?php

namespace src\controllers;

use core\BaseController;
use src\models\User;
use core\lib\Utils;

class UserController extends BaseController
{
    private $model;

    public function __construct()
    {
        parent::__construct();
        $this->model = new User;
    }


    public function login()
    {
        $headerTitle = "Se connecter";

        $this->render('login.html.twig', ['pageTitle' => $headerTitle]);
    }

    public function index()
    {
        $headerTitle = "liste personnel";
        $this->model->getAll();

        $this->render('users/index.html.twig', ['pageTitle' => $headerTitle, 'userList' => $this->model->getInfos()]);
    }

    public function add()
    {
        $headerTitle = "ajout utilisateur";

        $this->render('users/add.html.twig', ['pageTitle' => $headerTitle]);
    }

    public function edit($id)
    {
        $headerTitle = "ajout utilisateur";
        $this->model->setId($id);
        $this->model->getOne();
        $this->render('users/edit.html.twig', ['pageTitle' => $headerTitle, 'user' => $this->model->getInfos(), 'maxCredit' => $this->model::maxCreditAmount]);
    }

    public function checkAuthForm(array $args): bool
    {
        if (isset($args['loginEmail']) && isset($args['loginPassword'])) {
            return (Utils::isStringEmail($args['loginEmail']) && Utils::isStringPassword($args['loginPassword']));
        } else {
            return false;
        }
    }

    private function validateUserLogin(string $email, string $password): bool
    {
        $resultPass = $this->model->getLoggingUserPassword($email);
        if ($resultPass['mailExists']) {
            $hashedPassword = $resultPass['password'];
            return Utils::passwordVerify($password, $hashedPassword);
        } else {
            return false;
        }
    }

    private function isUserAdmin(): bool
    {
        return ($this->model->getInfos()['is_admin'] == 1);
    }

    public function auth(string $email, string $password)
    {
        $loggingIsValid = $this->validateUserLogin($email, $password);
        if ($loggingIsValid) {
            $this->model->setLoggingUserInfos($email);
            if ($this->isUserAdmin()) {
                $_SESSION['username'] = $this->model->getInfos()['first_name'];
                $_SESSION['id'] = $this->model->getInfos()['id'];
                echo "<script>window.location.href='/login'</script>";
            } else {
                $_SESSION['message'] = "vous n'avez pas les droits";
                echo "<script>window.location.href='/login'</script>";
            }
        } else {
            $_SESSION['message'] = "informations incorrectes";
            echo "<script>window.location.href='/login'</script>";
        }
    }

    public function areCreateInputsSet(array $inputs)
    {
        return (isset($inputs['lastName']) && isset($inputs['firstName']) && isset($inputs['email']) &&
            isset($inputs['phone']) && isset($inputs['password']) && isset($inputs['solde']) && isset($inputs['adminStatus']));
    }

    public function addNew(array $inputs)
    {

        $message = "";
        if ($this->areCreateInputsSet($inputs)) {
            $insertSuccess = false;
            $successState = $this->model->setCreationInfos(
                $inputs['lastName'],
                $inputs['firstName'],
                $inputs['email'],
                $inputs['phone'],
                $inputs['password'],
                $inputs['solde'],
                $inputs['adminStatus']
            );

            if ($successState) {

                $insertSuccess = $this->model->createUser();
                $message = ($insertSuccess ? "L'utilisateur " . $inputs['firstName'] . " " . $inputs['lastName'] . " a bien été ajouté !" : "erreur lors de l'insertion");
            } else {
                $message = "erreur : des infos du formulaires sont érronées";
            }
        } else {
            $message = "Certaines des informations requises n'ont pas été fournies";
        }
        $_SESSION['message'] = $message;
        if ($insertSuccess) {
            header("location: /users/index");
        } else {
            header("location: /user/add");
        }
    }

    private function areIncreaseInputsValid(array $array)
    {
        return (Utils::isNumberValidInt($array['id'], 0) && Utils::isNumberValidInt($array['addAmount'], 0, $this->model::maxCreditAmount));
    }

    public function increaseUserSolde(array $array)
    {
        if ($this->areIncreaseInputsValid($array)) {
            $this->model->setId($array['id']);
            $success = $this->model->increaseSolde($array['addAmount']);
            if ($success) {
                $this->model->getOne();
                $_SESSION['message'] = "le solde de l'utilisateur " . $this->model->getOne()['first_name'] . " " . $this->model->getOne()['last_name'] .
                    " a bien été crédité de " . $array['addAmount'] . " points.";
                header("location: /users/index");
                return;
            } else
                $_SESSION['message'] = "erreur de mise à jour : vérifier que le solde ne soit pas déjà au maximum autorisé.";
            header("location: /user/edit?id=" . $array['id']);
            return;
        }
        $_SESSION['message'] = "erreur, le formulaire est invalide.";
        header("location: /user/edit?id=" . $array['id']);
        return;
    }

    /* ***** API RELATED ***** */

    public function giveClientToken(string $email, string $password)
    {
        $response = null;
        $loggingIsValid = $this->validateUserLogin($email, $password);
        if ($loggingIsValid) {
            $this->model->giveClientToken($email);
            $response = ['success' => true, 'clientToken' => $this->model->getInfos()['token']];
        } else {
            $response = ['success' => false, 'clientToken' => null];
        }
    }

    public function getApiClientInfo(string $email, string $password)
    {

        $loggingIsValid = $this->validateUserLogin($email, $password);
        if ($loggingIsValid) {
            $this->model->setLoggingUserInfos($email);
            $clientInfos = ["firstName" => $this->model->getInfos()["first_name"], "lastName" => $this->model->getInfos()["last_name"], "solde" => $this->model->getInfos()["solde"], "token" => $this->model->getInfos()["token"]];
            $response = json_encode(["success" => true, "clientInfos" => $clientInfos]);
        } else {
            $response = json_encode(["success" => false, "error" => "les informations envoyées sont erronnées"]);
        }
        return $response;
    }
}
