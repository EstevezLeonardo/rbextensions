<?php

/**
 * Listagem de usuários, com busca por nome/sobrenome, filtro por
 * status (ativo/inativo) e paginação. Exige login. É a página que
 * concentra os links para cadastrar (via dashboard), editar e excluir.
 *
 * Layout com o mesmo visual do dashboard (sidebar, nav-top, cards —
 * ver dashboard/public/assets/css/style.css), em vez do tema próprio
 * de usuarios/stilo1.css usado nas páginas de cadastro/login.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Vaga;
use App\Db\Pagination;
use App\Session\Login;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();

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

include 'includes/listagem.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuários RB</title>
    <link rel="stylesheet" href="../dashboard/public/assets/css/all.css">
    <link rel="stylesheet" href="../dashboard/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../dashboard/public/assets/css/style.css?v=<?= filemtime(__DIR__.'/../dashboard/public/assets/css/style.css') ?>">
</head>
<body>

    <div class="container-fluid">
        <header class="head">

            <nav>
                <div class="logo">
                    <a href="../dashboard/index.php">
                        <img src="../dashboard/public/assets/images/Royal_Brazilian_Extensions_logo_transparente.png" alt="Logo">
                    </a>

                </div>
                <form action="" class="form-group">
                    <div class="rows">
                        <input type="text" name="search" class="form-control rounded-0" placeholder="Pesquisar opções">
                        <i class="fa-solid fa-search"></i>
                    </div>
                </form>

                    <ul>
                        <li><a href="../dashboard/index.php"><span><i class="fa-solid fa-home"></i></span>Home</a></li>
                        <li><a href="../dashboard/agenda.php"><span><i class="fa-solid fa-calendar-alt"></i></span>Agenda</a></li>
                        <li><a href="../dashboard/perfil.php"><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="../dashboard/controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href="../dashboard/servicos.php"><span><i class="fa-solid fa-envelope"></i></span>Correio</a></li>
                        <li><a href="listar.php" class="actives"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
                        <li><a href="../dashboard/vendas.php"><span><i class="fa-solid fa-shopping-cart"></i></span>Vendas</a></li>
                        <li><a href="../dashboard/estoque.php"><span><i class="fa-solid fa-warehouse"></i></span>Estoque</a></li>
                        <li><a href="../dashboard/financeiro.php"><span><i class="fa-solid fa-dollar"></i></span>Financeiro</a></li>
                    </ul>

            </nav>

        </header>
        <main>
            <div class="nav-top">
                    <div class="user-notification">
                        <a href="../dashboard/perfil.php" title="Ver perfil">
                            <button class="users">
                                <p>Olá, <span><?= htmlspecialchars($usuarioLogado->nome, ENT_QUOTES, 'UTF-8') ?></span></p>
                                <i class="fa-solid fa-user"></i>
                            </button>
                        </a>
                        <a href="../dashboard/servicos.php" title="Ver notificações">
                            <button class="notification">
                                <i class="fa-solid fa-bell"></i>
                                <span id="notificacoes-badge" class="escondido">0</span>
                            </button>
                        </a>
                        <a href="logout.php" title="Sair">
                            <button class="notification logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </a>
                    </div>

            </div>

            <?= $mensagem ?>

            <div class="cards">
                <div class="event-form-card">
                    <h3>Pesquisar Usuários</h3>
                    <form method="get">
                        <div class="campo">
                            <label for="busca">Nome ou sobrenome</label>
                            <input type="text" id="busca" name="busca" placeholder="Pesquisar por nome ou sobrenome" value="<?= $busca ?>">
                        </div>
                        <div class="campo">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="">Todos</option>
                                <option value="s" <?= isset($filtroativo) && $filtroativo === 's' ? 'selected' : '' ?>>Ativo</option>
                                <option value="n" <?= isset($filtroativo) && $filtroativo === 'n' ? 'selected' : '' ?>>Inativo</option>
                            </select>
                        </div>
                        <button type="submit">Pesquisar</button>
                    </form>
                </div>
            </div>

            <div class="cards">
                <div class="event-form-card event-list-card">
                    <h3>Resultados</h3>

                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Sobrenome</th>
                                    <th>Status</th>
                                    <th>Email</th>
                                    <th>Data de Nascimento</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?= $resultados ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="paginacao">
                        <?= $paginacao ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../dashboard/public/assets/js/bootstrap.bundle.min.js"></script>
    <script src="../dashboard/public/assets/js/notificacoes.js?v=<?= filemtime(__DIR__.'/../dashboard/public/assets/js/notificacoes.js') ?>"></script>
    <script src="../dashboard/public/assets/js/busca-menu.js?v=<?= filemtime(__DIR__.'/../dashboard/public/assets/js/busca-menu.js') ?>"></script>
</body>
</html>
