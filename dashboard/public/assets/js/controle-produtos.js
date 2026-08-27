"use strict";
/**
 * Controle de Produtos (dashboard/controle-produtos.php).
 *
 * Liga:
 *   - o formulário "Adicionar Produto", que funciona tanto pra CRIAR
 *     quanto pra EDITAR um produto (o campo escondido #produto-id
 *     decide qual endpoint chamar: produtos-criar.php ou
 *     produtos-editar.php);
 *   - a caixa "Buscar Produtos" (texto, status e categoria), que
 *     recarrega a lista de resultados a partir da página 1;
 *   - a lista de resultados (#lista-produtos), paginada em 10 por
 *     página (dashboard/produtos-listar.php), com os botões "Editar"
 *     (carrega o produto no formulário acima) e "Excluir" (dois
 *     cliques: o primeiro pede confirmação, o segundo apaga de fato);
 *   - os botões de página (#paginacao-produtos): editar/excluir
 *     recarregam a MESMA página em que a pessoa está; já criar um
 *     produto ou mudar a busca volta pra página 1.
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
    document.addEventListener('DOMContentLoaded', function () {
        const buscaTextoEl = document.getElementById('busca-produto-texto');
        const buscaStatusEl = document.getElementById('busca-produto-status');
        const buscaCategoriaEl = document.getElementById('busca-produto-categoria');
        let paginaAtual = 1;
        const carregarPagina = function (pagina) {
            paginaAtual = pagina;
            carregarListaDeProdutos(buscaTextoEl, buscaStatusEl, buscaCategoriaEl, pagina, carregarPagina, recarregarPaginaAtual);
        };
        // recarrega a mesma página em que a pessoa está (usado depois de editar/excluir um item)
        const recarregarPaginaAtual = function () {
            carregarPagina(paginaAtual);
        };
        // começa a busca do zero, na página 1 (usado ao mudar filtros ou criar um produto)
        const buscarDoInicio = function () {
            carregarPagina(1);
        };
        ligarFormularioDeProduto(recarregarPaginaAtual, buscarDoInicio);
        ligarBuscaDeProdutos(buscarDoInicio);
        buscarDoInicio();
    });
    /**
     * Liga o envio do formulário "Adicionar/Editar Produto". Em modo
     * "criar" (#produto-id vazio) manda pra produtos-criar.php e, ao
     * terminar, chama `aoCriar` (volta pra página 1); em modo "editar"
     * (#produto-id preenchido, definido por preencherParaEdicao) manda
     * pra produtos-editar.php e chama `aoEditar` (fica na mesma página).
     */
    function ligarFormularioDeProduto(aoEditar, aoCriar) {
        const form = document.getElementById('form-produto');
        const mensagemEl = document.getElementById('produto-mensagem');
        const tituloFormEl = document.getElementById('produto-form-titulo');
        const botaoSubmitEl = document.getElementById('botao-produto-submit');
        const botaoCancelarEl = document.getElementById('botao-cancelar-edicao-produto');
        const idEl = document.getElementById('produto-id');
        if (!form || !mensagemEl || !tituloFormEl || !botaoSubmitEl || !botaoCancelarEl || !idEl) {
            return;
        }
        const mostrarMensagem = function (texto, tipo) {
            mensagemEl.textContent = texto;
            mensagemEl.className = 'evento-mensagem ' + tipo;
        };
        /** Volta o formulário pro modo "criar produto novo". */
        const voltarParaModoCriacao = function () {
            form.reset();
            idEl.value = '';
            tituloFormEl.textContent = 'Adicionar Produto';
            botaoSubmitEl.textContent = 'Adicionar Produto';
            botaoCancelarEl.classList.add('escondido');
        };
        /** Carrega um produto no formulário e muda pro modo "editar". Chamado pelos botões "Editar" da lista. */
        const preencherParaEdicao = function (produto) {
            idEl.value = String(produto.id);
            document.getElementById('produto-codigo').value = produto.Codigo;
            document.getElementById('produto-nome').value = produto.Nome;
            document.getElementById('produto-categoria').value = produto.Categoria || '';
            document.getElementById('produto-descricao').value = produto.Descricao || '';
            document.getElementById('produto-preco').value = String(produto.Preco);
            document.getElementById('produto-quantidade').value = String(produto.Quantidade);
            const radioAtivo = document.querySelector('input[name="ativo"][value="' + (produto.Ativo === 'n' ? 'n' : 's') + '"]');
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
            const radioAtivoMarcado = document.querySelector('input[name="ativo"]:checked');
            const corpo = {
                codigo: document.getElementById('produto-codigo').value,
                nome: document.getElementById('produto-nome').value,
                categoria: document.getElementById('produto-categoria').value,
                descricao: document.getElementById('produto-descricao').value,
                preco: document.getElementById('produto-preco').value,
                quantidade: document.getElementById('produto-quantidade').value,
                ativo: radioAtivoMarcado ? radioAtivoMarcado.value : 's',
                csrf_token: document.getElementById('produto-csrf-token').value,
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
                if (emEdicao) {
                    aoEditar();
                }
                else {
                    aoCriar();
                }
            })
                .catch(function (erro) {
                mostrarMensagem(erro.message, 'erro');
            });
        });
        // exposto no elemento do form pra ligarListaDeProdutos conseguir
        // chamar "editar" sem precisar duplicar a lógica de preenchimento
        form.preencherParaEdicao = preencherParaEdicao;
    }
    /**
     * Liga a caixa "Buscar Produtos": clicar em "Buscar" (ou apertar
     * Enter no campo de texto, ou trocar o status/categoria) volta a
     * lista de resultados pra página 1, já com os filtros atuais.
     */
    function ligarBuscaDeProdutos(buscarDoInicio) {
        const botaoBuscar = document.getElementById('botao-buscar-produtos');
        const textoEl = document.getElementById('busca-produto-texto');
        const statusEl = document.getElementById('busca-produto-status');
        const categoriaEl = document.getElementById('busca-produto-categoria');
        if (!botaoBuscar) {
            return;
        }
        botaoBuscar.addEventListener('click', buscarDoInicio);
        if (textoEl) {
            textoEl.addEventListener('keydown', function (evento) {
                if (evento.key === 'Enter') {
                    evento.preventDefault();
                    buscarDoInicio();
                }
            });
        }
        // trocar status/categoria já dispara a busca sozinho, sem precisar clicar em "Buscar"
        if (statusEl) {
            statusEl.addEventListener('change', buscarDoInicio);
        }
        if (categoriaEl) {
            categoriaEl.addEventListener('change', buscarDoInicio);
        }
    }
    /**
     * Busca a página de produtos em dashboard/produtos-listar.php (com os
     * filtros e a página atuais) e desenha a lista numerada
     * #lista-produtos, mais os botões de página em #paginacao-produtos.
     */
    function carregarListaDeProdutos(buscaTextoEl, buscaStatusEl, buscaCategoriaEl, pagina, irParaPagina, recarregarPaginaAtual) {
        const listaEl = document.getElementById('lista-produtos');
        const mensagemEl = document.getElementById('lista-produtos-mensagem');
        if (!listaEl) {
            return;
        }
        const parametros = new URLSearchParams({
            busca: buscaTextoEl ? buscaTextoEl.value : '',
            status: buscaStatusEl ? buscaStatusEl.value : '',
            categoria: buscaCategoriaEl ? buscaCategoriaEl.value : '',
            pagina: String(pagina),
        });
        fetch('produtos-listar.php?' + parametros.toString())
            .then(function (resposta) {
            return resposta.json();
        })
            .then(function (dados) {
            desenharListaDeProdutos(dados.produtos, listaEl, mensagemEl, recarregarPaginaAtual);
            desenharPaginacao(dados.paginaAtual, dados.totalPaginas, irParaPagina);
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
    function desenharListaDeProdutos(produtos, listaEl, mensagemEl, recarregarPaginaAtual) {
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
                const form = document.getElementById('form-produto');
                if (form && typeof form.preencherParaEdicao === 'function') {
                    form.preencherParaEdicao(produto);
                }
            });
            const botaoExcluir = document.createElement('button');
            botaoExcluir.type = 'button';
            botaoExcluir.className = 'btn-excluir';
            botaoExcluir.textContent = 'Excluir';
            ligarBotaoExcluir(botaoExcluir, produto, recarregarPaginaAtual);
            acoes.appendChild(botaoEditar);
            acoes.appendChild(botaoExcluir);
            item.appendChild(info);
            item.appendChild(acoes);
            listaEl.appendChild(item);
        });
    }
    /**
     * Desenha os botões de página em #paginacao-produtos (um por página;
     * a página atual fica desabilitada). Não mostra nada quando só existe
     * uma página.
     */
    function desenharPaginacao(paginaAtual, totalPaginas, irParaPagina) {
        const container = document.getElementById('paginacao-produtos');
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
     * "confirmando" (troca o texto/cor, pra evitar excluir sem querer);
     * o segundo clique, dentro de 4 segundos, chama produtos-excluir.php
     * de verdade e recarrega a página atual da lista.
     */
    function ligarBotaoExcluir(botao, produto, recarregarPaginaAtual) {
        let confirmando = false;
        let timeoutId;
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
            const csrfToken = document.getElementById('produto-csrf-token').value;
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
                recarregarPaginaAtual();
            })
                .catch(function (erro) {
                botao.disabled = false;
                botao.textContent = 'Excluir';
                botao.classList.remove('confirmando');
                confirmando = false;
                alert(erro.message);
            });
        });
    }
    /** Formata categoria, preço (R$) e quantidade em estoque numa linha só. */
    function formatarDetalhes(produto) {
        const preco = Number(produto.Preco).toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL',
        });
        const status = produto.Ativo === 'n' ? 'Inativo' : 'Ativo';
        const categoria = produto.Categoria ? produto.Categoria + ' · ' : '';
        return categoria + preco + ' · Estoque: ' + produto.Quantidade + ' · ' + status;
    }
})();
