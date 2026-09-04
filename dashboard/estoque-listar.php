<?php

/**
 * Endpoint JSON: devolve as movimentações de estoque da tabela
 * `movimentacoes_estoque` (com o produto de cada uma via JOIN),
 * paginadas (10 por página). Consumido por dashboard/estoque.php (ver
 * dashboard/src/estoque.ts) — não é uma página pra visitar pelo menu,
 * só uma fonte de dados.
 *
 * Aceita via querystring, vindos da caixa "Buscar Movimentações":
 *   - busca:     texto a procurar no nome ou no código do produto (LIKE)
 *   - tipo:      "entrada" ou "saida"
 *   - categoria: categoria do produto (igual às de App\Entity\Produto::getCategorias())
 *   - pagina:    página atual (padrão 1)
 *
 * Devolve { movimentacoes, paginaAtual, totalPaginas }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\MovimentacaoEstoque;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

$busca = trim($_GET['busca'] ?? '');
$tipo = trim($_GET['tipo'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$condicoes = [];
$params = [];

if ($busca !== '') {
    $condicoes[] = '(p.Nome LIKE ? OR p.Codigo LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($tipo === 'entrada' || $tipo === 'saida') {
    $condicoes[] = 'm.Tipo = ?';
    $params[] = $tipo;
}

if ($categoria !== '') {
    $condicoes[] = 'p.Categoria = ?';
    $params[] = $categoria;
}

$where = implode(' AND ', $condicoes);

$total = MovimentacaoEstoque::getTotalMovimentacoes($where, $params);
$paginacao = new Pagination($total, $_GET['pagina'] ?? 1, 10);

$movimentacoes = MovimentacaoEstoque::getMovimentacoes($where, $params, $paginacao->getLimit());

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'movimentacoes' => $movimentacoes,
    'paginaAtual' => $paginacao->getCurrentPage(),
    'totalPaginas' => $paginacao->getTotalPages(),
]);
