<?php

    /**
     * Lógica da listagem de usuários, incluída por listar.php antes do
     * HTML. Espera $vagas (resultado de Vaga::getVagas), $obPagination,
     * $busca e $filtroativo (para reexibir o formulário de busca
     * preenchido). Monta aqui as strings $mensagem, $resultados (linhas
     * <tr>) e $paginacao (botões de página), que listar.php imprime
     * dentro do layout com visual do dashboard.
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
                                        <button class="btn-editar">Editar</button>
                                    </a>
                                    <a href="excluir.php?id='.$vaga->id.'">
                                        <button class="btn-excluir">Excluir</button>
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
