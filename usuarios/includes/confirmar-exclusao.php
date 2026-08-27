<!--
    Tela de confirmação de exclusão, incluída por excluir.php. Espera
    $obVaga (usuário a excluir). A exclusão só acontece de fato quando
    esse form é enviado via POST — visitar a página por GET só mostra
    a confirmação, sem apagar nada.
-->
<main>
    <h2>Excluir Usuário</h2>

    <p>Tem certeza que deseja excluir o usuário <strong><?= $obVaga->nome ?></strong>?</p>

    <form method="POST">
        <!-- token CSRF: confirmado em excluir.php antes de processar o POST -->
        <input type="hidden" name="csrf_token" value="<?= \App\Session\Csrf::token() ?>">
        <a href="listar.php">
            <button type="button">Cancelar</button>
        </a>
        <button type="submit" name="excluir">Excluir</button>
    </form>
</main>
