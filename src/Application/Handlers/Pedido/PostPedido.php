<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class PostPedido
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        try {
            // Begin Transaction
            $this->pdo->beginTransaction();

            // Insert Pedido
            $stmt = $this->pdo->prepare("
                INSERT INTO pedidos (cliente_id, status, condicao_pagamento, forma_pagamento_id, tipo_pedido_id, nome_contato, status_faturamento, observacoes, numero, data_criacao, ultima_alteracao, condicao_pagamento_id, data_emissao, total, criador_id)
                VALUES (:cliente_id, :status, :condicao_pagamento, :forma_pagamento_id, :tipo_pedido_id, :nome_contato, :status_faturamento, :observacoes, :numero, NOW(), NOW(), :condicao_pagamento_id, :data_emissao, :total, :criador_id)
            ");
            $stmt->execute([
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
            $pedidoId = $this->pdo->lastInsertId();

            // Insert Endereços de Entrega
            if (isset($data['enderecos'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pedido_endereco_entrega (pedido_id, endereco, numero, complemento, bairro, cidade, estado, cep)
                    VALUES (:pedido_id, :endereco, :numero, :complemento, :bairro, :cidade, :estado, :cep)
                ");
                foreach ($data['enderecos'] as $endereco) {
                    $stmt->execute([
                        ':pedido_id' => $pedidoId,
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

            // Insert Itens do Pedido
            if (isset($data['itens'])) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO pedido_item (pedido_id, tipo_ipi, quantidade, preco_tabela, tabela_preco_id, ipi, observacoes, st, produto_id, excluido, subtotal, preco_liquido)
                    VALUES (:pedido_id, :tipo_ipi, :quantidade, :preco_tabela, :tabela_preco_id, :ipi, :observacoes, :st, :produto_id, :excluido, :subtotal, :preco_liquido)
                ");
                foreach ($data['itens'] as $item) {
                    $stmt->execute([
                        ':pedido_id' => $pedidoId,
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

                    // Insert Descontos dos Itens
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
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Pedido criado com sucesso', 'id' => $pedidoId]);

        } catch (\Exception $e) {
            // Rollback Transaction
            $this->pdo->rollBack();
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao criar pedido', 'error' => $e->getMessage()]);
        }
    }
}
