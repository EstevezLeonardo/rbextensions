<?php

/**
 * Endpoint JSON da agenda: devolve os eventos da tabela `eventos` no
 * formato que o FullCalendar espera (title/start/end). Consumido por
 * dashboard/agenda.php através da opção `events` do FullCalendar
 * (ver dashboard/src/agenda.ts) — não é uma página pra visitar
 * diretamente pelo menu, só uma fonte de dados que o JavaScript da
 * agenda busca sozinho.
 *
 * Aceita dois filtros opcionais via querystring, vindos da caixa
 * "Buscar Eventos":
 *   - busca:  texto a procurar no título (LIKE)
 *   - status: "futuro" (Inicio ainda não chegou) ou "encerrado"
 *             (Fim já passou), comparado com a hora atual do banco
 *
 * Exige login, como o restante do sistema.
 */

require __DIR__.'/../vendor/autoload.php';

use App\Entity\Evento;
use App\Session\Login;

Login::requireLogin();

$busca = trim($_GET['busca'] ?? '');
$status = trim($_GET['status'] ?? '');

$condicoes = [];
$params = [];

if ($busca !== '') {
    $condicoes[] = 'Titulo LIKE ?';
    $params[] = "%{$busca}%";
}

if ($status === 'futuro') {
    $condicoes[] = 'Inicio >= NOW()';
} elseif ($status === 'encerrado') {
    $condicoes[] = 'Fim < NOW()';
}

$where = implode(' AND ', $condicoes);

$eventos = Evento::getEventos($where, $params);

// o FullCalendar espera as chaves em minúsculo (title/start/end) e
// datas no formato ISO 8601 (com "T" separando data e hora); aqui só
// traduzimos os nomes/formato das colunas do banco pro que ele espera
$eventosFormatados = array_map(function (Evento $evento) {
    return [
        'id' => $evento->id,
        'title' => $evento->Titulo,
        'start' => str_replace(' ', 'T', $evento->Inicio),
        'end' => str_replace(' ', 'T', $evento->Fim),
    ];
}, $eventos);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($eventosFormatados);
