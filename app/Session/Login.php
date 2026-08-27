<?php

namespace App\Session;

use App\Entity\Vaga;

/**
 * Controla a sessão de autenticação do usuário: verificar se está
 * logado, proteger páginas que exigem (ou exigem não ter) login, e
 * encerrar a sessão (logout).
 */
class Login{

    /** Inicia a sessão do PHP apenas se ainda não houver uma ativa. */
    private static function startSession(){
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Verifica se existe um login válido: precisa haver um
     * `usuario_id` na sessão E esse id precisa corresponder a um
     * usuário que ainda existe de fato no banco. Se o id da sessão
     * apontar para um usuário que não existe mais (ex: foi excluído),
     * a sessão é limpa e o resultado é "não logado".
     */
    public static function isLogged(){
        return self::getUsuario() !== null;
    }

    /**
     * Devolve o usuário atualmente logado (Vaga), ou null se não
     * houver sessão válida. Faz a mesma checagem de isLogged() e
     * limpa a sessão da mesma forma quando o id não corresponde a um
     * usuário real.
     */
    public static function getUsuario(){
        self::startSession();

        if (!isset($_SESSION['usuario_id'])) {
            return null;
        }

        $usuario = Vaga::getVaga($_SESSION['usuario_id']);
        if (!$usuario instanceof Vaga) {
            unset($_SESSION['usuario_id']);
            return null;
        }

        return $usuario;
    }

    /** Bloqueia páginas que só podem ser acessadas logado; redireciona para o login caso contrário. */
    public static function requireLogin(){
        if(!self::isLogged()){
            header('location: /rbextensions/login.php');
            exit;
        }
    }

    /** Bloqueia páginas que só fazem sentido deslogado (ex: a própria tela de login); redireciona para o dashboard caso já esteja logado. */
    public static function requireLogout(){
        if(self::isLogged()){
            header('location: /rbextensions/dashboard/index.php');
            exit;
        }
    }

    /**
     * Encerra a sessão do usuário: limpa os dados da sessão, remove o
     * cookie de sessão do navegador e destrói o arquivo de sessão no
     * servidor.
     */
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
