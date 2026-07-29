<?php
$nome = '';
$sobrenome = '';
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = isset($_POST['nome']) ? trim($_POST['nome']) : '';
    $sobrenome = isset($_POST['sobrenome']) ? trim($_POST['sobrenome']) : '';
    $submitted = true;
}
include 'includes/header.php';
include 'includes/footer.php';
