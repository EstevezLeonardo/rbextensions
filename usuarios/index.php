<?php

/**
 * Página inicial (pública): apresenta o sistema e dá acesso ao login
 * e ao cadastro. Não exige autenticação.
 */

require __DIR__.'/../vendor/autoload.php';

include 'includes/header.php';
?>
<main>
    <section>
        <h2>Bem-vindo</h2>
        <p>Faça login para acessar o sistema.</p>
        <a href="login.php">
            <button>Acessar a Conta</button>
        </a>
    </section>

    <section>
        <p>Cadastre-se para acessar o sistema.</p>
        <a href="cadastrar.php">
            <button>Cadastrar</button>
        </a>
    </section>
</main>
<?php
include 'includes/footer.php';
