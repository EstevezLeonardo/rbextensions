<?php 

    $busca = isset($busca) ? $busca : '';

    $mensagem = '';
    if(isset($_GET['status'])) {
        switch($_GET['status']) {
            case 'success':
                $mensagem = '<div class="alert alert-success">Ação executada com sucesso!</div>';
                break;
            case 'error':
                $mensagem = '<div class="alert alert-danger">Ação não executada!</div>';
                break;
        }
    }

    $resultados = '';
    if (!empty($vagas) && is_iterable($vagas)) {
        foreach ($vagas as $vaga) {
            $resultados .= '<tr>
                                <td>'.$vaga->id.'</td>
                                <td>'.$vaga->nome.'</td>
                                <td>'.$vaga->sobrenome.'</td>
                                <td>'.$vaga->email.'</td>
                                <td>'.$vaga->datanascimento.'</td>
                                <td>
                                    <a href="editar.php?id='.$vaga->id.'">
                                        <button class="btn btn-primary">Editar</button>
                                    </a>
                                    <a href="excluir.php?id='.$vaga->id.'">
                                        <button class="btn btn-danger">Excluir</button>
                                    </a>
                                </td>
                            </tr>';
        }
    } else {
        $resultados = '<tr><td colspan="6" class="text-center">Nenhum registro encontrado.</td></tr>';
    }

    $resultados = strlen($resultados) ? $resultados : '<tr><td colspan="6" class="text-center">Nenhum registro encontrado.</td></tr>';

?>
<main>

                <?= $mensagem ?>

    <section>
        <a href="cadas.php">
            <button class="btn btn-success">Cadastrar Usuário</button>
        </a>
    </section>
    <section>
        <form method="get">
            <div class="row my-4"> 
            
                <div class="col">
                    <label for="busca">Pesquisar:</label>
                    <input type="text" name="busca" class="form-control" placeholder="Pesquisar por nome ou sobrenome" value="<?=$busca?>">
                </div>
                <div class="col d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">Pesquisar</button>
                </div>

        </form>
    </section>

    <section>
         <table class="table bg-light mt-3">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Sobrenome</th>
                    <th>Email</th>
                    <th>Data de Nascimento</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?= $resultados ?>
            </tbody>
         </table>
    </section>
</main>