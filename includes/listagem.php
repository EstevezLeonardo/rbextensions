<?php 
    $resultados = '';
    foreach($vagas as $vaga) {
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
?>
<main>
    <section>
        <a href="cadas.php">
            <button class="btn btn-success">Cadastrar Usuário</button>
        </a>
    </section>
    <section>
         <table class="table">
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