<?php

namespace App\Entity;

use \App\Db\Database;
use \PDO;

/**
 * Representa uma venda (tabela `vendas`: id, ClienteId, Data,
 * ValorTotal — a soma dos itens, ver VendaItem —, FormaPagamento
 * [debito/pix/credito] e Status [concluida/extornada]). Uma venda
 * sempre tem pelo menos um item (app/Entity/VendaItem.php); os itens em
 * si (quais produtos, quantidade, valor de cada um) não fazem parte
 * desta classe.
 *
 * ClienteNome vem de um JOIN com `rbextensionst` (feito em
 * getVendas/getTotalVendas/getVenda, via Database::execute() — essa
 * tabela não faz parte desta entidade), só pra exibição.
 */
class Venda{
    public $id;
    public $ClienteId;
    public $Data;
    public $ValorTotal;
    public $FormaPagamento;
    public $Status;
    public $ClienteNome;

    /**
     * Busca vendas (com o nome do cliente via JOIN), da mais recente
     * pra mais antiga. $where deve usar placeholders `?` com os campos
     * das tabelas `v` (vendas) e `u` (rbextensionst) — ex: 'v.Data >= ?'.
     *
     * @return Venda[]
     */
    public static function getVendas($where = null, $params = [], $limit = null){
        $where = !empty($where) ? 'WHERE '.$where : '';
        $limit = !empty($limit) ? 'LIMIT '.$limit : '';

        $sql = "SELECT v.*, CONCAT(u.nome, ' ', u.sobrenome) AS ClienteNome
                FROM `vendas` v
                INNER JOIN `rbextensionst` u ON u.id = v.ClienteId
                $where
                ORDER BY v.Data DESC, v.id DESC
                $limit";

        return (new Database())->execute($sql, $params)->fetchAll(PDO::FETCH_CLASS, self::class);
    }

    /**
     * Conta quantas vendas casam com $where (mesma regra de
     * placeholders/params de getVendas). Usado na paginação.
     */
    public static function getTotalVendas($where = null, $params = []){
        $where = !empty($where) ? 'WHERE '.$where : '';

        $sql = "SELECT COUNT(*) as total
                FROM `vendas` v
                INNER JOIN `rbextensionst` u ON u.id = v.ClienteId
                $where";

        return (new Database())->execute($sql, $params)->fetchObject()->total;
    }

    /** Busca uma única venda pelo id (com o cliente via JOIN). Retorna null se não existir. */
    public static function getVenda($id){
        $sql = "SELECT v.*, CONCAT(u.nome, ' ', u.sobrenome) AS ClienteNome
                FROM `vendas` v
                INNER JOIN `rbextensionst` u ON u.id = v.ClienteId
                WHERE v.id = ?";

        return (new Database())->execute($sql, [$id])->fetchObject(self::class);
    }

    /**
     * Soma o valor das vendas de $where (mesma regra de
     * placeholders/params de getVendas, tipicamente um filtro de
     * período em v.Data) em quatro totais:
     *   - total:        soma de TODAS as vendas do período (bruto)
     *   - extornado:    soma só das vendas com Status = extornada
     *   - debitoPix:    soma das vendas concluídas pagas em débito ou PIX
     *   - credito:      soma das vendas concluídas pagas em cartão de crédito
     *
     * debitoPix/credito não contam as extornadas — o valor estornado já
     * aparece separado em "extornado", pra não ser contado duas vezes.
     *
     * @return object{total: float, extornado: float, debitoPix: float, credito: float}
     */
    public static function getResumoFinanceiro($where = null, $params = []){
        $where = !empty($where) ? 'WHERE '.$where : '';

        $sql = "SELECT
                    COALESCE(SUM(ValorTotal), 0) AS total,
                    COALESCE(SUM(CASE WHEN Status = 'extornada' THEN ValorTotal ELSE 0 END), 0) AS extornado,
                    COALESCE(SUM(CASE WHEN Status = 'concluida' AND FormaPagamento IN ('debito','pix') THEN ValorTotal ELSE 0 END), 0) AS debitoPix,
                    COALESCE(SUM(CASE WHEN Status = 'concluida' AND FormaPagamento = 'credito' THEN ValorTotal ELSE 0 END), 0) AS credito
                FROM `vendas` v
                $where";

        return (new Database())->execute($sql, $params)->fetchObject();
    }

    /**
     * Insere esta venda no banco, usando os valores já atribuídos.
     * Preenche $this->id com o id gerado.
     */
    public function cadastrar(){
        $obDatabase = new Database('vendas');
        $this->id = $obDatabase->insert([
            'ClienteId' => $this->ClienteId,
            'Data' => $this->Data,
            'ValorTotal' => $this->ValorTotal,
            'FormaPagamento' => $this->FormaPagamento,
            'Status' => $this->Status ?? 'concluida',
        ]);
        return true;
    }

    /**
     * Marca esta venda (identificada por $this->id) como extornada.
     * Não mexe no estoque nem gera movimentação — o produto já saiu
     * fisicamente; o extorno aqui é só o registro financeiro (a venda
     * deixa de contar como receita concluída no resumo).
     */
    public function marcarComoExtornada(){
        return (new Database('vendas'))->update('id = ?', ['Status' => 'extornada'], [$this->id]);
    }
}
