<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa a compra de produtos pra reposição de estoque (tabela
 * `compras_produtos`: id, Categoria, Fornecedor, Data, ValorTotal,
 * ParcelaAtual/ParcelaTotal — ex: 1/3 = primeira de três parcelas).
 *
 * Só registro financeiro (o quanto foi gasto, com quem e quando) — não
 * identifica produto/quantidade específicos, então não mexe na
 * Quantidade de nenhum produto nem gera movimentação de estoque (ao
 * contrário de uma venda, que desconta o produto vendido).
 */
class CompraProduto{
    public $id;
    public $Categoria;
    public $Fornecedor;
    public $Data;
    public $ValorTotal;
    public $ParcelaAtual;
    public $ParcelaTotal;

    /**
     * Busca compras, da mais recente pra mais antiga. $where deve usar
     * placeholders `?` (ex: 'Categoria = ?'), com os valores em
     * $params — mesma regra das demais entidades do projeto.
     *
     * @return CompraProduto[]
     */
    public static function getCompras($where = null, $params = [], $limit = null){
        return (new Database('compras_produtos'))->select($where, 'Data DESC, id DESC', $limit, '*', $params)
                                                   ->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Conta quantas compras casam com $where (mesma regra de
     * placeholders/params de getCompras). Usado na paginação.
     */
    public static function getTotalCompras($where = null, $params = []){
        return (new Database('compras_produtos'))->select($where, null, null, 'COUNT(*) as total', $params)
                                                   ->fetchObject()->total;
    }

    /** Busca uma única compra pelo id. Retorna null se não existir. */
    public static function getCompra($id){
        return (new Database('compras_produtos'))->select('id = ?', null, null, '*', [$id])
                                                   ->fetchObject(self::class);
    }

    /**
     * Soma o ValorTotal das compras de $where (mesma regra de
     * placeholders/params de getCompras, tipicamente um filtro de
     * período em Data). Usado como "Saída de Valores" no resumo
     * financeiro (dashboard/financeiro-resumo.php).
     */
    public static function getTotalGasto($where = null, $params = []){
        $where = !empty($where) ? 'WHERE '.$where : '';
        $sql = "SELECT COALESCE(SUM(ValorTotal), 0) AS total FROM `compras_produtos` $where";
        return (new Database())->execute($sql, $params)->fetchObject()->total;
    }

    /**
     * Insere esta compra no banco, usando os valores já atribuídos.
     * Preenche $this->id com o id gerado.
     */
    public function cadastrar(){
        $obDatabase = new Database('compras_produtos');
        $this->id = $obDatabase->insert([
            'Categoria' => $this->Categoria,
            'Fornecedor' => $this->Fornecedor,
            'Data' => $this->Data,
            'ValorTotal' => $this->ValorTotal,
            'ParcelaAtual' => $this->ParcelaAtual,
            'ParcelaTotal' => $this->ParcelaTotal,
        ]);
        return true;
    }

    /** Remove esta compra (identificada por $this->id) do banco. */
    public function excluir(){
        return (new Database('compras_produtos'))->delete('id = ?', [$this->id]);
    }
}
