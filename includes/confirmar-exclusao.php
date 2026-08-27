<main>
    <h2>Excluir Usuário</h2>

    <p>Tem certeza que deseja excluir o usuário <strong><?= $obVaga->nome ?></strong>?</p>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= \App\Session\Csrf::token() ?>">
        <a href="listar-usuarios.php">
            <button type="button">Cancelar</button>
        </a>
        <button type="submit" name="excluir">Excluir</button>
    </form>
</main>
