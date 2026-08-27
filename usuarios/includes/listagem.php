<?php

    /**
     * Corpo da listagem de usuários, incluído por listar.php.
     * Espera $vagas (resultado de Vaga::getVagas), $obPagination,
     * $busca e $filtroativo (para reexibir o formulário de busca
     * preenchido). Monta aqui o HTML da tabela e da paginação antes
     * de imprimir, para manter o bloco de PHP separado do HTML puro.
     */

    $busca = isset($busca) ? $busca : '';
    $filtroativo = isset($filtroativo) ? $filtroativo : '';

    // mensagem de feedback conforme o ?status= vindo do redirect (cadastro/edição/exclusão)
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

    // monta as linhas <tr> da tabela a partir da lista de usuários
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

    // monta os botões/links de página a partir de Pagination::getPages()
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
        <a href="../dashboard/index.php">
            <button>Voltar ao Dashboard</button>
        </a>
        <a href="logout.php">
            <button>Sair</button>
        </a>

        <?= $paginacao ?>
    </section>


</main>
