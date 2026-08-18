<?php

    $busca = isset($busca) ? $busca : '';
    $filtroativo = isset($filtroativo) ? $filtroativo : '';

    $mensagem = '';
    if(isset($_GET['status'])) {
        switch($_GET['status']) {
            case 'success':
                $mensagem = '<p><strong>Ação executada com sucesso!</strong></p>';
                break;
            case 'error':
                $mensagem = '<p><strong>Ação não executada!</strong></p>';
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
                                <td>'.($vaga->ativo === 's' ? 'Ativo' : 'Inativo').'</td>
                                <td>'.$vaga->email.'</td>
                                <td>'.$vaga->datanascimento.'</td>

                                <td>
                                    <a href="editar.php?id='.$vaga->id.'">
                                        <button>Editar</button>
                                    </a>
                                    <a href="excluir.php?id='.$vaga->id.'">
                                        <button>Excluir</button>
                                    </a>
                                </td>
                            </tr>';
        }
    } else {
        $resultados = '<tr><td colspan="6">Nenhum registro encontrado.</td></tr>';
    }

    $resultados = strlen($resultados) ? $resultados : '<tr><td colspan="6">Nenhum registro encontrado.</td></tr>';

    $paginacao = '';
    $paginas = [];

    if (isset($obPagination) && is_object($obPagination) && method_exists($obPagination, 'getPages')) {
        $paginas = $obPagination->getPages();
        foreach ($paginas as $key => $pagina) {
            if ($pagina['atual']) {
                $paginacao .= '<button disabled>'.$pagina['pagina'].'</button>';
            } else {
                $paginacao .= '<a href="?pagina='.$pagina['pagina'].'&busca='.$busca.'&status='.$filtroativo.'">
                                    <button>'.$pagina['pagina'].'</button>
                                </a>';
            }
        }
    }
?>
<main class="wide">

                <?= $mensagem ?>

        <a href="cadas.php">
            <button>Cadastrar Usuário</button>
        </a>
    </section>

    <section>

        <form method="get">
            <label for="busca">Pesquisar:</label>
            <input type="text" name="busca" placeholder="Pesquisar por nome ou sobrenome" value="<?=$busca?>">

            <label for="ativo">Status:</label>
            <select name="status">
                <option value="">Todos</option>
                <option value="s" <?= isset($filtroativo) && $filtroativo === 's' ? 'selected' : '' ?>>Ativo</option>
                <option value="n" <?= isset($filtroativo) && $filtroativo === 'n' ? 'selected' : '' ?>>Inativo</option>
            </select>

            <input type="submit" value="Pesquisar">
        </form>

    </section>

    <section>

        <div class="table-scroll">
        <table>
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

    </section>
    <section class="actions">
        <a href="index.php">
            <button>Voltar</button>
        </a>
    
        <?= $paginacao ?>
    </section>


</main>
