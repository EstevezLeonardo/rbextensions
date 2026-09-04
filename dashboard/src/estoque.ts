/**
 * Estoque (dashboard/estoque.php).
 *
 * Liga:
 *   - o filtro "Categoria do produto" de "Registrar Movimentação": não
 *     manda nada pro servidor, só mostra/esconde as <option> de
 *     #movimentacao-produto conforme o data-categoria de cada uma
 *     (preenchido em PHP a partir de App\Entity\Produto::$Categoria) —
 *     existe só pra achar mais fácil o produto certo quando várias
 *     categorias têm nomes parecidos;
 *   - o formulário "Registrar Movimentação" (produto, tipo, quantidade,
 *     observação), que sempre CRIA — diferente de Produtos, não existe
 *     modo de edição aqui (dashboard/estoque-criar.php);
 *   - a caixa "Buscar Movimentações" (produto, tipo e categoria), que
 *     recarrega o histórico a partir da página 1 — esse filtro de
 *     categoria já vai pro servidor (dashboard/estoque-listar.php),
 *     diferente do de "Registrar Movimentação" acima;
 *   - o histórico (#lista-movimentacoes), paginado em 10 por página
 *     (dashboard/estoque-listar.php), com o botão "Excluir" (dois
 *     cliques: o primeiro pede confirmação, o segundo desfaz a
 *     movimentação — devolve/retira do estoque — e apaga de fato);
 *   - os botões de página (#paginacao-movimentacoes): excluir recarrega
 *     a MESMA página em que a pessoa está; já registrar uma
 *     movimentação ou mudar a busca volta pra página 1.
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/estoque.js.
 *
 * Dentro de uma IIFE, como os demais .ts do dashboard (ver o comentário
 * equivalente em controle-produtos.ts).
 */
(function () {

/** Formato de movimentação devolvido por dashboard/estoque-listar.php. */
interface MovimentacaoApi {
    id: number | string;
    ProdutoId: number | string;
    ProdutoCodigo: string;
    ProdutoNome: string;
    ProdutoCategoria: string;
    Tipo: string;
    Quantidade: number | string;
    Observacao: string;
    Data: string;
}

/** Formato da resposta de dashboard/estoque-listar.php. */
interface ListaMovimentacoesResposta {
    movimentacoes: MovimentacaoApi[];
    paginaAtual: number;
    totalPaginas: number;
}

/** Formato de compra devolvido por dashboard/compras-listar.php. */
interface CompraApi {
    id: number | string;
    Categoria: string;
    Fornecedor: string;
    Data: string;
    ValorTotal: number | string;
    ParcelaAtual: number | string;
    ParcelaTotal: number | string;
}

/** Formato da resposta de dashboard/compras-listar.php. */
interface ListaComprasResposta {
    compras: CompraApi[];
    paginaAtual: number;
    totalPaginas: number;
}

document.addEventListener('DOMContentLoaded', function () {
    const buscaTextoEl = document.getElementById('busca-movimentacao-texto') as HTMLInputElement | null;
    const buscaTipoEl = document.getElementById('busca-movimentacao-tipo') as HTMLSelectElement | null;
    const buscaCategoriaEl = document.getElementById('busca-movimentacao-categoria') as HTMLSelectElement | null;

    let paginaAtual = 1;

    const carregarPagina = function (pagina: number): void {
        paginaAtual = pagina;
        carregarListaDeMovimentacoes(buscaTextoEl, buscaTipoEl, buscaCategoriaEl, pagina, carregarPagina, recarregarPaginaAtual);
    };

    // recarrega a mesma página em que a pessoa está (usado depois de excluir um item)
    const recarregarPaginaAtual = function (): void {
        carregarPagina(paginaAtual);
    };

    // começa a busca do zero, na página 1 (usado ao mudar filtros ou registrar uma movimentação)
    const buscarDoInicio = function (): void {
        carregarPagina(1);
    };

    ligarFiltroDeCategoriaDoProduto();
    ligarFormularioDeMovimentacao(buscarDoInicio);
    ligarBuscaDeMovimentacoes(buscarDoInicio, buscaCategoriaEl);

    buscarDoInicio();
});

/**
 * Liga "Categoria do produto" (Registrar Movimentação): não manda nada
 * pro servidor, só mostra/esconde as <option> de #movimentacao-produto
 * conforme o data-categoria de cada uma. Trocar de categoria com um
 * produto de outra já selecionado limpa a seleção (senão a
 * movimentação seria registrada num produto escondido/fora do filtro,
 * sem a pessoa perceber).
 */
function ligarFiltroDeCategoriaDoProduto(): void {
    const categoriaEl = document.getElementById('movimentacao-categoria-filtro') as HTMLSelectElement | null;
    const produtoEl = document.getElementById('movimentacao-produto') as HTMLSelectElement | null;

    if (!categoriaEl || !produtoEl) {
        return;
    }

    const opcoes = Array.from(produtoEl.options);

    categoriaEl.addEventListener('change', function () {
        const categoria = categoriaEl.value;
        const opcaoSelecionada = produtoEl.options[produtoEl.selectedIndex];

        opcoes.forEach(function (opcao) {
            opcao.hidden = categoria !== '' && opcao.value !== '' && opcao.dataset.categoria !== categoria;
        });

        if (opcaoSelecionada && opcaoSelecionada.hidden) {
            produtoEl.value = '';
        }
    });
}

// "Compra de Produtos": bloco independente do de Movimentações acima,
// com seu próprio estado de paginação — as duas listas da página
// (histórico de movimentações e compras) recarregam cada uma na sua.
document.addEventListener('DOMContentLoaded', function () {
    const buscaTextoEl = document.getElementById('busca-compra-texto') as HTMLInputElement | null;
    const buscaDeEl = document.getElementById('busca-compra-de') as HTMLInputElement | null;
    const buscaAteEl = document.getElementById('busca-compra-ate') as HTMLInputElement | null;

    let paginaAtual = 1;

    const carregarPagina = function (pagina: number): void {
        paginaAtual = pagina;
        carregarListaDeCompras(buscaTextoEl, buscaDeEl, buscaAteEl, pagina, carregarPagina, recarregarPaginaAtual);
    };

    // recarrega a mesma página em que a pessoa está (usado depois de excluir uma compra)
    const recarregarPaginaAtual = function (): void {
        carregarPagina(paginaAtual);
    };

    // começa a busca do zero, na página 1 (usado ao mudar a busca ou registrar uma compra)
    const buscarDoInicio = function (): void {
        carregarPagina(1);
    };

    ligarFormularioDeCompra(buscarDoInicio);
    ligarBuscaDeCompras(buscarDoInicio);

    buscarDoInicio();
});

/**
 * Liga o envio do formulário "Registrar Movimentação". Sempre chama
 * dashboard/estoque-criar.php; ao terminar, limpa o formulário e chama
 * `aoRegistrar` (volta a lista pra página 1).
 */
function ligarFormularioDeMovimentacao(aoRegistrar: () => void): void {
    const form = document.getElementById('form-movimentacao') as HTMLFormElement | null;
    const mensagemEl = document.getElementById('movimentacao-mensagem');
    const produtoEl = document.getElementById('movimentacao-produto') as HTMLSelectElement | null;
    const quantidadeEl = document.getElementById('movimentacao-quantidade') as HTMLInputElement | null;
    const observacaoEl = document.getElementById('movimentacao-observacao') as HTMLInputElement | null;
    const csrfEl = document.getElementById('movimentacao-csrf-token') as HTMLInputElement | null;

    if (!form || !mensagemEl || !produtoEl || !quantidadeEl || !observacaoEl || !csrfEl) {
        return;
    }

    const mostrarMensagem = function (texto: string, tipo: 'erro' | 'sucesso'): void {
        mensagemEl.textContent = texto;
        mensagemEl.className = 'evento-mensagem ' + tipo;
    };

    form.addEventListener('submit', function (evento) {
        // evita o envio "tradicional" do form (que recarregaria a página)
        evento.preventDefault();

        const radioTipoMarcado = document.querySelector('input[name="tipo"]:checked') as HTMLInputElement | null;

        const corpo = {
            produtoId: produtoEl.value,
            tipo: radioTipoMarcado ? radioTipoMarcado.value : 'entrada',
            quantidade: quantidadeEl.value,
            observacao: observacaoEl.value,
            csrf_token: csrfEl.value,
        };

        fetch('estoque-criar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(corpo),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível registrar a movimentação.');
                    }
                    return dados;
                });
            })
            .then(function () {
                form.reset();
                mostrarMensagem('Movimentação registrada!', 'sucesso');
                aoRegistrar();
            })
            .catch(function (erro: Error) {
                mostrarMensagem(erro.message, 'erro');
            });
    });
}

/**
 * Liga a caixa "Buscar Movimentações": clicar em "Buscar" (ou apertar
 * Enter no campo de texto, ou trocar o tipo/categoria) volta o
 * histórico pra página 1, já com os filtros atuais.
 */
function ligarBuscaDeMovimentacoes(buscarDoInicio: () => void, categoriaEl: HTMLSelectElement | null): void {
    const botaoBuscar = document.getElementById('botao-buscar-movimentacoes');
    const textoEl = document.getElementById('busca-movimentacao-texto');
    const tipoEl = document.getElementById('busca-movimentacao-tipo');

    if (!botaoBuscar) {
        return;
    }

    botaoBuscar.addEventListener('click', buscarDoInicio);

    if (textoEl) {
        textoEl.addEventListener('keydown', function (evento) {
            if ((evento as KeyboardEvent).key === 'Enter') {
                evento.preventDefault();
                buscarDoInicio();
            }
        });
    }

    // trocar o tipo ou a categoria já dispara a busca sozinho, sem precisar clicar em "Buscar"
    if (tipoEl) {
        tipoEl.addEventListener('change', buscarDoInicio);
    }
    if (categoriaEl) {
        categoriaEl.addEventListener('change', buscarDoInicio);
    }
}

/**
 * Busca a página de movimentações em dashboard/estoque-listar.php (com
 * os filtros e a página atuais) e desenha o histórico numerado
 * #lista-movimentacoes, mais os botões de página em
 * #paginacao-movimentacoes.
 */
function carregarListaDeMovimentacoes(
    buscaTextoEl: HTMLInputElement | null,
    buscaTipoEl: HTMLSelectElement | null,
    buscaCategoriaEl: HTMLSelectElement | null,
    pagina: number,
    irParaPagina: (pagina: number) => void,
    recarregarPaginaAtual: () => void
): void {
    const listaEl = document.getElementById('lista-movimentacoes');
    const mensagemEl = document.getElementById('lista-movimentacoes-mensagem');

    if (!listaEl) {
        return;
    }

    const parametros = new URLSearchParams({
        busca: buscaTextoEl ? buscaTextoEl.value : '',
        tipo: buscaTipoEl ? buscaTipoEl.value : '',
        categoria: buscaCategoriaEl ? buscaCategoriaEl.value : '',
        pagina: String(pagina),
    });

    fetch('estoque-listar.php?' + parametros.toString())
        .then(function (resposta) {
            return resposta.json() as Promise<ListaMovimentacoesResposta>;
        })
        .then(function (dados) {
            desenharListaDeMovimentacoes(dados.movimentacoes, listaEl, mensagemEl, recarregarPaginaAtual);
            desenharPaginacao(dados.paginaAtual, dados.totalPaginas, irParaPagina);
        })
        .catch(function () {
            if (mensagemEl) {
                mensagemEl.textContent = 'Não foi possível carregar o histórico de movimentações.';
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
}

/**
 * Monta os <li> do histórico a partir das movimentações recebidas. Usa
 * textContent (nunca innerHTML) pros textos, pra um nome de
 * produto/observação com caracteres de HTML não virar código na página.
 */
function desenharListaDeMovimentacoes(
    movimentacoes: MovimentacaoApi[],
    listaEl: HTMLElement,
    mensagemEl: HTMLElement | null,
    recarregarPaginaAtual: () => void
): void {
    listaEl.innerHTML = '';

    if (mensagemEl) {
        mensagemEl.textContent = movimentacoes.length === 0 ? 'Nenhuma movimentação encontrada.' : '';
        mensagemEl.className = 'evento-mensagem';
    }

    movimentacoes.forEach(function (movimentacao) {
        const item = document.createElement('li');
        item.className = 'evento-item';

        const info = document.createElement('div');
        info.className = 'evento-item-info';

        const titulo = document.createElement('strong');
        titulo.textContent = movimentacao.ProdutoCodigo + ' — ' + movimentacao.ProdutoNome + ' (' + movimentacao.ProdutoCategoria + ')';

        const detalhes = document.createElement('span');
        detalhes.textContent = formatarDetalhes(movimentacao);

        info.appendChild(titulo);
        info.appendChild(detalhes);

        const acoes = document.createElement('div');
        acoes.className = 'evento-item-acoes';

        const botaoExcluir = document.createElement('button');
        botaoExcluir.type = 'button';
        botaoExcluir.className = 'btn-excluir';
        botaoExcluir.textContent = 'Excluir';
        ligarBotaoExcluir(botaoExcluir, movimentacao, recarregarPaginaAtual);

        acoes.appendChild(botaoExcluir);

        item.appendChild(info);
        item.appendChild(acoes);
        listaEl.appendChild(item);
    });
}

/**
 * Desenha os botões de página em #paginacao-movimentacoes (um por
 * página; a página atual fica desabilitada). Não mostra nada quando só
 * existe uma página.
 */
function desenharPaginacao(paginaAtual: number, totalPaginas: number, irParaPagina: (pagina: number) => void): void {
    const container = document.getElementById('paginacao-movimentacoes');
    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (totalPaginas <= 1) {
        return;
    }

    for (let pagina = 1; pagina <= totalPaginas; pagina++) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.textContent = String(pagina);
        botao.disabled = pagina === paginaAtual;
        botao.addEventListener('click', function () {
            irParaPagina(pagina);
        });
        container.appendChild(botao);
    }
}

/**
 * Liga o botão "Excluir" de um item: o primeiro clique só marca
 * "confirmando" (troca o texto/cor, pra evitar excluir sem querer); o
 * segundo clique, dentro de 4 segundos, chama estoque-excluir.php de
 * verdade (que desfaz o efeito no estoque) e recarrega a página atual
 * do histórico.
 */
function ligarBotaoExcluir(botao: HTMLButtonElement, movimentacao: MovimentacaoApi, recarregarPaginaAtual: () => void): void {
    let confirmando = false;
    let timeoutId: number | undefined;

    botao.addEventListener('click', function () {
        if (!confirmando) {
            confirmando = true;
            botao.textContent = 'Confirmar exclusão?';
            botao.classList.add('confirmando');

            // se não confirmar em 4s, volta ao estado normal
            timeoutId = window.setTimeout(function () {
                confirmando = false;
                botao.textContent = 'Excluir';
                botao.classList.remove('confirmando');
            }, 4000);

            return;
        }

        window.clearTimeout(timeoutId);
        botao.disabled = true;

        const csrfToken = (document.getElementById('movimentacao-csrf-token') as HTMLInputElement).value;

        fetch('estoque-excluir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: movimentacao.id, csrf_token: csrfToken }),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível excluir a movimentação.');
                    }
                    return dados;
                });
            })
            .then(function () {
                recarregarPaginaAtual();
            })
            .catch(function (erro: Error) {
                botao.disabled = false;
                botao.textContent = 'Excluir';
                botao.classList.remove('confirmando');
                confirmando = false;
                alert(erro.message);
            });
    });
}

/** Formata tipo, quantidade, data e observação numa linha só. */
function formatarDetalhes(movimentacao: MovimentacaoApi): string {
    const tipo = movimentacao.Tipo === 'saida' ? 'Saída' : 'Entrada';
    const data = new Date(movimentacao.Data.replace(' ', 'T')).toLocaleString('pt-BR');
    const observacao = movimentacao.Observacao ? ' · ' + movimentacao.Observacao : '';

    return tipo + ' de ' + movimentacao.Quantidade + ' · ' + data + observacao;
}

/**
 * Liga o envio do formulário "Registrar Compra". Sempre chama
 * dashboard/compras-criar.php; ao terminar, limpa o formulário e chama
 * `aoRegistrar` (volta a lista de compras pra página 1).
 */
function ligarFormularioDeCompra(aoRegistrar: () => void): void {
    const form = document.getElementById('form-compra') as HTMLFormElement | null;
    const mensagemEl = document.getElementById('compra-mensagem');
    const categoriaEl = document.getElementById('compra-categoria') as HTMLSelectElement | null;
    const fornecedorEl = document.getElementById('compra-fornecedor') as HTMLInputElement | null;
    const dataEl = document.getElementById('compra-data') as HTMLInputElement | null;
    const valorEl = document.getElementById('compra-valor') as HTMLInputElement | null;
    const parcelasEl = document.getElementById('compra-parcelas') as HTMLSelectElement | null;
    const csrfEl = document.getElementById('compra-csrf-token') as HTMLInputElement | null;

    if (!form || !mensagemEl || !categoriaEl || !fornecedorEl || !dataEl || !valorEl || !parcelasEl || !csrfEl) {
        return;
    }

    const mostrarMensagem = function (texto: string, tipo: 'erro' | 'sucesso'): void {
        mensagemEl.textContent = texto;
        mensagemEl.className = 'evento-mensagem ' + tipo;
    };

    form.addEventListener('submit', function (evento) {
        // evita o envio "tradicional" do form (que recarregaria a página)
        evento.preventDefault();

        const corpo = {
            categoria: categoriaEl.value,
            fornecedor: fornecedorEl.value,
            data: dataEl.value,
            valorTotal: valorEl.value,
            parcelas: parcelasEl.value,
            csrf_token: csrfEl.value,
        };

        fetch('compras-criar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(corpo),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível registrar a compra.');
                    }
                    return dados;
                });
            })
            .then(function () {
                form.reset();
                mostrarMensagem('Compra registrada!', 'sucesso');
                aoRegistrar();
            })
            .catch(function (erro: Error) {
                mostrarMensagem(erro.message, 'erro');
            });
    });
}

/**
 * Liga a caixa "Buscar Compras": clicar em "Buscar" (ou apertar Enter no
 * campo de texto) volta a lista de compras pra página 1, já com a
 * busca e o período (De/Até) atuais.
 */
function ligarBuscaDeCompras(buscarDoInicio: () => void): void {
    const botaoBuscar = document.getElementById('botao-buscar-compras');
    const textoEl = document.getElementById('busca-compra-texto');

    if (!botaoBuscar) {
        return;
    }

    botaoBuscar.addEventListener('click', buscarDoInicio);

    if (textoEl) {
        textoEl.addEventListener('keydown', function (evento) {
            if ((evento as KeyboardEvent).key === 'Enter') {
                evento.preventDefault();
                buscarDoInicio();
            }
        });
    }
}

/**
 * Busca a página de compras em dashboard/compras-listar.php (com a
 * busca, o período e a página atuais) e desenha a lista numerada
 * #lista-compras, mais os botões de página em #paginacao-compras.
 */
function carregarListaDeCompras(
    buscaTextoEl: HTMLInputElement | null,
    buscaDeEl: HTMLInputElement | null,
    buscaAteEl: HTMLInputElement | null,
    pagina: number,
    irParaPagina: (pagina: number) => void,
    recarregarPaginaAtual: () => void
): void {
    const listaEl = document.getElementById('lista-compras');
    const mensagemEl = document.getElementById('lista-compras-mensagem');

    if (!listaEl) {
        return;
    }

    const parametros = new URLSearchParams({
        busca: buscaTextoEl ? buscaTextoEl.value : '',
        de: buscaDeEl ? buscaDeEl.value : '',
        ate: buscaAteEl ? buscaAteEl.value : '',
        pagina: String(pagina),
    });

    fetch('compras-listar.php?' + parametros.toString())
        .then(function (resposta) {
            return resposta.json() as Promise<ListaComprasResposta>;
        })
        .then(function (dados) {
            desenharListaDeCompras(dados.compras, listaEl, mensagemEl, recarregarPaginaAtual);
            desenharPaginacaoCompras(dados.paginaAtual, dados.totalPaginas, irParaPagina);
        })
        .catch(function () {
            if (mensagemEl) {
                mensagemEl.textContent = 'Não foi possível carregar a lista de compras.';
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
}

/**
 * Monta os <li> da lista a partir das compras recebidas. Usa
 * textContent (nunca innerHTML) pros textos, pra uma categoria/
 * fornecedor com caracteres de HTML não virar código na página.
 */
function desenharListaDeCompras(
    compras: CompraApi[],
    listaEl: HTMLElement,
    mensagemEl: HTMLElement | null,
    recarregarPaginaAtual: () => void
): void {
    listaEl.innerHTML = '';

    if (mensagemEl) {
        mensagemEl.textContent = compras.length === 0 ? 'Nenhuma compra encontrada.' : '';
        mensagemEl.className = 'evento-mensagem';
    }

    compras.forEach(function (compra) {
        const item = document.createElement('li');
        item.className = 'evento-item';

        const info = document.createElement('div');
        info.className = 'evento-item-info';

        const titulo = document.createElement('strong');
        titulo.textContent = compra.Categoria + ' — ' + compra.Fornecedor;

        const detalhes = document.createElement('span');
        detalhes.textContent = formatarDetalhesCompra(compra);

        info.appendChild(titulo);
        info.appendChild(detalhes);

        const acoes = document.createElement('div');
        acoes.className = 'evento-item-acoes';

        const botaoExcluir = document.createElement('button');
        botaoExcluir.type = 'button';
        botaoExcluir.className = 'btn-excluir';
        botaoExcluir.textContent = 'Excluir';
        ligarBotaoExcluirCompra(botaoExcluir, compra, recarregarPaginaAtual);

        acoes.appendChild(botaoExcluir);

        item.appendChild(info);
        item.appendChild(acoes);
        listaEl.appendChild(item);
    });
}

/**
 * Desenha os botões de página em #paginacao-compras (um por página; a
 * página atual fica desabilitada). Não mostra nada quando só existe
 * uma página.
 */
function desenharPaginacaoCompras(paginaAtual: number, totalPaginas: number, irParaPagina: (pagina: number) => void): void {
    const container = document.getElementById('paginacao-compras');
    if (!container) {
        return;
    }

    container.innerHTML = '';

    if (totalPaginas <= 1) {
        return;
    }

    for (let pagina = 1; pagina <= totalPaginas; pagina++) {
        const botao = document.createElement('button');
        botao.type = 'button';
        botao.textContent = String(pagina);
        botao.disabled = pagina === paginaAtual;
        botao.addEventListener('click', function () {
            irParaPagina(pagina);
        });
        container.appendChild(botao);
    }
}

/**
 * Liga o botão "Excluir" de uma compra: o primeiro clique só marca
 * "confirmando" (troca o texto/cor, pra evitar excluir sem querer); o
 * segundo clique, dentro de 4 segundos, chama compras-excluir.php de
 * verdade e recarrega a página atual da lista.
 */
function ligarBotaoExcluirCompra(botao: HTMLButtonElement, compra: CompraApi, recarregarPaginaAtual: () => void): void {
    let confirmando = false;
    let timeoutId: number | undefined;

    botao.addEventListener('click', function () {
        if (!confirmando) {
            confirmando = true;
            botao.textContent = 'Confirmar exclusão?';
            botao.classList.add('confirmando');

            timeoutId = window.setTimeout(function () {
                confirmando = false;
                botao.textContent = 'Excluir';
                botao.classList.remove('confirmando');
            }, 4000);

            return;
        }

        window.clearTimeout(timeoutId);
        botao.disabled = true;

        const csrfToken = (document.getElementById('compra-csrf-token') as HTMLInputElement).value;

        fetch('compras-excluir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: compra.id, csrf_token: csrfToken }),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível excluir a compra.');
                    }
                    return dados;
                });
            })
            .then(function () {
                recarregarPaginaAtual();
            })
            .catch(function (erro: Error) {
                botao.disabled = false;
                botao.textContent = 'Excluir';
                botao.classList.remove('confirmando');
                confirmando = false;
                alert(erro.message);
            });
    });
}

/**
 * Formata valor total, valor da parcela, parcela e data numa linha só.
 * Valor da Parcela (Valor Total ÷ Parcela Total) é calculado aqui, na
 * hora de exibir — não fica guardado no banco (ver
 * dashboard/compras-criar.php).
 */
function formatarDetalhesCompra(compra: CompraApi): string {
    const valorTotal = Number(compra.ValorTotal);
    const parcelaTotal = Number(compra.ParcelaTotal);
    const valorParcela = (valorTotal / parcelaTotal).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const total = valorTotal.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    const parcela = 'Parcela ' + compra.ParcelaAtual + '/' + compra.ParcelaTotal;
    const data = new Date(compra.Data.replace(' ', 'T')).toLocaleString('pt-BR');

    return 'Total: ' + total + ' · Valor da parcela: ' + valorParcela + ' · ' + parcela + ' · ' + data;
}

})();
