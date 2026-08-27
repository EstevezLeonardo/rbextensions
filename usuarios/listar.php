<?php

/**
 * Listagem de usuários, com busca por nome/sobrenome, filtro por
 * status (ativo/inativo) e paginação. Exige login. É a página que
 * concentra os links para cadastrar (via dashboard), editar e excluir.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Vaga;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

define('TITLE', 'Usuários');

// escapado com htmlspecialchars por segurança ao reexibir no <input> de busca;
// a query em si é protegida à parte, via placeholders (?) mais abaixo
$busca = isset($_GET['busca']) ? htmlspecialchars(trim($_GET['busca']), ENT_QUOTES, 'UTF-8') : '';

$status = isset($_GET['status']) ? htmlspecialchars(trim($_GET['status']), ENT_QUOTES, 'UTF-8') : '';

$ativo = isset($_GET['ativo']) ? htmlspecialchars(trim($_GET['ativo']), ENT_QUOTES, 'UTF-8') : '';

$filtroativo = in_array($status, ['s', 'n']) ? $status : (in_array($ativo, ['s', 'n']) ? $ativo : '');

$condicoes = [];
$params = [];

if (!empty($busca)) {
    $condicoes[] = '(nome LIKE ? OR sobrenome LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($filtroativo === 's' || $filtroativo === 'n') {
    $condicoes[] = 'ativo = ?';
    $params[] = $filtroativo;
}

$where = implode(' AND ', $condicoes);

$totalVagas = Vaga::getTotalVagas($where, $params);

$obPagination = new Pagination($totalVagas, $_GET['pagina'] ?? 1, 5);


$vagas = Vaga::getVagas($where, null, $obPagination->getLimit(), $params);

include 'includes/header.php';
include 'includes/listagem.php';
include 'includes/footer.php';
