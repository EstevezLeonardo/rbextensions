<?php

namespace App\Db;

/**
 * Calcula os dados de paginação (offset/limit para o SQL e a lista de
 * páginas para renderizar os links) a partir do total de resultados,
 * da página atual e de quantos itens mostrar por página.
 */
class Pagination{

    /** @var int Quantidade de itens por página. */
    private $limit;

    /** @var int Total de resultados encontrados (sem paginação). */
    private $results;

    /** @var int Número total de páginas. */
    private $pages;

    /** @var int Página atualmente selecionada. */
    private $currentPage;

    public function __construct($results, $currentPage = 1, $limit = 10){

            $this->results = (int) $results;

            $this->limit = (int) $limit;

            // use && e cast pra int pra evitar problema de precedencia e garantir valor numerico
            $this->currentPage = (is_numeric($currentPage) && $currentPage > 0) ? (int) $currentPage : 1;

            $this->calculate();
        }

    /**
     * Calcula o total de páginas com base em $results/$limit e "trava"
     * a página atual no máximo permitido (evita currentPage além do fim).
     */
    private function calculate(){

        $this->pages = $this->results > 0 ? ceil($this->results / $this->limit) : 1;

        $this->currentPage = ($this->currentPage > $this->pages ? $this->pages : $this->currentPage);

    }

    /**
     * Monta o trecho "offset, limit" pronto para uso em `LIMIT` do SQL,
     * baseado na página atual.
     */
    public function getLimit(){
            $offset = (int) ($this->limit * ($this->currentPage - 1));
            $limit = (int) $this->limit;
            return $offset . ', ' . $limit;

        }

    /**
     * Retorna a lista de páginas para renderizar os links de paginação,
     * cada uma com o número e se é a página atual. Retorna array vazio
     * quando só existe uma página (não há o que paginar).
     */
    public function getPages(){
        if($this->pages == 1) return[];

        $paginas = [];
        for($i = 1; $i <= $this->pages; $i++){
            $paginas[] = [
                'pagina' => $i,
                'atual' => ($i == $this->currentPage)
            ];
        }

        return $paginas;

    }


}
