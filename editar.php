<?php

require 'vendor/autoload.php';

define('TITLE', 'Editar Usuário');

use App\Entity\Vaga;

if(!isset($_GET['id']) or !is_numeric($_GET['id'])) {
    header('location: index.php?status=error');
    exit;
}
$obVaga = Vaga::getVaga($_GET['id']);

if(!$obVaga instanceof Vaga) {
    header('location: index.php?status=error');
    exit;
}

if(isset($_POST['nome'],$_POST['sobrenome'],$_POST['email'],$_POST['datanascimento'])) {
    
    $obVaga->nome = $_POST['nome'];
    $obVaga->sobrenome = $_POST['sobrenome'];
    $obVaga->email = $_POST['email'];
    $obVaga->datanascimento = $_POST['datanascimento'];
    
    $obVaga->atualizar();

    header('location: index.php?status=success');
    exit;
}

include 'includes/header.php';
include 'includes/footer.php';
include 'includes/formulario.php';