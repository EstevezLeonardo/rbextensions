<?php

/**
 * Endpoint JSON: devolve os produtos da tabela `produtos`. Consumido
 * por dashboard/controle-produtos.php (ver
 * dashboard/src/controle-produtos.ts) — não é uma página pra visitar
 * pelo menu, só uma fonte de dados.
 *
 * Aceita dois filtros opcionais via querystring, vindos da caixa
 * "Buscar Produtos":
 *   - busca:  texto a procurar no nome ou no código (LIKE)
 *   - status: "s" (ativo) ou "n" (inativo)
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Produto;
use App\Session\Login;

Login::requireLogin();

$busca = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');

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

$where = implode(' AND ', $condicoes);

$produtos = Produto::getProdutos($where, $params);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($produtos);
