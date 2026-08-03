<?php
$nome = '';
$sobrenome = '';
$email = '';
$datanascimento = '';
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $sobrenome = isset($_POST['sobrenome']) ? trim($_POST['sobrenome']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $datanascimento = isset($_POST['datanascimento']) ? trim($_POST['datanascimento']) : '';
    $senha = isset($_POST['senha']) ? trim($_POST['senha']) : '';
    $submitted = true;
}
require 'vendor/autoload.php';
include 'includes/header.php';
include 'includes/footer.php';
include 'includes/listagem.php';