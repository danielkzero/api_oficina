<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetPedidoById
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $id = (int)$args['id'];

        $stmt = $this->pdo->prepare("
            SELECT p.*, 
                   e.endereco, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep, 
                   i.id AS item_id, i.quantidade, i.preco_tabela, i.ipi, i.observacoes, i.st, i.produto_id, i.excluido AS item_excluido, i.subtotal, i.preco_liquido, 
                   d.desconto AS item_desconto 
            FROM pedido p
            LEFT JOIN pedido_endereco_entrega e ON p.id = e.pedido_id
            LEFT JOIN pedido_item i ON p.id = i.pedido_id
            LEFT JOIN pedido_item_desconto d ON i.id = d.pedido_item_id
            WHERE p.id = :id AND p.excluido = 0 ORDER BY id DESC
        ");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$pedidos) {
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Pedido não encontrado']);
        }

        $pedido = $pedidos[0];
        $resultado = [
            'id' => $pedido['id'],
            'cliente_id' => $pedido['cliente_id'],
            'cliente' => $pedido['cliente'],
            'representada' => $pedido['representada'],
            'tipo_pedido' => $pedido['tipo_pedido'],
            'tabela_preco' => $pedido['tabela_preco'],
            'vendedor' => $pedido['vendedor'],
            'contato_cliente' => $pedido['contato_cliente'],
            'condicao_pagamento' => $pedido['condicao_pagamento'],
            'observacoes' => $pedido['observacoes'],
            'status' => $pedido['status'],
            'status_faturamento' => $pedido['status_faturamento'],
            'criador_id' => $pedido['criador_id'],
            'total' => $pedido['total'],
            'data_emissao' => $pedido['data_emissao'],
            'ultima_alteracao' => $pedido['ultima_alteracao'],
            'cadastrado_em' => $pedido['cadastrado_em'],
            'excluido' => $pedido['excluido'],
            'enderecos' => [],
            'itens' => []
        ];

        foreach ($pedidos as $pedido) {
            if ($pedido['endereco']) {
                $resultado['enderecos'][] = [
                    'endereco' => $pedido['endereco'],
                    'numero' => $pedido['numero'],
                    'complemento' => $pedido['complemento'],
                    'bairro' => $pedido['bairro'],
                    'cidade' => $pedido['cidade'],
                    'estado' => $pedido['estado'],
                    'cep' => $pedido['cep'],
                ];
            }

            if ($pedido['item_id']) {
                $resultado['itens'][] = [
                    'id' => $pedido['item_id'],
                    'quantidade' => $pedido['quantidade'],
                    'preco_tabela' => $pedido['preco_tabela'],
                    'ipi' => $pedido['ipi'],
                    'observacoes' => $pedido['observacoes'],
                    'st' => $pedido['st'],
                    'produto_id' => $pedido['produto_id'],
                    'excluido' => $pedido['item_excluido'],
                    'subtotal' => $pedido['subtotal'],
                    'preco_liquido' => $pedido['preco_liquido'],
                    'descontos' => $pedido['item_desconto'] ? [$pedido['item_desconto']] : []
                ];
            }
        }

        return $response->withHeader('Content-Type', 'application/json')->withJson($resultado);
    }
}
