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
                UPDATE pedidos 
                SET cliente_id = :cliente_id, status = :status, condicao_pagamento = :condicao_pagamento, forma_pagamento_id = :forma_pagamento_id, tipo_pedido_id = :tipo_pedido_id, nome_contato = :nome_contato, status_faturamento = :status_faturamento, observacoes = :observacoes, numero = :numero, ultima_alteracao = NOW(), condicao_pagamento_id = :condicao_pagamento_id, data_emissao = :data_emissao, total = :total, criador_id = :criador_id
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $id,
                ':cliente_id' => $data['cliente_id'],
                ':status' => $data['status'],
                ':condicao_pagamento' => $data['condicao_pagamento'],
                ':forma_pagamento_id' => $data['forma_pagamento_id'],
                ':tipo_pedido_id' => $data['tipo_pedido_id'],
                ':nome_contato' => $data['nome_contato'],
                ':status_faturamento' => $data['status_faturamento'],
                ':observacoes' => $data['observacoes'],
                ':numero' => $data['numero'],
                ':condicao_pagamento_id' => $data['condicao_pagamento_id'],
                ':data_emissao' => $data['data_emissao'],
                ':total' => $data['total'],
                ':criador_id' => $data['criador_id']
            ]);

            // Delete old Endereços de Entrega
            $stmt = $this->pdo->prepare("DELETE FROM pedido_endereco_entrega WHERE pedido_id = :pedido_id");
            $stmt->execute([':pedido_id' => $id]);

            // Insert new Endereços de Entrega
            if (isset($data['enderecos'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pedido_endereco_entrega (pedido_id, endereco, numero, complemento, bairro, cidade, estado, cep)
                    VALUES (:pedido_id, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep)
                ");
                foreach ($data['enderecos'] as $endereco) {
                    $stmt->execute([
                        ':pedido_id' => $id,
                        ':endereco' => $endereco['endereco'],
                        ':numero' => $endereco['numero'],
                        ':complemento' => $endereco['complemento'],
                        ':bairro' => $endereco['bairro'],
                        ':cidade' => $endereco['cidade'],
                        ':estado' => $endereco['estado'],
                        ':cep' => $endereco['cep']
                    ]);
                }
            }

            // Delete old Itens do Pedido
            $stmt = $this->pdo->prepare("DELETE FROM pedido_item WHERE pedido_id = :pedido_id");
            $stmt->execute([':pedido_id' => $id]);

            // Insert new Itens do Pedido
            if (isset($data['itens'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pedido_item (pedido_id, tipo_ipi, quantidade, preco_tabela, tabela_preco_id, ipi, observacoes, st, produto_id, excluido, subtotal, preco_liquido)
                    VALUES (:pedido_id, :tipo_ipi, :quantidade, :preco_tabela, :tabela_preco_id, :ipi, :observacoes, :st, :produto_id, :excluido, :subtotal, :preco_liquido)
                ");
                foreach ($data['itens'] as $item) {
                    $stmt->execute([
                        ':pedido_id' => $id,
                        ':tipo_ipi' => $item['tipo_ipi'],
                        ':quantidade' => $item['quantidade'],
                        ':preco_tabela' => $item['preco_tabela'],
                        ':tabela_preco_id' => $item['tabela_preco_id'],
                        ':ipi' => $item['ipi'],
                        ':observacoes' => $item['observacoes'],
                        ':st' => $item['st'],
                        ':produto_id' => $item['produto_id'],
                        ':excluido' => $item['excluido'],
                        ':subtotal' => $item['subtotal'],
                        ':preco_liquido' => $item['preco_liquido']
                    ]);
                    $itemId = $this->pdo->lastInsertId();

                    // Delete old Descontos dos Itens
                    $stmt = $this->pdo->prepare("DELETE FROM pedido_item_desconto WHERE pedido_item_id = :pedido_item_id");
                    $stmt->execute([':pedido_item_id' => $itemId]);

                    // Insert new Descontos dos Itens
                    if (isset($item['descontos'])) {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO pedido_item_desconto (pedido_item_id, desconto)
                            VALUES (:pedido_item_id, :desconto)
                        ");
                        foreach ($item['descontos'] as $desconto) {
                            $stmt->execute([
                                ':pedido_item_id' => $itemId,
                                ':desconto' => $desconto
                            ]);
                        }
                    }
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
