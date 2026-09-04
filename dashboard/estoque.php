<?php

/**
 * Estoque: registra entradas/saídas de produtos e mantém o histórico
 * de movimentações (tabela `movimentacoes_estoque`). Cada movimentação
 * ajusta a Quantidade do produto correspondente (ver
 * dashboard/estoque-criar.php e dashboard/estoque-excluir.php).
 *
 * Estrutura copiada de dashboard/controle-produtos.php (mesmas caixas
 * de Adicionar/Buscar e Resultados), sem o modo de edição — uma
 * movimentação só é criada ou excluída (excluir desfaz o efeito dela
 * no estoque), nunca editada, pra manter o histórico como um registro
 * confiável do que de fato aconteceu. Toda a lógica de tela está em
 * dashboard/public/assets/js/estoque.js (compilado de
 * dashboard/src/estoque.ts).
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Entity\Produto;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();
$produtos = Produto::getProdutos("Ativo = 's'");
$categorias = Produto::getCategorias();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estoque RB</title>
    <link rel="stylesheet" href="public/assets/css/all.css">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css?v=<?= filemtime(__DIR__.'/public/assets/css/style.css') ?>">
</head>
<body>

    <div class="container-fluid">
        <header class="head">

            <nav>
                <div class="logo">
                    <a href="index.php">
                        <img src="public/assets/images/Royal_Brazilian_Extensions_logo_transparente.png" alt="Logo">
                    </a>

                </div>
                <form action="" class="form-group">
                    <div class="rows">
                        <input type="text" name="search" class="form-control rounded-0" placeholder="Pesquisar opções">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </form>

                    <ul>
                        <li><a href="index.php"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href="perfil.php"><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href="servicos.php"><span><i class="fa-solid fa-envelope"></i></span>Correio</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="vendas.php"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>
                        <li><a href="estoque.php" class="actives"><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href="financeiro.php"><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
                    </ul>

            </nav>

        </header>
        <main>
            <div class="nav-top">
                    <div class="user-notification">
                        <a href="perfil.php" title="Ver perfil">
                            <button class="users">
                                <p>Olá, <span><?= htmlspecialchars($usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?></span></p>
                                <i class="fa-solid fa-user"></i>
                            </button>
                        </a>
                        <a href="servicos.php" title="Ver notificações">
                            <button class="notification">
                                <i class="fa-solid fa-bell"></i>
                                <span id="notificacoes-badge" class="escondido">0</span>
                            </button>
                        </a>
                        <a href="../usuarios/logout.php" title="Sair">
                            <button class="notification logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </a>
                    </div>

            </div>

            <!--
                Histórico das movimentações que batem com a busca abaixo,
                mais recente primeiro (mesmos dados de
                dashboard/estoque-listar.php). Cada item só tem o botão
                "Excluir" (duplo clique pra confirmar) — movimentação não
                se edita, se desfaz.
            -->
            <div class="cards">
                <div class="event-form-card event-list-card">
                    <h3>Histórico</h3>
                    <p id="lista-movimentacoes-mensagem" class="evento-mensagem"></p>
                    <ol id="lista-movimentacoes" class="lista-eventos"></ol>
                    <div id="paginacao-movimentacoes" class="paginacao"></div>
                </div>
            </div>

            <!--
                "Registrar Movimentação" grava na tabela
                `movimentacoes_estoque` via dashboard/estoque-criar.php e
                ajusta a Quantidade do produto escolhido. "Buscar
                Movimentações" filtra a lista acima
                (dashboard/estoque-listar.php) por produto e por tipo
                (entrada/saída). Toda a lógica está em
                dashboard/public/assets/js/estoque.js.
            -->
            <div class="cards">
                <div class="event-form-card">
                    <h3>Registrar Movimentação</h3>
                    <p id="movimentacao-mensagem" class="evento-mensagem"></p>
                    <form id="form-movimentacao">
                        <!-- token CSRF: confirmado em estoque-criar.php/estoque-excluir.php antes de gravar -->
                        <input type="hidden" id="movimentacao-csrf-token" value="<?= \App\Session\Csrf::token() ?>">

                        <div class="campo">
                            <label for="movimentacao-categoria-filtro">Categoria do produto</label>
                            <select id="movimentacao-categoria-filtro">
                                <option value="">Todas as categorias</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="movimentacao-produto">Produto</label>
                            <select id="movimentacao-produto" name="produtoId" required>
                                <option value="">Selecione um produto</option>
                                <?php foreach ($produtos as $produto): ?>
                                    <option value="<?= $produto->id ?>" data-categoria="<?= htmlspecialchars($produto->Categoria, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($produto->Codigo.' — '.$produto->Nome.' ('.$produto->Categoria.')', ENT_QUOTES, 'UTF-8') ?>
                                        (estoque atual: <?= (int) $produto->Quantidade ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <fieldset class="campo campo-radio">
                            <legend>Tipo</legend>
                            <label><input type="radio" name="tipo" value="entrada" checked> Entrada</label>
                            <label><input type="radio" name="tipo" value="saida"> Saída</label>
                        </fieldset>

                        <div class="campo">
                            <label for="movimentacao-quantidade">Quantidade</label>
                            <input type="number" id="movimentacao-quantidade" name="quantidade" min="1" step="1" required>
                        </div>

                        <div class="campo">
                            <label for="movimentacao-observacao">Observação (opcional)</label>
                            <input type="text" id="movimentacao-observacao" name="observacao" placeholder="Ex: compra do fornecedor, ajuste de inventário...">
                        </div>

                        <button type="submit" id="botao-movimentacao-submit">Registrar</button>
                    </form>
                </div>

                <div class="event-form-card">
                    <h3>Buscar Movimentações</h3>
                    <div class="campo">
                        <label for="busca-movimentacao-texto">Produto (nome ou código)</label>
                        <input type="text" id="busca-movimentacao-texto" placeholder="Buscar por nome ou código do produto">
                    </div>
                    <div class="campo">
                        <label for="busca-movimentacao-tipo">Tipo</label>
                        <select id="busca-movimentacao-tipo">
                            <option value="">Todos</option>
                            <option value="entrada">Entrada</option>
                            <option value="saida">Saída</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="busca-movimentacao-categoria">Categoria</label>
                        <select id="busca-movimentacao-categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" id="botao-buscar-movimentacoes">Buscar</button>
                </div>
            </div>

            <!--
                Compra de produtos pra reposição de estoque (tabela
                `compras_produtos`) — só registro financeiro (categoria,
                fornecedor, data, valor, parcelas), não identifica
                produto/quantidade específicos e por isso não mexe na
                Quantidade de nenhum produto nem no histórico de
                movimentações acima. Uma compra em N parcelas vira N
                linhas na tabela, uma por mês (mesmo dia da compra),
                cada uma com o Valor da Parcela (Valor Total ÷ N) — ver
                dashboard/compras-criar.php e app/Entity/CompraProduto.php.
                Entra também como "Saída de Valores" no resumo de
                dashboard/financeiro.php.
            -->
            <div class="cards">
                <div class="event-form-card event-list-card">
                    <h3>Compra de Produtos</h3>
                    <p id="lista-compras-mensagem" class="evento-mensagem"></p>
                    <ol id="lista-compras" class="lista-eventos"></ol>
                    <div id="paginacao-compras" class="paginacao"></div>
                </div>
            </div>

            <div class="cards">
                <div class="event-form-card">
                    <h3>Registrar Compra</h3>
                    <p id="compra-mensagem" class="evento-mensagem"></p>
                    <form id="form-compra">
                        <!-- token CSRF: confirmado em compras-criar.php/compras-excluir.php antes de gravar -->
                        <input type="hidden" id="compra-csrf-token" value="<?= \App\Session\Csrf::token() ?>">

                        <div class="campo">
                            <label for="compra-categoria">Categoria do produto</label>
                            <select id="compra-categoria" name="categoria" required>
                                <option value="">Selecione uma categoria</option>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label for="compra-fornecedor">Fornecedor</label>
                            <input type="text" id="compra-fornecedor" name="fornecedor" required>
                        </div>

                        <div class="campo">
                            <label for="compra-data">Data e hora</label>
                            <input type="datetime-local" id="compra-data" name="data" required>
                        </div>

                        <div class="campo">
                            <label for="compra-valor">Valor total da compra (R$)</label>
                            <input type="number" id="compra-valor" name="valorTotal" min="0" step="0.01" required>
                        </div>

                        <div class="campo">
                            <label for="compra-parcelas">Parcelas</label>
                            <select id="compra-parcelas" name="parcelas" required>
                                <?php for ($parcela = 1; $parcela <= 12; $parcela++): ?>
                                    <option value="<?= $parcela ?>"><?= $parcela ?>x</option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <button type="submit" id="botao-compra-submit">Registrar Compra</button>
                    </form>
                </div>

                <div class="event-form-card">
                    <h3>Buscar Compras</h3>
                    <div class="campo">
                        <label for="busca-compra-texto">Categoria ou fornecedor</label>
                        <input type="text" id="busca-compra-texto" placeholder="Buscar por categoria ou fornecedor">
                    </div>
                    <div class="campo">
                        <label for="busca-compra-de">De</label>
                        <input type="date" id="busca-compra-de">
                    </div>
                    <div class="campo">
                        <label for="busca-compra-ate">Até</label>
                        <input type="date" id="busca-compra-ate">
                    </div>
                    <button type="button" id="botao-buscar-compras">Buscar</button>
                </div>
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/notificacoes.js?v=<?= filemtime(__DIR__.'/public/assets/js/notificacoes.js') ?>"></script>
    <script src="public/assets/js/busca-menu.js?v=<?= filemtime(__DIR__.'/public/assets/js/busca-menu.js') ?>"></script>
    <script src="public/assets/js/estoque.js?v=<?= filemtime(__DIR__.'/public/assets/js/estoque.js') ?>"></script>
</body>
</html>
