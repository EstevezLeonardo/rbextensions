<?php

namespace App\Session;

class Csrf{

    private static function startSession(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function token(){
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validate($token){
        self::startSession();
        return isset($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }

}
