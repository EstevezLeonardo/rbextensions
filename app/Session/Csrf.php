<?php

namespace App\Session;

/**
 * Proteção contra CSRF (Cross-Site Request Forgery).
 *
 * Gera um token secreto guardado na sessão do usuário e exposto como
 * campo escondido nos formulários. Ao receber o POST, o valor enviado
 * é comparado com o da sessão — como um site externo não tem como
 * conhecer esse token, ele não consegue montar um envio válido em
 * nome do usuário.
 */
class Csrf{

    /** Inicia a sessão do PHP apenas se ainda não houver uma ativa. */
    private static function startSession(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Retorna o token CSRF da sessão atual, gerando um novo
     * (aleatório, criptograficamente seguro) na primeira vez.
     */
    public static function token(){
        self::startSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Confere se $token (normalmente vindo de $_POST) bate com o
     * token guardado na sessão. Usa hash_equals para comparar em
     * tempo constante e evitar timing attacks.
     */
    public static function validate($token){
        self::startSession();
        return isset($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }

}
