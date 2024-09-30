<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetPedido
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        try {
            // Obter parâmetros da requisição
            $queryParams = $request->getQueryParams();
            $busca = isset($queryParams['busca']) ? $queryParams['busca'] : '';
            $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;

            // Construir a consulta SQL com base nos parâmetros
            $sql = "
                SELECT p.*, sts.descricao as sts_descricao, hex_rgb, auto_checked, us.nome as criador_nome, us.avatar as criador_avatar, c.razao_social, c.nome_fantasia, 
                       e.endereco, e.numero, e.complemento, e.bairro, e.cidade, e.estado, e.cep, 
                       i.id AS item_id, i.quantidade, i.preco_tabela, i.ipi, i.observacoes, i.st, i.produto_id, i.excluido AS item_excluido, i.subtotal, i.preco_liquido, 
                       d.desconto AS item_desconto 
                FROM pedido p
                LEFT JOIN cliente c ON c.id = p.cliente_id 
                LEFT JOIN pedido_endereco_entrega e ON p.id = e.pedido_id
                LEFT JOIN pedido_item i ON p.id = i.pedido_id
                LEFT JOIN pedido_item_desconto d ON i.id = d.pedido_item_id
                LEFT JOIN usuario us ON us.id = p.criador_id 
                LEFT JOIN pedido_status sts ON sts.status = p.status
                WHERE p.excluido = 0
            ";

            if (!empty($busca)) {
                $sql .= ' AND (c.razao_social LIKE :busca OR c.nome_fantasia LIKE :busca)';
            }
            $sql .= ' ORDER BY id DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($sql);

            // Vincular os parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar os dados para combinar várias linhas em um formato estruturado
            $pedidosOrganizados = [];
            foreach ($pedidos as $pedido) {
                if (!isset($pedidosOrganizados[$pedido['id']])) {
                    $pedidosOrganizados[$pedido['id']] = [
                        'id' => $pedido['id'],
                        'cliente_id' => $pedido['cliente_id'],
                        'razao_social' => $pedido['razao_social'],
                        'nome_fantasia' => $pedido['nome_fantasia'],
                        'status' => $pedido['status'],
                        'sts_descricao' => $pedido['sts_descricao'],
                        'hex_rgb' => $pedido['hex_rgb'],
                        'condicao_pagamento' => $pedido['condicao_pagamento'],
                        'forma_pagamento_id' => $pedido['forma_pagamento_id'],
                        'tipo_pedido_id' => $pedido['tipo_pedido_id'],
                        'nome_contato' => $pedido['nome_contato'],
                        'status_faturamento' => $pedido['status_faturamento'],
                        'observacoes' => $pedido['observacoes'],
                        'numero' => $pedido['numero'],
                        'cadastrado_em' => $pedido['cadastrado_em'],
                        'ultima_alteracao' => $pedido['ultima_alteracao'],
                        'condicao_pagamento_id' => $pedido['condicao_pagamento_id'],
                        'data_emissao' => $pedido['data_emissao'],
                        'total' => $pedido['total'],
                        'criador_id' => $pedido['criador_id'],
                        'criador_nome' => $pedido['criador_nome'],
                        'criador_avatar' => $pedido['criador_avatar'],
                        'enderecos' => [],
                        'itens' => []
                    ];
                }

                if ($pedido['endereco']) {
                    $pedidosOrganizados[$pedido['id']]['enderecos'][] = [
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
                    $pedidosOrganizados[$pedido['id']]['itens'][] = [
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

            // Obter o total de registros para a paginação
            $countSql = "
                SELECT COUNT(DISTINCT p.id) AS total
                FROM pedido p
                LEFT JOIN cliente c ON c.id = p.cliente_id 
                WHERE p.excluido = 0
            ";

            if (!empty($busca)) {
                $countSql .= ' AND (c.razao_social LIKE :busca OR c.nome_fantasia LIKE :busca)';
            }

            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => array_values($pedidosOrganizados),
                    'total' => $total
                ]);
        } catch (\Exception $e) {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'error' => [
                        'type' => 'SERVER_ERROR',
                        'description' => $e->getMessage()
                    ]
                ]);
        }
    }
}
