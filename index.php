<?php

require 'vendor/autoload.php';

use App\Entity\Vaga;

$busca = isset($_GET['busca']) ? htmlspecialchars(trim($_GET['busca']), ENT_QUOTES, 'UTF-8') : '';

$condicao = !empty($busca) ? "nome LIKE '%{$busca}%' OR sobrenome LIKE '%{$busca}%'" : '';

$vagas = Vaga::getVagas($condicao);

include 'includes/header.php';
include 'includes/footer.php';
include 'includes/listagem.php';