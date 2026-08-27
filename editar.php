<?php

require 'vendor/autoload.php';

define('TITLE', 'Editar Usuário');

use App\Entity\Vaga;
use App\Session\Login;
use App\Session\Csrf;

Login::requireLogin();

if(!isset($_GET['id']) or !is_numeric($_GET['id'])) {
    header('location: listar-usuarios.php?status=error');
    exit;
}
$obVaga = Vaga::getVaga($_GET['id']);

if(!$obVaga instanceof Vaga) {
    header('location: listar-usuarios.php?status=error');
    exit;
}

if(isset($_POST['nome'],$_POST['sobrenome'],$_POST['email'],$_POST['datanascimento'])) {

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        header('location: listar-usuarios.php?status=error');
        exit;
    }

    $obVaga->nome = $_POST['nome'];
    $obVaga->sobrenome = $_POST['sobrenome'];
    $obVaga->email = $_POST['email'];
    $obVaga->datanascimento = $_POST['datanascimento'];
    $obVaga->ativo = $_POST['ativo'];

    $obVaga->atualizar();

    header('location: listar-usuarios.php?status=success');
    exit;
}

include 'includes/header.php';
include 'includes/formulario.php';
include 'includes/footer.php';