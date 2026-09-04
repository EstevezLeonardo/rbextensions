<?php

/**
 * Endpoint JSON: devolve o extrato de vendas (um item de venda por
 * linha, com cliente/produto/data/valor total via JOIN — ver
 * app/Entity/VendaItem.php), paginado (10 por página). Consumido por
 * dashboard/vendas.php (ver dashboard/src/vendas.ts) — não é uma
 * página pra visitar pelo menu, só uma fonte de dados.
 *
 * Aceita via querystring, vindo da caixa "Buscar no Extrato":
 *   - busca:    texto a procurar no nome do cliente ou no nome/código do produto (LIKE)
 *   - pagina:   página atual (padrão 1)
 *   - venda_id: em vez de busca, mostra só os itens dessa venda
 *               específica (vindo do botão "Venda" de dashboard/index.php
 *               ou do link "Ver extrato completo" de dashboard/vendas.php)
 *
 * Devolve { itens, paginaAtual, totalPaginas }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\VendaItem;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

$busca = trim($_GET['busca'] ?? '');
$vendaId = $_GET['venda_id'] ?? '';

$condicoes = [];
$params = [];

if ($vendaId !== '' && ctype_digit((string) $vendaId)) {
    $condicoes[] = 'vi.VendaId = ?';
    $params[] = $vendaId;
} elseif ($busca !== '') {
    $condicoes[] = "(CONCAT(u.nome, ' ', u.sobrenome) LIKE ? OR p.Nome LIKE ? OR p.Codigo LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$where = implode(' AND ', $condicoes);

$total = VendaItem::getTotalExtrato($where, $params);
$paginacao = new Pagination($total, $_GET['pagina'] ?? 1, 10);

$itens = VendaItem::getExtrato($where, $params, $paginacao->getLimit());

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'itens' => $itens,
    'paginaAtual' => $paginacao->getCurrentPage(),
    'totalPaginas' => $paginacao->getTotalPages(),
]);
