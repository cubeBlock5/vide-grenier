<?php

namespace App\Controllers;

use App\Config;
use App\Model\UserRegister;
use App\Models\Articles;
use App\Utility\Flash;
use App\Utility\Hash;
use \Core\View;
use Exception;
use http\Env\Request;
use http\Exception\InvalidArgumentException;

/**
 * User controller
 */
class User extends \Core\Controller
{

    /**
     * Affiche la page de login
     */
    public function loginAction()
    {
        if(isset($_POST['submit'])){
            $f = $_POST;

            if($this->login($f)){
                header('Location: /account');
                die;
            }

            Flash::danger("Email ou mot de passe incorrect.");
        }

        View::renderTemplate('User/login.html');
    }

    /**
     * Page de création de compte
     */
    public function registerAction()
    {
        if(isset($_POST['submit'])){
            $f = $_POST;

            if($f['password'] !== $f['password-check']){
                Flash::danger("Les mots de passe ne correspondent pas.");
                View::renderTemplate('User/register.html');
                die;
            }

            if($this->register($f)){
                // Connecte automatiquement l'utilisateur après la création de son compte
                if($this->login($f)){
                    header('Location: /account');
                    die;
                }

                // Le compte est créé mais l'auto-connexion a échoué : direction la page de login
                header('Location: /login');
                die;
            }

            Flash::danger("Une erreur est survenue lors de la création du compte.");
        }

        View::renderTemplate('User/register.html');
    }

    /**
     * Affiche la page du compte
     */
    public function accountAction()
    {
        $articles = Articles::getByUser($_SESSION['user']['id']);

        View::renderTemplate('User/account.html', [
            'articles' => $articles
        ]);
    }

    /*
     * Fonction privée pour enregister un utilisateur
     */
    private function register($data)
    {
        try {
            // Generate a salt, which will be applied to the during the password
            // hashing process.
            $salt = Hash::generateSalt(32);

            $userID = \App\Models\User::createUser([
                "email" => $data['email'],
                "username" => $data['username'],
                "password" => Hash::generate($data['password'], $salt),
                "salt" => $salt
            ]);

            return $userID;

        } catch (Exception $ex) {
            Flash::danger($ex->getMessage());
            return false;
        }
    }

    private function login($data){
        try {
            if(empty($data['email']) || empty($data['password'])){
                return false;
            }


            $user = \App\Models\User::getByLogin($data['email']);

            if (!$user || Hash::generate($data['password'], $user['salt']) !== $user['password']) {
                return false;
            }

            session_regenerate_id(true);

            $_SESSION['user'] = array(
                'id' => $user['id'],
                'username' => $user['username'],
            );

            // Si "Se souvenir de moi" est coché, on prolonge la durée de vie du
            // cookie de session (par défaut, il expire à la fermeture du
            // navigateur) afin que l'utilisateur reste connecté.
            if (!empty($data['remember'])) {
                $duration = 60 * 60 * 24 * 30; // 30 jours
                ini_set('session.gc_maxlifetime', $duration);
                $params = session_get_cookie_params();
                setcookie(session_name(), session_id(), time() + $duration,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }

            return true;

        } catch (Exception $ex) {
            Flash::danger($ex->getMessage());
            return false;
        }
    }


    /**
     * Logout: Delete cookie and session. Returns true if everything is okay,
     * otherwise turns false.
     * @access public
     * @return boolean
     * @since 1.0.2
     */
    public function logoutAction() {

        // Destroy all data registered to the session.

        $_SESSION = array();

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        header ("Location: /");

        return true;
    }

}
