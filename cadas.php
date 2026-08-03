<?php


require 'vendor/autoload.php';

use App\Entity\Vaga;

if(isset($_POST['nome'],$_POST['sobrenome'],$_POST['email'],$_POST['datanascimento'])) {
    $obVaga = new Vaga();
    $obVaga->nome = $_POST['nome'];
    $obVaga->sobrenome = $_POST['sobrenome'];
    $obVaga->email = $_POST['email'];
    $obVaga->datanascimento = $_POST['datanascimento'];
    $obVaga->senha = $_POST['senha'];
    $obVaga->cadastrar();

    // Aqui você pode salvar a vaga no banco de dados ou realizar outras operações
}

include 'includes/header.php';
include 'includes/footer.php';
include 'includes/formulario.php';