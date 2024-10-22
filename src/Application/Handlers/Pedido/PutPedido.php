<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PutPedido
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];
        $data = $request->getParsedBody();

        try {
            // Begin Transaction
            $this->pdo->beginTransaction();
            // Update Pedido
            $stmt = $this->pdo->prepare("
                UPDATE pedido 
                SET 
                    cliente = :cliente, cliente_id = :cliente_id, condicao_pagamento = :condicao_pagamento, contato_cliente = :contato_cliente, criador_id = :criador_id, 
                    enderecos = :enderecos, excluido = :excluido, status = :status, id_tabela_preco = :id_tabela_preco, observacoes = :observacoes, representada = :representada, 
                    status = :status, status_faturamento = :status_faturamento, tabela_preco = :tabela_preco, tipo_pedido = :tipo_pedido, total = :total, vendedor = :vendedor
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':cliente' => json_encode($data['cliente']),
                ':cliente_id' => $data['cliente_id'],
                ':condicao_pagamento' => json_encode($data['condicao_pagamento']),
                ':contato_cliente' => json_encode($data['contato_cliente']),
                ':criador_id' => $data['criador_id'],
                ':enderecos' => json_encode($data['enderecos']),
                ':excluido' => $data['excluido'],
                ':status' => $data['status'],
                ':id_tabela_preco' => $data['id_tabela_preco'],
                ':observacoes' => $data['observacoes'],
                ':representada' => json_encode($data['representada']),
                ':status' => $data['status'],
                ':status_faturamento' => $data['status_faturamento'],
                ':tabela_preco' => $data['tabela_preco'],
                ':tipo_pedido' => json_encode($data['tipo_pedido']),
                ':total' => $data['total'],
                ':vendedor' => json_encode($data['vendedor'])
            ]);

            // Insert new Itens do Pedido
            if (isset($data['itens'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pedido_item (pedido_id, qtd_unitaria, quantidade, codigo, nome, st, ipi, 
                    comissao, item_acrescimo, item_desconto, preco_liquido, preco_minimo, preco_tabela, subtotal, observacoes, excluido)
                    VALUES (:pedido_id, :qtd_unitaria, :quantidade, :codigo, :nome, :st, :ipi, 
                    :comissao, :item_acrescimo, :item_desconto, :preco_liquido, :preco_minimo, :preco_tabela, :subtotal, :observacoes, :excluido) 
                    ON DUPLICATE KEY UPDATE 
                    qtd_unitaria = VALUES(qtd_unitaria), 
                    quantidade = VALUES(quantidade), 
                    codigo = VALUES(codigo), 
                    nome = VALUES(nome), 
                    st = VALUES(st), 
                    ipi = VALUES(ipi), 
                    comissao = VALUES(comissao), 
                    item_acrescimo = VALUES(item_acrescimo), 
                    item_desconto = VALUES(item_desconto), 
                    preco_liquido = VALUES(preco_liquido), 
                    preco_minimo = VALUES(preco_minimo), 
                    preco_tabela = VALUES(preco_tabela), 
                    subtotal = VALUES(subtotal), 
                    observacoes = VALUES(observacoes), 
                    excluido = VALUES(excluido) 
                ");
                foreach ($data['itens'] as $item) {
                    $stmt->execute([
                        ':pedido_id' => $id,                  
                        ':qtd_unitaria' => $item['qtd_unitaria'],
                        ':quantidade' => $item['quantidade'],
                        ':tabela_preco_id' => $item['tabela_preco_id'],
                        ':produto_id' => $item['produto_id'],
                        ':codigo' => $item['codigo'],
                        ':nome' => $item['nome'],
                        ':st' => $item['icms_destino'],
                        ':ipi' => $item['ipi'],
                        ':comissao' => $item['comissao'],
                        ':item_acrescimo' => json_encode($item['item_acrescimo'] ?? []),
                        ':item_desconto' => json_encode($item['item_desconto'] ?? []),
                        ':preco_liquido' => $item['preco_liquido'],
                        ':preco_minimo' => $item['preco_minimo'],
                        ':preco_tabela' => $item['preco_tabela'],                        
                        ':subtotal' => $item['subtotal'],
                        ':observacoes' => $item['observacoes'],
                        ':excluido' => $item['excluido']
                    ]);
                }
            }

            // Commit Transaction
            $this->pdo->commit();
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Pedido atualizado com sucesso']);

        } catch (\Exception $e) {
            // Rollback Transaction
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao atualizar pedido', 'error' => $e->getMessage()]);
        }
    }
}
