/**
 * Declaração "ambiente" do objeto global FullCalendar.
 *
 * Carregamos o FullCalendar como script global — arquivos vendorizados
 * em dashboard/public/assets/js/fullcalendar/ (baixados via npm e
 * copiados manualmente, sem bundler) — em vez de usar `import`. Como
 * não importamos o pacote, o TypeScript não enxerga os tipos oficiais
 * da biblioteca (esses tipos são pensados pra quem importa via
 * módulos). Aqui só avisamos o compilador que `FullCalendar` existe
 * em tempo de execução; `any` troca checagem detalhada da API do
 * FullCalendar por simplicidade, já que optamos por não usar bundler.
 */
declare const FullCalendar: any;
