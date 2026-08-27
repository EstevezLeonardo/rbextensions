<?php

namespace App\Session;

use App\Entity\Vaga;

class Login{

    private static function startSession(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function isLogged(){
        self::startSession();

        if (!isset($_SESSION['usuario_id'])) {
            return false;
        }

        $usuario = Vaga::getVaga($_SESSION['usuario_id']);
        if (!$usuario instanceof Vaga) {
            unset($_SESSION['usuario_id']);
            return false;
        }

        return true;
    }

    public static function requireLogin(){
        if(!self::isLogged()){
            header('location: /rbextensions/login.php');
            exit;
        }
    }

    public static function requireLogout(){
        if(self::isLogged()){
            header('location: /rbextensions/dashboard/index.php');
            exit;
        }
    }

    public static function logout(){
        self::startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'], $params['secure'], $params['httponly']
            );
        }

        session_destroy();
    }

}
