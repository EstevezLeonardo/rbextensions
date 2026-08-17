<?php

namespace App\Db;

class Pagination{

    private $limit;

    private $results;

    private $pages;

    private $currentPage;

    public function __construct($results, $currentPage = 1, $limit = 10){

            $this->results = (int) $results;

            $this->limit = (int) $limit;

            // use && and cast to int to avoid precedence issues and ensure numeric values
            $this->currentPage = (is_numeric($currentPage) && $currentPage > 0) ? (int) $currentPage : 1;

            $this->calculate();
        }

    private function calculate(){
        
        $this->pages = $this->results > 0 ? ceil($this->results / $this->limit) : 1;

        $this->currentPage = ($this->currentPage > $this->pages ? $this->pages : $this->currentPage);

    }

    public function getLimit(){
            $offset = (int) ($this->limit * ($this->currentPage - 1));
            $limit = (int) $this->limit;
            return $offset . ', ' . $limit;

        } 

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