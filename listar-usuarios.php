<?php

require 'vendor/autoload.php';

use App\Entity\Vaga;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogout();

define('TITLE', 'Usuários');

$busca = isset($_GET['busca']) ? htmlspecialchars(trim($_GET['busca']), ENT_QUOTES, 'UTF-8') : '';

$status = isset($_GET['status']) ? htmlspecialchars(trim($_GET['status']), ENT_QUOTES, 'UTF-8') : '';

$ativo = isset($_GET['ativo']) ? htmlspecialchars(trim($_GET['ativo']), ENT_QUOTES, 'UTF-8') : '';

$filtroativo = in_array($status, ['s', 'n']) ? $status : (in_array($ativo, ['s', 'n']) ? $ativo : '');

$condicoes = [];

if (!empty($busca)) {
    $condicoes[] = "(nome LIKE '%{$busca}%' OR sobrenome LIKE '%{$busca}%')";
}

if ($filtroativo === 's' || $filtroativo === 'n') {
    $condicoes[] = "ativo = '{$filtroativo}'";
}

$where = implode(' AND ', $condicoes);

$totalVagas = Vaga::getTotalVagas($where);

$obPagination = new Pagination($totalVagas, $_GET['pagina'] ?? 1, 5);


$vagas = Vaga::getVagas($where,null, $obPagination->getLimit());

include 'includes/header.php';
include 'includes/footer.php';
include 'includes/listagem.php';
