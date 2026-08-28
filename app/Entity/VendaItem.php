<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa um item de venda (tabela `venda_itens`: id, VendaId,
 * ProdutoId, Quantidade, ValorUnitario — o preço do produto no momento
 * da venda, guardado à parte pra o extrato não mudar se o preço do
 * produto for alterado depois).
 *
 * ClienteNome/ProdutoCodigo/ProdutoNome/VendaData/VendaValorTotal vêm
 * de um JOIN com `vendas` + `produtos` + `rbextensionst` (feito em
 * getExtrato/getTotalExtrato, via Database::execute() — essas tabelas
 * não fazem parte desta entidade), só pra exibir o extrato de vendas
 * numa linha só por item.
 */
class VendaItem{
    public $id;
    public $VendaId;
    public $ProdutoId;
    public $Quantidade;
    public $ValorUnitario;
    public $ClienteNome;
    public $ProdutoCodigo;
    public $ProdutoNome;
    public $VendaData;
    public $VendaValorTotal;

    /**
     * Busca o extrato de vendas (um item de venda por linha, com
     * cliente/produto/data/valor total via JOIN), do mais recente pro
     * mais antigo. $where deve usar placeholders `?` com os campos das
     * tabelas `vi` (venda_itens), `v` (vendas), `p` (produtos) e `u`
     * (rbextensionst) — ex: 'p.Nome LIKE ?'.
     *
     * @return VendaItem[]
     */
    public static function getExtrato($where = null, $params = [], $limit = null){
        $where = !empty($where) ? 'WHERE '.$where : '';
        $limit = !empty($limit) ? 'LIMIT '.$limit : '';

        $sql = "SELECT vi.*,
                       CONCAT(u.nome, ' ', u.sobrenome) AS ClienteNome,
                       p.Codigo AS ProdutoCodigo, p.Nome AS ProdutoNome,
                       v.Data AS VendaData, v.ValorTotal AS VendaValorTotal
                FROM `venda_itens` vi
                INNER JOIN `vendas` v ON v.id = vi.VendaId
                INNER JOIN `produtos` p ON p.id = vi.ProdutoId
                INNER JOIN `rbextensionst` u ON u.id = v.ClienteId
                $where
                ORDER BY v.Data DESC, v.id DESC, vi.id ASC
                $limit";

        return (new Database())->execute($sql, $params)->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Conta quantos itens de venda casam com $where (mesma regra de
     * placeholders/params de getExtrato). Usado na paginação.
     */
    public static function getTotalExtrato($where = null, $params = []){
        $where = !empty($where) ? 'WHERE '.$where : '';

        $sql = "SELECT COUNT(*) as total
                FROM `venda_itens` vi
                INNER JOIN `vendas` v ON v.id = vi.VendaId
                INNER JOIN `produtos` p ON p.id = vi.ProdutoId
                INNER JOIN `rbextensionst` u ON u.id = v.ClienteId
                $where";

        return (new Database())->execute($sql, $params)->fetchObject()->total;
    }

    /**
     * Insere este item no banco, usando os valores já atribuídos.
     * Preenche $this->id com o id gerado. Não mexe na Quantidade do
     * produto nem no estoque — responsabilidade de quem chama (ver
     * dashboard/vendas-criar.php).
     */
    public function cadastrar(){
        $obDatabase = new Database('venda_itens');
        $this->id = $obDatabase->insert([
            'VendaId' => $this->VendaId,
            'ProdutoId' => $this->ProdutoId,
            'Quantidade' => $this->Quantidade,
            'ValorUnitario' => $this->ValorUnitario,
        ]);
        return true;
    }
}
