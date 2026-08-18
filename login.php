<?php

require 'vendor/autoload.php';


use App\Session\Login;



Login::requireLogout();

if(isset($_POST['acao']) && $_POST['acao'] === 'Fazer Login') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $obVaga = new \App\Entity\Vaga();
    $usuario = $obVaga->getVagas("email = '$email'")[0] ?? null;

    $mensagem = '';
    if ($usuario && password_verify($senha, $usuario->senha)) {
        // Login bem-sucedido
        session_start();
        $_SESSION['usuario_id'] = $usuario->id;
        header('Location: listar-usuarios.php');
        exit;
    } else {
        // Login falhou
        $mensagem = "Credenciais inválidas. Tente novamente.";
    }
}


include 'includes/header.php';
include 'includes/footer.php';
include 'includes/formulario-login.php'; 