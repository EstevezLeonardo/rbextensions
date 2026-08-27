<?php

require 'vendor/autoload.php';

define('TITLE', 'Cadastrar Usuário');

use App\Entity\Vaga;
use App\Session\Csrf;

$obVaga = new Vaga();

if(isset($_POST['nome'],$_POST['sobrenome'],$_POST['email'],$_POST['datanascimento'])) {
    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        header('location: cadas.php?status=error');
        exit;
    }

    $obVaga = new Vaga();
    $obVaga->nome = $_POST['nome'];
    $obVaga->sobrenome = $_POST['sobrenome'];
    $obVaga->email = $_POST['email'];
    $obVaga->datanascimento = $_POST['datanascimento'];
    $obVaga->senha = $_POST['senha'];
    $obVaga->ativo = $_POST['ativo'];
    $obVaga->cadastrar();

    header('location: listar-usuarios.php?status=success');
    exit;
}

include 'includes/header.php';
include 'includes/formulario.php';
include 'includes/footer.php';