/**
 * Controle de Produtos (dashboard/controle-produtos.php).
 *
 * Liga:
 *   - o formulário "Adicionar Produto", que funciona tanto pra CRIAR
 *     quanto pra EDITAR um produto (o campo escondido #produto-id
 *     decide qual endpoint chamar: produtos-criar.php ou
 *     produtos-editar.php);
 *   - a caixa "Buscar Produtos", que recarrega a lista de resultados
 *     (dashboard/produtos-listar.php) com os filtros atuais;
 *   - a lista de resultados (#lista-produtos), com os botões "Editar"
 *     (carrega o produto no formulário acima) e "Excluir" (dois
 *     cliques: o primeiro pede confirmação, o segundo apaga de fato).
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/controle-produtos.js.
 *
 * Tudo dentro de uma IIFE: como não usamos módulos (sem bundler), os
 * .ts compilados viram scripts globais — sem isso, nomes de função
 * repetidos entre este arquivo e outras páginas (ex: agenda.ts)
 * colidiriam no escopo global e o `tsc` recusaria compilar.
 */
(function () {

/** Formato de produto devolvido por dashboard/produtos-listar.php. */
interface ProdutoApi {
    id: number | string;
    Codigo: string;
    Nome: string;
    Descricao: string;
    Categoria: string;
    Preco: number | string;
    Quantidade: number | string;
    Ativo: string;
}

document.addEventListener('DOMContentLoaded', function () {
    const buscaTextoEl = document.getElementById('busca-produto-texto') as HTMLInputElement | null;
    const buscaStatusEl = document.getElementById('busca-produto-status') as HTMLSelectElement | null;

    const recarregarLista = function (): void {
        carregarListaDeProdutos(buscaTextoEl, buscaStatusEl, recarregarLista);
    };

    ligarFormularioDeProduto(recarregarLista);
    ligarBuscaDeProdutos(recarregarLista);

    recarregarLista();
});

/**
 * Liga o envio do formulário "Adicionar/Editar Produto". Em modo
 * "criar" (#produto-id vazio) manda pra produtos-criar.php; em modo
 * "editar" (#produto-id preenchido, definido por preencherParaEdicao)
 * manda pra produtos-editar.php. Em ambos os casos, ao terminar com
 * sucesso volta pro modo "criar" e recarrega a lista.
 */
function ligarFormularioDeProduto(recarregarLista: () => void): void {
    const form = document.getElementById('form-produto') as HTMLFormElement | null;
    const mensagemEl = document.getElementById('produto-mensagem');
    const tituloFormEl = document.getElementById('produto-form-titulo');
    const botaoSubmitEl = document.getElementById('botao-produto-submit');
    const botaoCancelarEl = document.getElementById('botao-cancelar-edicao-produto');
    const idEl = document.getElementById('produto-id') as HTMLInputElement | null;

    if (!form || !mensagemEl || !tituloFormEl || !botaoSubmitEl || !botaoCancelarEl || !idEl) {
        return;
    }

    const mostrarMensagem = function (texto: string, tipo: 'erro' | 'sucesso'): void {
        mensagemEl.textContent = texto;
        mensagemEl.className = 'evento-mensagem ' + tipo;
    };

    /** Volta o formulário pro modo "criar produto novo". */
    const voltarParaModoCriacao = function (): void {
        form.reset();
        idEl.value = '';
        tituloFormEl.textContent = 'Adicionar Produto';
        botaoSubmitEl.textContent = 'Adicionar Produto';
        botaoCancelarEl.classList.add('escondido');
    };

    /** Carrega um produto no formulário e muda pro modo "editar". Chamado pelos botões "Editar" da lista. */
    const preencherParaEdicao = function (produto: ProdutoApi): void {
        idEl.value = String(produto.id);
        (document.getElementById('produto-codigo') as HTMLInputElement).value = produto.Codigo;
        (document.getElementById('produto-nome') as HTMLInputElement).value = produto.Nome;
        (document.getElementById('produto-categoria') as HTMLInputElement).value = produto.Categoria || '';
        (document.getElementById('produto-descricao') as HTMLTextAreaElement).value = produto.Descricao || '';
        (document.getElementById('produto-preco') as HTMLInputElement).value = String(produto.Preco);
        (document.getElementById('produto-quantidade') as HTMLInputElement).value = String(produto.Quantidade);

        const radioAtivo = document.querySelector(
            'input[name="ativo"][value="' + (produto.Ativo === 'n' ? 'n' : 's') + '"]'
        ) as HTMLInputElement | null;
        if (radioAtivo) {
            radioAtivo.checked = true;
        }

        tituloFormEl.textContent = 'Editar Produto';
        botaoSubmitEl.textContent = 'Salvar Alterações';
        botaoCancelarEl.classList.remove('escondido');
        mostrarMensagem('', 'sucesso');

        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
    };

    botaoCancelarEl.addEventListener('click', voltarParaModoCriacao);

    form.addEventListener('submit', function (evento) {
        // evita o envio "tradicional" do form (que recarregaria a página)
        evento.preventDefault();

        const emEdicao = idEl.value !== '';
        const url = emEdicao ? 'produtos-editar.php' : 'produtos-criar.php';

        const radioAtivoMarcado = document.querySelector('input[name="ativo"]:checked') as HTMLInputElement | null;

        const corpo: Record<string, string> = {
            codigo: (document.getElementById('produto-codigo') as HTMLInputElement).value,
            nome: (document.getElementById('produto-nome') as HTMLInputElement).value,
            categoria: (document.getElementById('produto-categoria') as HTMLInputElement).value,
            descricao: (document.getElementById('produto-descricao') as HTMLTextAreaElement).value,
            preco: (document.getElementById('produto-preco') as HTMLInputElement).value,
            quantidade: (document.getElementById('produto-quantidade') as HTMLInputElement).value,
            ativo: radioAtivoMarcado ? radioAtivoMarcado.value : 's',
            csrf_token: (document.getElementById('produto-csrf-token') as HTMLInputElement).value,
        };
        if (emEdicao) {
            corpo.id = idEl.value;
        }

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(corpo),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível salvar o produto.');
                    }
                    return dados;
                });
            })
            .then(function () {
                const mensagemSucesso = emEdicao ? 'Produto atualizado!' : 'Produto adicionado!';
                voltarParaModoCriacao();
                mostrarMensagem(mensagemSucesso, 'sucesso');
                recarregarLista();
            })
            .catch(function (erro: Error) {
                mostrarMensagem(erro.message, 'erro');
            });
    });

    // exposto no elemento do form pra ligarListaDeProdutos conseguir
    // chamar "editar" sem precisar duplicar a lógica de preenchimento
    (form as any).preencherParaEdicao = preencherParaEdicao;
}

/**
 * Liga a caixa "Buscar Produtos": clicar em "Buscar" (ou apertar
 * Enter no campo de texto, ou trocar o status) recarrega a lista de
 * resultados, que já manda os filtros atuais junto.
 */
function ligarBuscaDeProdutos(recarregarLista: () => void): void {
    const botaoBuscar = document.getElementById('botao-buscar-produtos');
    const textoEl = document.getElementById('busca-produto-texto');
    const statusEl = document.getElementById('busca-produto-status');

    if (!botaoBuscar) {
        return;
    }

    botaoBuscar.addEventListener('click', recarregarLista);

    if (textoEl) {
        textoEl.addEventListener('keydown', function (evento) {
            if ((evento as KeyboardEvent).key === 'Enter') {
                evento.preventDefault();
                recarregarLista();
            }
        });
    }

    // trocar o status já dispara a busca sozinho, sem precisar clicar em "Buscar"
    if (statusEl) {
        statusEl.addEventListener('change', recarregarLista);
    }
}

/**
 * Busca os produtos em dashboard/produtos-listar.php (com os filtros
 * atuais) e desenha a lista numerada #lista-produtos, um <li> por
 * produto, com os botões Editar/Excluir.
 */
function carregarListaDeProdutos(
    buscaTextoEl: HTMLInputElement | null,
    buscaStatusEl: HTMLSelectElement | null,
    recarregarLista: () => void
): void {
    const listaEl = document.getElementById('lista-produtos');
    const mensagemEl = document.getElementById('lista-produtos-mensagem');

    if (!listaEl) {
        return;
    }

    const parametros = new URLSearchParams({
        busca: buscaTextoEl ? buscaTextoEl.value : '',
        status: buscaStatusEl ? buscaStatusEl.value : '',
    });

    fetch('produtos-listar.php?' + parametros.toString())
        .then(function (resposta) {
            return resposta.json() as Promise<ProdutoApi[]>;
        })
        .then(function (produtos) {
            desenharListaDeProdutos(produtos, listaEl, mensagemEl, recarregarLista);
        })
        .catch(function () {
            if (mensagemEl) {
                mensagemEl.textContent = 'Não foi possível carregar a lista de produtos.';
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
}

/**
 * Monta os <li> da lista a partir dos produtos recebidos. Usa
 * textContent (nunca innerHTML) pros textos do produto, pra um nome/
 * descrição com caracteres de HTML não virar código na página.
 */
function desenharListaDeProdutos(
    produtos: ProdutoApi[],
    listaEl: HTMLElement,
    mensagemEl: HTMLElement | null,
    recarregarLista: () => void
): void {
    listaEl.innerHTML = '';

    if (mensagemEl) {
        mensagemEl.textContent = produtos.length === 0 ? 'Nenhum produto encontrado.' : '';
        mensagemEl.className = 'evento-mensagem';
    }

    produtos.forEach(function (produto) {
        const item = document.createElement('li');
        item.className = 'evento-item';

        const info = document.createElement('div');
        info.className = 'evento-item-info';

        const titulo = document.createElement('strong');
        titulo.textContent = produto.Codigo + ' — ' + produto.Nome;

        const detalhes = document.createElement('span');
        detalhes.textContent = formatarDetalhes(produto);

        info.appendChild(titulo);
        info.appendChild(detalhes);

        const acoes = document.createElement('div');
        acoes.className = 'evento-item-acoes';

        const botaoEditar = document.createElement('button');
        botaoEditar.type = 'button';
        botaoEditar.className = 'btn-editar';
        botaoEditar.textContent = 'Editar';
        botaoEditar.addEventListener('click', function () {
            const form = document.getElementById('form-produto') as any;
            if (form && typeof form.preencherParaEdicao === 'function') {
                form.preencherParaEdicao(produto);
            }
        });

        const botaoExcluir = document.createElement('button');
        botaoExcluir.type = 'button';
        botaoExcluir.className = 'btn-excluir';
        botaoExcluir.textContent = 'Excluir';
        ligarBotaoExcluir(botaoExcluir, produto, recarregarLista);

        acoes.appendChild(botaoEditar);
        acoes.appendChild(botaoExcluir);

        item.appendChild(info);
        item.appendChild(acoes);
        listaEl.appendChild(item);
    });
}

/**
 * Liga o botão "Excluir" de um item: o primeiro clique só marca
 * "confirmando" (troca o texto/cor, pra evitar excluir sem querer);
 * o segundo clique, dentro de 4 segundos, chama produtos-excluir.php
 * de verdade e recarrega a lista.
 */
function ligarBotaoExcluir(botao: HTMLButtonElement, produto: ProdutoApi, recarregarLista: () => void): void {
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

        const csrfToken = (document.getElementById('produto-csrf-token') as HTMLInputElement).value;

        fetch('produtos-excluir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: produto.id, csrf_token: csrfToken }),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível excluir o produto.');
                    }
                    return dados;
                });
            })
            .then(function () {
                recarregarLista();
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

/** Formata categoria, preço (R$) e quantidade em estoque numa linha só. */
function formatarDetalhes(produto: ProdutoApi): string {
    const preco = Number(produto.Preco).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    });
    const status = produto.Ativo === 'n' ? 'Inativo' : 'Ativo';
    const categoria = produto.Categoria ? produto.Categoria + ' · ' : '';

    return categoria + preco + ' · Estoque: ' + produto.Quantidade + ' · ' + status;
}

})();
