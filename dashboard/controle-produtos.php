<?php

/**
 * Controle de Produtos. Exige login, mesma checagem de sessão usada
 * no restante do projeto.
 *
 * Estrutura copiada de dashboard/agenda.php (mesmas caixas de
 * Adicionar/Editar, Buscar e Resultados), sem o calendário. Os dados
 * vêm da tabela `produtos` (id, Codigo, Nome, Descricao, Categoria,
 * Preco, Quantidade, Ativo) através dos endpoints
 * dashboard/produtos-listar.php, produtos-criar.php,
 * produtos-editar.php e produtos-excluir.php.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Session\Login;
use App\Entity\Produto;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();
$categorias = Produto::getCategorias();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos RB</title>
    <link rel="stylesheet" href="public/assets/css/all.css">
    <link rel="stylesheet" href="public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/assets/css/style.css">
</head>
<body>

    <div class="container-fluid">
        <header class="head">

            <nav>
                <div class="logo">
                    <a href="#">
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
                        <li><a href="index.php" class="actives"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href=""><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href=""><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
                        <li><a href="../usuarios/listar.php"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href=""><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>
                        <li><a href=""><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href=""><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
                    </ul>

            </nav>

        </header>
        <main>
            <div class="nav-top">
                <div class="bars">
                    <button class="btns"><i class="fa-solid fa-bars"></i></button>
                    <p>Painel RB</p>
                </div>
                    <div class="user-notification">
                        <button
                        class="users">
                            <p>Olá, <span><?= htmlspecialchars($usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?></span></p>
                            <i class="fa-solid fa-user"></i>
                        </button>
                        <button class="notification">
                            <i class="fa-solid fa-bell"></i>
                            <span>1</span>
                        </button>
                    </div>

            </div>

            <!--
                Lista dos produtos que batem com a busca abaixo, em
                ordem (mesmos dados de dashboard/produtos-listar.php).
                Cada item tem os botões "Editar" (carrega o produto no
                formulário abaixo) e "Excluir" (pede confirmação
                clicando duas vezes, para não apagar por engano).
            -->
            <div class="cards">
                <div class="event-form-card event-list-card">
                    <h3>Resultados da Busca</h3>
                    <p id="lista-produtos-mensagem" class="evento-mensagem"></p>
                    <ol id="lista-produtos" class="lista-eventos"></ol>
                    <div id="paginacao-produtos" class="paginacao"></div>
                </div>
            </div>

            <!--
                "Adicionar Produto" grava na tabela `produtos` via
                dashboard/produtos-criar.php — o mesmo formulário vira
                um formulário de EDIÇÃO (dashboard/produtos-editar.php)
                quando a pessoa clica em "Editar" num item da lista
                acima (o #produto-id escondido guarda qual produto
                está sendo editado; vazio = modo "criar"). "Buscar
                Produtos" filtra a lista acima
                (dashboard/produtos-listar.php) por nome/código e por
                status (ativo/inativo). Toda a lógica está em
                dashboard/public/assets/js/controle-produtos.js.
            -->
            <div class="cards">
                <div class="event-form-card">
                    <h3 id="produto-form-titulo">Adicionar Produto</h3>
                    <p id="produto-mensagem" class="evento-mensagem"></p>
                    <form id="form-produto">
                        <!-- token CSRF: confirmado em produtos-criar.php/produtos-editar.php antes de gravar -->
                        <input type="hidden" id="produto-csrf-token" value="<?= \App\Session\Csrf::token() ?>">
                        <!-- vazio = criando um produto novo; preenchido = editando o produto com esse id -->
                        <input type="hidden" id="produto-id" value="">

                        <div class="campo">
                            <label for="produto-codigo">Código</label>
                            <input type="text" id="produto-codigo" name="codigo" required>
                        </div>
                        <div class="campo">
                            <label for="produto-nome">Nome</label>
                            <input type="text" id="produto-nome" name="nome" required>
                        </div>
                        <div class="campo">
                            <label for="produto-categoria">Categoria</label>
                            <input type="text" id="produto-categoria" name="categoria">
                        </div>
                        <div class="campo">
                            <label for="produto-descricao">Descrição</label>
                            <textarea id="produto-descricao" name="descricao" rows="3"></textarea>
                        </div>
                        <div class="campo">
                            <label for="produto-preco">Preço (R$)</label>
                            <input type="number" id="produto-preco" name="preco" min="0" step="0.01" required>
                        </div>
                        <div class="campo">
                            <label for="produto-quantidade">Quantidade em estoque</label>
                            <input type="number" id="produto-quantidade" name="quantidade" min="0" step="1" required>
                        </div>
                        <fieldset class="campo campo-radio">
                            <legend>Status</legend>
                            <label><input type="radio" name="ativo" value="s" checked> Ativo</label>
                            <label><input type="radio" name="ativo" value="n"> Inativo</label>
                        </fieldset>

                        <button type="submit" id="botao-produto-submit">Adicionar Produto</button>
                        <button type="button" id="botao-cancelar-edicao-produto" class="escondido">Cancelar</button>
                    </form>
                </div>

                <div class="event-form-card">
                    <h3>Buscar Produtos</h3>
                    <div class="campo">
                        <label for="busca-produto-texto">Nome ou código</label>
                        <input type="text" id="busca-produto-texto" placeholder="Buscar por nome ou código">
                    </div>
                    <div class="campo">
                        <label for="busca-produto-status">Status</label>
                        <select id="busca-produto-status">
                            <option value="">Todos</option>
                            <option value="s">Ativo</option>
                            <option value="n">Inativo</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="busca-produto-categoria">Categoria</label>
                        <select id="busca-produto-categoria">
                            <option value="">Todas</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" id="botao-buscar-produtos">Buscar</button>
                </div>
            </div>
        </main>
        </div>

    <script src="public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="public/assets/js/controle-produtos.js"></script>
</body>
</html>
