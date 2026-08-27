<?php

/**
 * Endpoint JSON: devolve os produtos da tabela `produtos`, paginados
 * (10 por página). Consumido por dashboard/controle-produtos.php (ver
 * dashboard/src/controle-produtos.ts) — não é uma página pra visitar
 * pelo menu, só uma fonte de dados.
 *
 * Aceita via querystring, vindos da caixa "Buscar Produtos":
 *   - busca:     texto a procurar no nome ou no código (LIKE)
 *   - status:    "s" (ativo) ou "n" (inativo)
 *   - categoria: nome exato de uma categoria
 *   - pagina:    página atual (padrão 1)
 *
 * Devolve { produtos, paginaAtual, totalPaginas }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Produto;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

$busca = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');

$condicoes = [];
$params = [];

if ($busca !== '') {
    $condicoes[] = '(Nome LIKE ? OR Codigo LIKE ?)';
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($status === 's' || $status === 'n') {
    $condicoes[] = 'Ativo = ?';
    $params[] = $status;
}

if ($categoria !== '') {
    $condicoes[] = 'Categoria = ?';
    $params[] = $categoria;
}

$where = implode(' AND ', $condicoes);

$total = Produto::getTotalProdutos($where, $params);
$paginacao = new Pagination($total, $_GET['pagina'] ?? 1, 10);

$produtos = Produto::getProdutos($where, $params, $paginacao->getLimit());

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'produtos' => $produtos,
    'paginaAtual' => $paginacao->getCurrentPage(),
    'totalPaginas' => $paginacao->getTotalPages(),
]);
