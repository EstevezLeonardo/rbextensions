/**
 * Financeiro (dashboard/financeiro.php).
 *
 * Liga:
 *   - o filtro de período (#financeiro-de/#financeiro-ate + "Filtrar"),
 *     que recarrega tanto o resumo quanto a lista de vendas abaixo, os
 *     dois pro mesmo período;
 *   - o resumo (#resumo-financeiro), uma linha só com os 4 totais
 *     (dashboard/financeiro-resumo.php);
 *   - a lista de vendas do período (#lista-financeiro-vendas), paginada
 *     em 10 por página (dashboard/financeiro-vendas-listar.php), com o
 *     botão "Marcar como Estornada" por linha (dois cliques: o primeiro
 *     pede confirmação, o segundo extorna de verdade via
 *     dashboard/financeiro-estornar.php e recarrega resumo + lista).
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/financeiro.js.
 *
 * Dentro de uma IIFE, como os demais .ts do dashboard (ver o comentário
 * equivalente em controle-produtos.ts).
 */
(function () {

/** Resumo devolvido por dashboard/financeiro-resumo.php. */
interface ResumoFinanceiroApi {
    total: number | string;
    extornado: number | string;
    debitoPix: number | string;
    credito: number | string;
    saida: number | string;
}

/** Uma venda devolvida por dashboard/financeiro-vendas-listar.php. */
interface VendaApi {
    id: number | string;
    ClienteNome: string;
    Data: string;
    ValorTotal: number | string;
    FormaPagamento: string;
    Status: string;
}

/** Formato da resposta de dashboard/financeiro-vendas-listar.php. */
interface ListaVendasResposta {
    vendas: VendaApi[];
    paginaAtual: number;
    totalPaginas: number;
}

document.addEventListener('DOMContentLoaded', function () {
    const deEl = document.getElementById('financeiro-de') as HTMLInputElement | null;
    const ateEl = document.getElementById('financeiro-ate') as HTMLInputElement | null;

    const carregarPagina = function (pagina: number): void {
        carregarVendas(deEl, ateEl, pagina, carregarPagina, recarregarTudo);
    };

    // recarrega resumo + primeira página da lista (usado ao filtrar ou depois de um estorno)
    const recarregarTudo = function (): void {
        carregarResumo(deEl, ateEl);
        carregarPagina(1);
    };

    ligarFiltro(recarregarTudo);

    recarregarTudo();
});

/**
 * Liga o botão "Filtrar": recarrega resumo + lista com as datas atuais
 * dos campos De/Até. Também reage ao Enter em qualquer um dos dois.
 */
function ligarFiltro(recarregarTudo: () => void): void {
    const botao = document.getElementById('botao-filtrar-financeiro');
    const deEl = document.getElementById('financeiro-de');
    const ateEl = document.getElementById('financeiro-ate');

    if (!botao) {
        return;
    }

    botao.addEventListener('click', recarregarTudo);

    [deEl, ateEl].forEach(function (el) {
        if (!el) {
            return;
        }
        el.addEventListener('keydown', function (evento) {
            if ((evento as KeyboardEvent).key === 'Enter') {
                evento.preventDefault();
                recarregarTudo();
            }
        });
    });
}

/** Monta a querystring de período (de/ate) a partir dos campos do filtro. */
function parametrosDePeriodo(deEl: HTMLInputElement | null, ateEl: HTMLInputElement | null): URLSearchParams {
    return new URLSearchParams({
        de: deEl ? deEl.value : '',
        ate: ateEl ? ateEl.value : '',
    });
}

/**
 * Busca o resumo em dashboard/financeiro-resumo.php (com o período
 * atual) e desenha a única linha de #resumo-financeiro.
 */
function carregarResumo(deEl: HTMLInputElement | null, ateEl: HTMLInputElement | null): void {
    const corpoTabelaEl = document.getElementById('resumo-financeiro');
    if (!corpoTabelaEl) {
        return;
    }

    fetch('financeiro-resumo.php?' + parametrosDePeriodo(deEl, ateEl).toString())
        .then(function (resposta) {
            return resposta.json() as Promise<ResumoFinanceiroApi>;
        })
        .then(function (resumo) {
            corpoTabelaEl.innerHTML = '';
            const linha = document.createElement('tr');

            [resumo.total, resumo.extornado, resumo.debitoPix, resumo.credito, resumo.saida].forEach(function (valor) {
                const celula = document.createElement('td');
                celula.textContent = formatarMoeda(Number(valor));
                linha.appendChild(celula);
            });

            corpoTabelaEl.appendChild(linha);
        })
        .catch(function () {
            corpoTabelaEl.innerHTML = '<tr><td colspan="5">Não foi possível carregar o resumo financeiro.</td></tr>';
        });
}

/**
 * Busca a página de vendas em dashboard/financeiro-vendas-listar.php
 * (com o período e a página atuais) e desenha as linhas da tabela
 * #lista-financeiro-vendas, mais os botões de página em
 * #paginacao-financeiro-vendas.
 */
function carregarVendas(
    deEl: HTMLInputElement | null,
    ateEl: HTMLInputElement | null,
    pagina: number,
    irParaPagina: (pagina: number) => void,
    aoEstornar: () => void
): void {
    const corpoTabelaEl = document.getElementById('lista-financeiro-vendas');
    const mensagemEl = document.getElementById('lista-financeiro-vendas-mensagem');

    if (!corpoTabelaEl) {
        return;
    }

    const parametros = parametrosDePeriodo(deEl, ateEl);
    parametros.set('pagina', String(pagina));

    fetch('financeiro-vendas-listar.php?' + parametros.toString())
        .then(function (resposta) {
            return resposta.json() as Promise<ListaVendasResposta>;
        })
        .then(function (dados) {
            desenharVendas(dados.vendas, corpoTabelaEl, mensagemEl, aoEstornar);
            desenharPaginacao(dados.paginaAtual, dados.totalPaginas, irParaPagina);
        })
        .catch(function () {
            if (mensagemEl) {
                mensagemEl.textContent = 'Não foi possível carregar a lista de vendas.';
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
}

const FORMAS_PAGAMENTO: Record<string, string> = {
    debito: 'Débito',
    pix: 'PIX',
    credito: 'Cartão de Crédito',
};

const STATUS_VENDA: Record<string, string> = {
    concluida: 'Concluída',
    extornada: 'Extornada',
};

/**
 * Monta as <tr> da tabela a partir das vendas recebidas. Usa
 * textContent (nunca innerHTML) pros textos, pra um nome de cliente com
 * caracteres de HTML não virar código na página.
 */
function desenharVendas(
    vendas: VendaApi[],
    corpoTabelaEl: HTMLElement,
    mensagemEl: HTMLElement | null,
    aoEstornar: () => void
): void {
    corpoTabelaEl.innerHTML = '';

    if (mensagemEl) {
        mensagemEl.textContent = vendas.length === 0 ? 'Nenhuma venda encontrada no período.' : '';
        mensagemEl.className = 'evento-mensagem';
    }

    vendas.forEach(function (venda) {
        const linha = document.createElement('tr');

        [
            venda.ClienteNome,
            new Date(venda.Data.replace(' ', 'T')).toLocaleString('pt-BR'),
            formatarMoeda(Number(venda.ValorTotal)),
            FORMAS_PAGAMENTO[venda.FormaPagamento] || venda.FormaPagamento,
            STATUS_VENDA[venda.Status] || venda.Status,
        ].forEach(function (texto) {
            const celula = document.createElement('td');
            celula.textContent = texto;
            linha.appendChild(celula);
        });

        const celulaAcoes = document.createElement('td');
        if (venda.Status !== 'extornada') {
            const botaoEstornar = document.createElement('button');
            botaoEstornar.type = 'button';
            botaoEstornar.className = 'btn-excluir';
            botaoEstornar.textContent = 'Marcar como Estornada';
            ligarBotaoEstornar(botaoEstornar, venda, aoEstornar);
            celulaAcoes.appendChild(botaoEstornar);
        }
        linha.appendChild(celulaAcoes);

        corpoTabelaEl.appendChild(linha);
    });
}

/**
 * Liga o botão "Marcar como Estornada" de uma venda: o primeiro clique
 * só marca "confirmando" (troca o texto/cor, pra evitar estornar sem
 * querer); o segundo clique, dentro de 4 segundos, chama
 * dashboard/financeiro-estornar.php de verdade e recarrega resumo +
 * lista.
 */
function ligarBotaoEstornar(botao: HTMLButtonElement, venda: VendaApi, aoEstornar: () => void): void {
    let confirmando = false;
    let timeoutId: number | undefined;

    botao.addEventListener('click', function () {
        if (!confirmando) {
            confirmando = true;
            botao.textContent = 'Confirmar estorno?';
            botao.classList.add('confirmando');

            timeoutId = window.setTimeout(function () {
                confirmando = false;
                botao.textContent = 'Marcar como Estornada';
                botao.classList.remove('confirmando');
            }, 4000);

            return;
        }

        window.clearTimeout(timeoutId);
        botao.disabled = true;

        const csrfToken = (document.getElementById('financeiro-csrf-token') as HTMLInputElement).value;

        fetch('financeiro-estornar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: venda.id, csrf_token: csrfToken }),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível marcar a venda como estornada.');
                    }
                    return dados;
                });
            })
            .then(function () {
                aoEstornar();
            })
            .catch(function (erro: Error) {
                botao.disabled = false;
                botao.textContent = 'Marcar como Estornada';
                botao.classList.remove('confirmando');
                confirmando = false;
                alert(erro.message);
            });
    });
}

/**
 * Desenha os botões de página em #paginacao-financeiro-vendas (um por
 * página; a página atual fica desabilitada). Não mostra nada quando só
 * existe uma página.
 */
function desenharPaginacao(paginaAtual: number, totalPaginas: number, irParaPagina: (pagina: number) => void): void {
    const container = document.getElementById('paginacao-financeiro-vendas');
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
function formatarMoeda(valor: number): string {
    return valor.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

})();
