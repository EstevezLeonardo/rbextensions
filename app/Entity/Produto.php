<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa um produto do catálogo (tabela `produtos`: id, Codigo,
 * Nome, Descricao, Categoria, Preco, Quantidade, Ativo).
 */
class Produto{
    public $id;
    public $Codigo;
    public $Nome;
    public $Descricao;
    public $Categoria;
    public $Preco;
    public $Quantidade;
    public $Ativo;

    /**
     * Busca produtos, ordenados por nome. $where deve usar
     * placeholders `?` (ex: 'Nome LIKE ?'), com os valores em
     * $params — mesma regra de Vaga::getVagas()/Evento::getEventos().
     * $limit é o trecho "offset, limit" de Pagination::getLimit().
     *
     * @return Produto[]
     */
    public static function getProdutos($where = null, $params = [], $limit = null){
        return (new Database('produtos'))->select($where, 'Nome ASC', $limit, '*', $params)
                                          ->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Conta quantos produtos casam com $where (mesma regra de
     * placeholders/params de getProdutos). Usado para calcular a
     * paginação.
     */
    public static function getTotalProdutos($where = null, $params = []){
        return (new Database('produtos'))->select($where, null, null, 'COUNT(*) as total', $params)
                                          ->fetchObject()->total;
    }

    /**
     * Lista as categorias distintas já usadas em produtos cadastrados
     * (em ordem alfabética), pra popular o filtro "Buscar Produtos".
     *
     * @return string[]
     */
    public static function getCategorias(){
        return (new Database('produtos'))->select(
                "Categoria IS NOT NULL AND Categoria != ''",
                'Categoria ASC',
                null,
                'DISTINCT Categoria'
            )->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Busca um único produto pelo id. Retorna null se não existir. */
    public static function getProduto($id){
        return (new Database('produtos'))->select('id = ?', null, null, '*', [$id])
                                           ->fetchObject(self::class);
    }

    /**
     * Verifica se já existe outro produto com esse código (Codigo é
     * UNIQUE no banco). $idIgnorar exclui o próprio produto da
     * checagem — usado na edição, pra não acusar conflito com ele
     * mesmo quando o código não muda.
     */
    public static function codigoExiste($codigo, $idIgnorar = null){
        if ($idIgnorar !== null) {
            $existentes = self::getProdutos('Codigo = ? AND id != ?', [$codigo, $idIgnorar]);
        } else {
            $existentes = self::getProdutos('Codigo = ?', [$codigo]);
        }
        return count($existentes) > 0;
    }

    /**
     * Insere este produto no banco, usando os valores já atribuídos.
     * Preenche $this->id com o id gerado.
     */
    public function cadastrar(){
        $obDatabase = new Database('produtos');
        $this->id = $obDatabase->insert([
            'Codigo' => $this->Codigo,
            'Nome' => $this->Nome,
            'Descricao' => $this->Descricao,
            'Categoria' => $this->Categoria,
            'Preco' => $this->Preco,
            'Quantidade' => $this->Quantidade,
            'Ativo' => $this->Ativo,
        ]);
        return true;
    }

    /**
     * Atualiza este produto (identificado por $this->id) com os
     * valores atuais das propriedades.
     */
    public function atualizar(){
        return (new Database('produtos'))->update('id = ?', [
            'Codigo' => $this->Codigo,
            'Nome' => $this->Nome,
            'Descricao' => $this->Descricao,
            'Categoria' => $this->Categoria,
            'Preco' => $this->Preco,
            'Quantidade' => $this->Quantidade,
            'Ativo' => $this->Ativo,
        ], [$this->id]);
    }

    /** Remove este produto (identificado por $this->id) do banco. */
    public function excluir(){
        return (new Database('produtos'))->delete('id = ?', [$this->id]);
    }
}
