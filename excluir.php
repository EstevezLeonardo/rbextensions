<?php

require 'vendor/autoload.php';

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

if(isset($_POST['excluir'])) {

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        header('location: listar-usuarios.php?status=error');
        exit;
    }

    $obVaga->excluir();

    header('location: listar-usuarios.php?status=success');
    exit;
}

include 'includes/header.php';
include 'includes/confirmar-exclusao.php';
include 'includes/footer.php';