<?php

/**
 * Tela e processamento do login.
 *
 * Se já houver uma sessão válida, Login::requireLogout() manda o
 * usuário direto para o dashboard. Caso contrário, mostra o
 * formulário e, ao receber o POST, valida o token CSRF, confere
 * email/senha (com password_verify) e abre a sessão.
 */

require 'vendor/autoload.php';


use App\Session\Login;
use App\Session\Csrf;



Login::requireLogout();

$mensagem = '';
if(isset($_POST['acao']) && $_POST['acao'] === 'Fazer Login') {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        $mensagem = "Sessão expirada. Tente novamente.";
    } else {
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        // busca por email usando placeholder (?) para evitar SQL injection
        $obVaga = new \App\Entity\Vaga();
        $usuario = $obVaga->getVagas('email = ?', null, null, [$email])[0] ?? null;

        if ($usuario && password_verify($senha, $usuario->senha)) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario->id;
            header('Location: dashboard/index.php');
            exit;
        } else {
            // Login falhou
            $mensagem = "Credenciais inválidas. Tente novamente.";
        }
    }
}


include 'includes/header.php';
include 'includes/formulario-login.php';
include 'includes/footer.php';
