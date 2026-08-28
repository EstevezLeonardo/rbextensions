<?php

/**
 * Endpoint JSON: devolve as compras de produtos da tabela
 * `compras_produtos`, paginadas (10 por página). Consumido pelo card
 * "Compra de Produtos" de dashboard/estoque.php (ver
 * dashboard/src/estoque.ts) — não é uma página pra visitar pelo menu,
 * só uma fonte de dados.
 *
 * Aceita via querystring, vindos da caixa "Buscar Compras":
 *   - busca:  texto a procurar na categoria ou no fornecedor (LIKE)
 *   - de:     data inicial (AAAA-MM-DD), inclusive
 *   - ate:    data final (AAAA-MM-DD), inclusive
 *   - pagina: página atual (padrão 1)
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\CompraProduto;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

$busca = trim($_GET['busca'] ?? '');
$de = trim($_GET['de'] ?? '');
$ate = trim($_GET['ate'] ?? '');

$condicoes = [];
$params = [];

if ($busca !== '') {
    $condicoes[] = '(Categoria LIKE ? OR Fornecedor LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($de !== '') {
    $condicoes[] = 'Data >= ?';
    $params[] = $de.' 00:00:00';
}

if ($ate !== '') {
    $condicoes[] = 'Data <= ?';
    $params[] = $ate.' 23:59:59';
}

$where = implode(' AND ', $condicoes);

$total = CompraProduto::getTotalCompras($where, $params);
$paginacao = new Pagination($total, $_GET['pagina'] ?? 1, 10);

$compras = CompraProduto::getCompras($where, $params, $paginacao->getLimit());

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'compras' => $compras,
    'paginaAtual' => $paginacao->getCurrentPage(),
    'totalPaginas' => $paginacao->getTotalPages(),
]);
