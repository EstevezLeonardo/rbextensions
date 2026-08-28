"use strict";
/**
 * Vendas (dashboard/vendas.php).
 *
 * Só gestão/consulta do extrato — sem registrar venda nova por aqui
 * (ver o comentário no topo de dashboard/vendas.php). Liga:
 *   - a caixa "Buscar no Extrato" (cliente ou produto), que recarrega o
 *     extrato a partir da página 1;
 *   - o extrato (#lista-vendas), paginado em 10 por página
 *     (dashboard/vendas-listar.php) — só leitura, sem editar/excluir
 *     (venda é registro financeiro, não se desfaz por aqui).
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/vendas.js.
 *
 * Dentro de uma IIFE, como os demais .ts do dashboard (ver o comentário
 * equivalente em controle-produtos.ts).
 */
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        const buscaTextoEl = document.getElementById('busca-venda-texto');
        const carregarPagina = function (pagina) {
            carregarExtrato(buscaTextoEl, pagina, carregarPagina);
        };
        // começa a busca do zero, na página 1 (usado ao mudar a busca)
        const buscarDoInicio = function () {
            carregarPagina(1);
        };
        ligarBuscaDeVendas(buscarDoInicio);
        buscarDoInicio();
    });
    /**
     * Liga a caixa "Buscar no Extrato": clicar em "Buscar" (ou apertar
     * Enter no campo) volta o extrato pra página 1, já com a busca atual.
     */
    function ligarBuscaDeVendas(buscarDoInicio) {
        const botaoBuscar = document.getElementById('botao-buscar-vendas');
        const textoEl = document.getElementById('busca-venda-texto');
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
    }
    /**
     * Busca a página do extrato em dashboard/vendas-listar.php (com a
     * busca e a página atuais) e desenha as linhas da tabela
     * #lista-vendas, mais os botões de página em #paginacao-vendas.
     */
    function carregarExtrato(buscaTextoEl, pagina, irParaPagina) {
        const corpoTabelaEl = document.getElementById('lista-vendas');
        const mensagemEl = document.getElementById('lista-vendas-mensagem');
        if (!corpoTabelaEl) {
            return;
        }
        const parametros = new URLSearchParams({
            busca: buscaTextoEl ? buscaTextoEl.value : '',
            pagina: String(pagina),
        });
        fetch('vendas-listar.php?' + parametros.toString())
            .then(function (resposta) {
            return resposta.json();
        })
            .then(function (dados) {
            desenharExtrato(dados.itens, corpoTabelaEl, mensagemEl);
            desenharPaginacao(dados.paginaAtual, dados.totalPaginas, irParaPagina);
        })
            .catch(function () {
            if (mensagemEl) {
                mensagemEl.textContent = 'Não foi possível carregar o extrato de vendas.';
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
    }
    /**
     * Monta as <tr> da tabela a partir dos itens recebidos. Usa
     * textContent (nunca innerHTML) pros textos, pra um nome de
     * cliente/produto com caracteres de HTML não virar código na página.
     */
    function desenharExtrato(itens, corpoTabelaEl, mensagemEl) {
        corpoTabelaEl.innerHTML = '';
        if (mensagemEl) {
            mensagemEl.textContent = itens.length === 0 ? 'Nenhuma venda encontrada.' : '';
            mensagemEl.className = 'evento-mensagem';
        }
        itens.forEach(function (item) {
            const linha = document.createElement('tr');
            const celulas = [
                item.ClienteNome,
                item.ProdutoCodigo + ' — ' + item.ProdutoNome,
                String(item.Quantidade),
                new Date(item.VendaData.replace(' ', 'T')).toLocaleString('pt-BR'),
                formatarMoeda(Number(item.ValorUnitario)),
                formatarMoeda(Number(item.VendaValorTotal)),
            ];
            celulas.forEach(function (texto) {
                const celula = document.createElement('td');
                celula.textContent = texto;
                linha.appendChild(celula);
            });
            corpoTabelaEl.appendChild(linha);
        });
    }
    /**
     * Desenha os botões de página em #paginacao-vendas (um por página; a
     * página atual fica desabilitada). Não mostra nada quando só existe
     * uma página.
     */
    function desenharPaginacao(paginaAtual, totalPaginas, irParaPagina) {
        const container = document.getElementById('paginacao-vendas');
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
    /** Formata um número como moeda brasileira (R$). */
    function formatarMoeda(valor) {
        return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }
})();
