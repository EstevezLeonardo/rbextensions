/**
 * Agenda dinâmica (dashboard/agenda.php).
 *
 * Monta o calendário mensal do FullCalendar na div #calendar. A fonte
 * de eventos aponta para dashboard/eventos.php e manda junto os
 * filtros atuais da caixa "Buscar Eventos" (extraParams) — o próprio
 * FullCalendar chama essa URL via fetch sempre que precisa (carga
 * inicial, troca de mês, ou quando mandamos recarregar manualmente).
 *
 * Também liga:
 *   - o formulário "Adicionar Evento", que funciona tanto pra CRIAR
 *     quanto pra EDITAR um evento (o campo escondido #evento-id decide
 *     qual endpoint chamar: eventos-criar.php ou eventos-editar.php);
 *   - a caixa "Buscar Eventos", que recarrega o calendário E a lista
 *     de resultados abaixo;
 *   - a lista de resultados (#lista-eventos), com os botões "Editar"
 *     (carrega o evento no formulário acima) e "Excluir" (dois
 *     cliques: o primeiro pede confirmação, o segundo apaga de fato).
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/agenda.js. Carregado em agenda.php DEPOIS
 * dos scripts globais do FullCalendar (core + daygrid), pois depende
 * do objeto global `FullCalendar` que eles expõem.
 *
 * Tudo dentro de uma IIFE: como não usamos módulos (sem bundler), os
 * .ts compilados viram scripts globais — sem isso, nomes de função
 * repetidos entre agenda.ts e outras páginas (ex: controle-produtos.ts)
 * colidiriam no escopo global e o `tsc` recusaria compilar.
 */
(function () {

/** Formato de evento devolvido por dashboard/eventos.php. */
interface EventoApi {
    id: number | string;
    title: string;
    start: string;
    end: string;
}

document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');

    // se a div não existir por algum motivo, não há calendário pra montar
    if (!calendarEl) {
        return;
    }

    const buscaTituloEl = document.getElementById('busca-titulo') as HTMLInputElement | null;
    const buscaStatusEl = document.getElementById('busca-status') as HTMLSelectElement | null;

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        height: 'auto',

        events: {
            url: 'eventos.php',
            // reavaliado a cada busca (inclusive refetchEvents), sempre
            // com o valor atual dos campos de filtro
            extraParams: function () {
                return {
                    busca: buscaTituloEl ? buscaTituloEl.value : '',
                    status: buscaStatusEl ? buscaStatusEl.value : '',
                };
            },
        },
    });

    calendar.render();

    // recarrega o calendário E a lista de resultados juntos, sempre
    // com os filtros atuais da caixa "Buscar Eventos"
    const recarregarTudo = function (): void {
        calendar.refetchEvents();
        carregarListaDeEventos(buscaTituloEl, buscaStatusEl, recarregarTudo);
    };

    ligarFormularioDeEvento(recarregarTudo);
    ligarBuscaDeEventos(recarregarTudo);

    // primeira carga da lista (sem filtro nenhum, igual ao calendário)
    carregarListaDeEventos(buscaTituloEl, buscaStatusEl, recarregarTudo);
});

/**
 * Liga o envio do formulário "Adicionar/Editar Evento". Em modo
 * "criar" (#evento-id vazio) manda pra eventos-criar.php; em modo
 * "editar" (#evento-id preenchido, definido por preencherParaEdicao)
 * manda pra eventos-editar.php. Em ambos os casos, ao terminar com
 * sucesso volta pro modo "criar" e chama `recarregarTudo`.
 */
function ligarFormularioDeEvento(recarregarTudo: () => void): void {
    const form = document.getElementById('form-evento') as HTMLFormElement | null;
    const mensagemEl = document.getElementById('evento-mensagem');
    const tituloFormEl = document.getElementById('evento-form-titulo');
    const botaoSubmitEl = document.getElementById('botao-evento-submit');
    const botaoCancelarEl = document.getElementById('botao-cancelar-edicao');
    const idEl = document.getElementById('evento-id') as HTMLInputElement | null;

    if (!form || !mensagemEl || !tituloFormEl || !botaoSubmitEl || !botaoCancelarEl || !idEl) {
        return;
    }

    const mostrarMensagem = function (texto: string, tipo: 'erro' | 'sucesso'): void {
        mensagemEl.textContent = texto;
        mensagemEl.className = 'evento-mensagem ' + tipo;
    };

    /** Volta o formulário pro modo "criar evento novo". */
    const voltarParaModoCriacao = function (): void {
        form.reset();
        idEl.value = '';
        tituloFormEl.textContent = 'Adicionar Evento';
        botaoSubmitEl.textContent = 'Adicionar Evento';
        botaoCancelarEl.classList.add('escondido');
    };

    /** Carrega um evento no formulário e muda pro modo "editar". Chamado pelos botões "Editar" da lista. */
    const preencherParaEdicao = function (evento: EventoApi): void {
        idEl.value = String(evento.id);
        (document.getElementById('evento-titulo') as HTMLInputElement).value = evento.title;
        // datetime-local aceita "AAAA-MM-DDTHH:MM[:SS]" — o mesmo
        // formato que dashboard/eventos.php já devolve
        (document.getElementById('evento-inicio') as HTMLInputElement).value = evento.start;
        (document.getElementById('evento-fim') as HTMLInputElement).value = evento.end;

        tituloFormEl.textContent = 'Editar Evento';
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
        const url = emEdicao ? 'eventos-editar.php' : 'eventos-criar.php';

        const titulo = (document.getElementById('evento-titulo') as HTMLInputElement).value;
        const inicio = (document.getElementById('evento-inicio') as HTMLInputElement).value;
        const fim = (document.getElementById('evento-fim') as HTMLInputElement).value;
        const csrfToken = (document.getElementById('evento-csrf-token') as HTMLInputElement).value;

        const corpo: Record<string, string> = { titulo, inicio, fim, csrf_token: csrfToken };
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
                        throw new Error(dados.erro || 'Não foi possível salvar o evento.');
                    }
                    return dados;
                });
            })
            .then(function () {
                const mensagemSucesso = emEdicao ? 'Evento atualizado!' : 'Evento adicionado!';
                voltarParaModoCriacao();
                mostrarMensagem(mensagemSucesso, 'sucesso');
                recarregarTudo();
            })
            .catch(function (erro: Error) {
                mostrarMensagem(erro.message, 'erro');
            });
    });

    // exposto no elemento do form pra ligarListaDeEventos conseguir
    // chamar "editar" sem precisar duplicar a lógica de preenchimento
    (form as any).preencherParaEdicao = preencherParaEdicao;
}

/**
 * Liga a caixa "Buscar Eventos": clicar em "Buscar" (ou apertar Enter
 * no campo de título, ou trocar o status) recarrega o calendário e a
 * lista de resultados, que já mandam os filtros atuais junto.
 */
function ligarBuscaDeEventos(recarregarTudo: () => void): void {
    const botaoBuscar = document.getElementById('botao-buscar-eventos');
    const tituloEl = document.getElementById('busca-titulo');
    const statusEl = document.getElementById('busca-status');

    if (!botaoBuscar) {
        return;
    }

    botaoBuscar.addEventListener('click', recarregarTudo);

    if (tituloEl) {
        tituloEl.addEventListener('keydown', function (evento) {
            if ((evento as KeyboardEvent).key === 'Enter') {
                evento.preventDefault();
                recarregarTudo();
            }
        });
    }

    // trocar o status já dispara a busca sozinho, sem precisar clicar em "Buscar"
    if (statusEl) {
        statusEl.addEventListener('change', recarregarTudo);
    }
}

/**
 * Busca os eventos em dashboard/eventos.php (com os filtros atuais) e
 * desenha a lista numerada #lista-eventos, um <li> por evento, com os
 * botões Editar/Excluir.
 */
function carregarListaDeEventos(
    buscaTituloEl: HTMLInputElement | null,
    buscaStatusEl: HTMLSelectElement | null,
    recarregarTudo: () => void
): void {
    const listaEl = document.getElementById('lista-eventos');
    const mensagemEl = document.getElementById('lista-eventos-mensagem');

    if (!listaEl) {
        return;
    }

    const parametros = new URLSearchParams({
        busca: buscaTituloEl ? buscaTituloEl.value : '',
        status: buscaStatusEl ? buscaStatusEl.value : '',
    });

    fetch('eventos.php?' + parametros.toString())
        .then(function (resposta) {
            return resposta.json() as Promise<EventoApi[]>;
        })
        .then(function (eventos) {
            desenharListaDeEventos(eventos, listaEl, mensagemEl, recarregarTudo);
        })
        .catch(function () {
            if (mensagemEl) {
                mensagemEl.textContent = 'Não foi possível carregar a lista de eventos.';
                mensagemEl.className = 'evento-mensagem erro';
            }
        });
}

/**
 * Monta os <li> da lista a partir dos eventos recebidos. Usa
 * textContent (nunca innerHTML) para o título do evento, pra um
 * título com caracteres de HTML não virar código na página.
 */
function desenharListaDeEventos(
    eventos: EventoApi[],
    listaEl: HTMLElement,
    mensagemEl: HTMLElement | null,
    recarregarTudo: () => void
): void {
    listaEl.innerHTML = '';

    if (mensagemEl) {
        mensagemEl.textContent = eventos.length === 0 ? 'Nenhum evento encontrado.' : '';
        mensagemEl.className = 'evento-mensagem';
    }

    eventos.forEach(function (evento) {
        const item = document.createElement('li');
        item.className = 'evento-item';

        const info = document.createElement('div');
        info.className = 'evento-item-info';

        const titulo = document.createElement('strong');
        titulo.textContent = evento.title;

        const periodo = document.createElement('span');
        periodo.textContent = formatarPeriodo(evento.start, evento.end);

        info.appendChild(titulo);
        info.appendChild(periodo);

        const acoes = document.createElement('div');
        acoes.className = 'evento-item-acoes';

        const botaoEditar = document.createElement('button');
        botaoEditar.type = 'button';
        botaoEditar.className = 'btn-editar';
        botaoEditar.textContent = 'Editar';
        botaoEditar.addEventListener('click', function () {
            const form = document.getElementById('form-evento') as any;
            if (form && typeof form.preencherParaEdicao === 'function') {
                form.preencherParaEdicao(evento);
            }
        });

        const botaoExcluir = document.createElement('button');
        botaoExcluir.type = 'button';
        botaoExcluir.className = 'btn-excluir';
        botaoExcluir.textContent = 'Excluir';
        ligarBotaoExcluir(botaoExcluir, evento, recarregarTudo);

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
 * o segundo clique, dentro de 4 segundos, chama eventos-excluir.php
 * de verdade e recarrega a lista/calendário.
 */
function ligarBotaoExcluir(botao: HTMLButtonElement, evento: EventoApi, recarregarTudo: () => void): void {
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

        const csrfToken = (document.getElementById('evento-csrf-token') as HTMLInputElement).value;

        fetch('eventos-excluir.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: evento.id, csrf_token: csrfToken }),
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    if (!resposta.ok) {
                        throw new Error(dados.erro || 'Não foi possível excluir o evento.');
                    }
                    return dados;
                });
            })
            .then(function () {
                // recarrega calendário + lista pra refletir a exclusão em todo lugar
                recarregarTudo();
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

/** Formata "AAAA-MM-DDTHH:MM[:SS]" pra "dd/mm/aaaa hh:mm", em pt-BR. */
function formatarPeriodo(inicioIso: string, fimIso: string): string {
    const formatarData = function (iso: string): string {
        return new Date(iso).toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return formatarData(inicioIso) + ' – ' + formatarData(fimIso);
}

})();
