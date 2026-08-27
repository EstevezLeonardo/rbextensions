<?php

/**
 * Endpoint POST que cria um produto (tabela `produtos`) a partir do
 * formulário "Adicionar Produto" de dashboard/controle-produtos.php.
 * Recebe JSON: { codigo, nome, descricao, categoria, preco,
 * quantidade, ativo, csrf_token }.
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Produto;
use App\Session\Login;
use App\Session\Csrf;

Login::requireLogin();

header('Content-Type: application/json; charset=utf-8');

$dados = json_decode(file_get_contents('php://input'), true) ?? [];

if (!Csrf::validate($dados['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['erro' => 'Sessão expirada. Recarregue a página e tente novamente.']);
    exit;
}

if (empty($dados['codigo']) || empty($dados['nome']) || !isset($dados['preco']) || !isset($dados['quantidade'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Preencha código, nome, preço e quantidade.']);
    exit;
}

if (!is_numeric($dados['preco']) || $dados['preco'] < 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Preço inválido.']);
    exit;
}

if (!is_numeric($dados['quantidade']) || $dados['quantidade'] < 0) {
    http_response_code(422);
    echo json_encode(['erro' => 'Quantidade inválida.']);
    exit;
}

if (Produto::codigoExiste($dados['codigo'])) {
    http_response_code(422);
    echo json_encode(['erro' => 'Já existe um produto com esse código.']);
    exit;
}

$produto = new Produto();
$produto->Codigo = $dados['codigo'];
$produto->Nome = $dados['nome'];
$produto->Descricao = $dados['descricao'] ?? '';
$produto->Categoria = $dados['categoria'] ?? '';
$produto->Preco = $dados['preco'];
$produto->Quantidade = (int) $dados['quantidade'];
$produto->Ativo = ($dados['ativo'] ?? 's') === 'n' ? 'n' : 's';
$produto->cadastrar();

echo json_encode($produto);
