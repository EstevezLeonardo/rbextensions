<?php

/**
 * Endpoint JSON: devolve as vendas (tabela `vendas`, com o cliente via
 * JOIN — ver App\Entity\Venda::getVendas) do período pedido, paginadas
 * (10 por página). Consumido por dashboard/financeiro.php, na lista que
 * permite marcar uma venda como extornada.
 *
 * Aceita via querystring, os mesmos filtros de dashboard/financeiro-resumo.php:
 *   - de:  data inicial (AAAA-MM-DD), inclusive
 *   - ate: data final (AAAA-MM-DD), inclusive
 *   - pagina: página atual (padrão 1)
 *
 * Devolve { vendas, paginaAtual, totalPaginas }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Venda;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

$de = trim($_GET['de'] ?? '');
$ate = trim($_GET['ate'] ?? '');

$condicoes = [];
$params = [];

if ($de !== '') {
    $condicoes[] = 'v.Data >= ?';
    $params[] = $de.' 00:00:00';
}

if ($ate !== '') {
    $condicoes[] = 'v.Data <= ?';
    $params[] = $ate.' 23:59:59';
}

$where = implode(' AND ', $condicoes);

$total = Venda::getTotalVendas($where, $params);
$paginacao = new Pagination($total, $_GET['pagina'] ?? 1, 10);

$vendas = Venda::getVendas($where, $params, $paginacao->getLimit());

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'vendas' => $vendas,
    'paginaAtual' => $paginacao->getCurrentPage(),
    'totalPaginas' => $paginacao->getTotalPages(),
]);
