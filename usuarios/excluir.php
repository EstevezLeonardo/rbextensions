<?php

/**
 * Exclusão de usuário: exige login e confirmação explícita (GET
 * mostra a tela "tem certeza?"; a exclusão de fato só acontece no
 * POST, validado com o token CSRF).
 *
 * Layout com o mesmo visual do dashboard (sidebar, nav-top, cards),
 * igual usuarios/listar.php.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Vaga;
use App\Session\Login;
use App\Session\Csrf;

Login::requireLogin();

$usuarioLogado = Login::getUsuario();

if(!isset($_GET['id']) or !is_numeric($_GET['id'])) {
    header('location: listar.php?status=error');
    exit;
}
$obVaga = Vaga::getVaga($_GET['id']);

if(!$obVaga instanceof Vaga) {
    header('location: listar.php?status=error');
    exit;
}

if(isset($_POST['excluir'])) {

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        header('location: listar.php?status=error');
        exit;
    }

    $obVaga->excluir();

    header('location: listar.php?status=success');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Excluir Usuário — RB</title>
    <link rel="stylesheet" href="../dashboard/public/assets/css/all.css">
    <link rel="stylesheet" href="../dashboard/public/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../dashboard/public/assets/css/style.css">
</head>
<body>

    <div class="container-fluid">
        <header class="head">

            <nav>
                <div class="logo">
                    <a href="#">
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
                        <li><a href=""><span><i class="fa-solid fa-server"></i></span>Perfil</a></li>
                        <li><a href="../dashboard/controle-produtos.php"><span><i class="fa-solid fa-box"></i></span>Produtos</a></li>
                        <li><a href=""><span><i class="fa-solid fa-concierge-bell"></i></span>Serviços</a></li>
                        <li><a href="listar.php" class="actives"><span><i class="fa-solid fa-user"></i></span>Clientes</a></li>
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
                        <a href="logout.php" title="Sair">
                            <button class="notification logout">
                                <i class="fa-solid fa-right-from-bracket"></i>
                            </button>
                        </a>
                    </div>

            </div>

            <div class="cards">
                <div class="event-form-card">
                    <h3>Excluir Usuário</h3>

                    <p>Tem certeza que deseja excluir o usuário <strong><?= htmlspecialchars($obVaga->nome, ENT_QUOTES, 'UTF-8') ?></strong>?</p>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">
                        <a href="listar.php">
                            <button type="button" id="botao-cancelar-edicao">Cancelar</button>
                        </a>
                        <button type="submit" name="excluir" class="btn-excluir">Excluir</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../dashboard/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>