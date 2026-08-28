<?php

/**
 * Edição de usuário existente. Exige login (Login::requireLogin()).
 * O id vem via GET (?id=), é validado como numérico e precisa
 * corresponder a um usuário real antes de mostrar o formulário.
 *
 * Layout com o mesmo visual do dashboard (sidebar, nav-top, cards),
 * igual usuarios/listar.php. O formulário é próprio desta página (não
 * reaproveita includes/formulario.php, que mantém o tema antigo usado
 * em cadastrar.php).
 */

require __DIR__.'/../vendor/autoload.php';

define('TITLE', 'Editar Usuário');

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

if(isset($_POST['nome'],$_POST['sobrenome'],$_POST['email'],$_POST['datanascimento'])) {

    if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
        header('location: listar.php?status=error');
        exit;
    }

    $obVaga->nome = $_POST['nome'];
    $obVaga->sobrenome = $_POST['sobrenome'];
    $obVaga->email = $_POST['email'];
    $obVaga->datanascimento = $_POST['datanascimento'];
    $obVaga->ativo = $_POST['ativo'];

    $obVaga->atualizar();

    header('location: listar.php?status=success');
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário — RB</title>
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
                    <h3><?= TITLE ?></h3>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= Csrf::token() ?>">

                        <div class="campo">
                            <label for="nome">Nome</label>
                            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($obVaga->nome ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="campo">
                            <label for="sobrenome">Sobrenome</label>
                            <input type="text" name="sobrenome" id="sobrenome" value="<?= htmlspecialchars($obVaga->sobrenome ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="campo">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" value="<?= htmlspecialchars($obVaga->email ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <div class="campo">
                            <label for="datanascimento">Data de Nascimento</label>
                            <input type="date" name="datanascimento" id="datanascimento" value="<?= htmlspecialchars($obVaga->datanascimento ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
                        </div>

                        <fieldset class="campo campo-radio">
                            <legend>Status</legend>
                            <label><input type="radio" name="ativo" value="s" <?= (isset($obVaga->ativo) && $obVaga->ativo === 's') ? 'checked' : '' ?> required> Ativo</label>
                            <label><input type="radio" name="ativo" value="n" <?= (isset($obVaga->ativo) && $obVaga->ativo === 'n') ? 'checked' : '' ?> required> Inativo</label>
                        </fieldset>

                        <button type="submit">Salvar Alterações</button>
                        <a href="listar.php">
                            <button type="button" id="botao-cancelar-edicao">Cancelar</button>
                        </a>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../dashboard/public/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>