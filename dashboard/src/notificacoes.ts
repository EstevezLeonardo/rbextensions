/**
 * Sino de notificações do cabeçalho — incluído em toda página do
 * dashboard que usa o nav-top padrão (não só dashboard/servicos.php).
 * O botão em si já é um link pra lá no HTML de cada página; aqui só
 * busca a contagem de "e-mails novos" (dashboard/servicos-notificacoes.php,
 * que zera sozinha ao entrar em Serviços — não precisa abrir cada
 * e-mail) e mostra/esconde o número no badge (#notificacoes-badge).
 *
 * Caminho absoluto (/rbextensions/...) porque este script roda tanto
 * em páginas de dashboard/ quanto de usuarios/ (profundidades
 * diferentes) — mesmo padrão já usado em App\Session\Login pros
 * redirects de login/logout.
 *
 * Compilado por `tsc` (ver tsconfig.json na raiz do projeto) para
 * dashboard/public/assets/js/notificacoes.js.
 */
(function () {

document.addEventListener('DOMContentLoaded', function () {
    const badge = document.getElementById('notificacoes-badge');
    if (!badge) {
        return;
    }

    fetch('/rbextensions/dashboard/servicos-notificacoes.php')
        .then(function (resposta) {
            return resposta.json();
        })
        .then(function (dados) {
            const naoLidas = Number(dados.naoLidas) || 0;
            if (naoLidas > 0) {
                badge.textContent = naoLidas > 99 ? '99+' : String(naoLidas);
                badge.classList.remove('escondido');
            } else {
                badge.classList.add('escondido');
            }
        })
        .catch(function () {
            badge.classList.add('escondido');
        });
});

})();
