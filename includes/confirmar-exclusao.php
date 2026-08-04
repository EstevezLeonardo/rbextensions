<main>
        <h2 class="mt-3">Excluir Usuário</h2>

    <form method="POST"> 
        <div class="form-group">
            <p>Tem certeza que deseja excluir o usuário <strong><?= $obVaga->nome ?></strong>?</p>
        </div>

        <div class="form-group">
             <a href="index.php">
                <button type="button" class="btn btn-success">Cancelar</button>
            </a>
            <button type="submit" name="excluir" class="btn btn-danger">Excluir</button>
        </div>
        
    </form>
</main>