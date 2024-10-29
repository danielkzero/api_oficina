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
            $status = isset($queryParams['status']) ? $queryParams['status'] : null;
            $criador_id = isset($queryParams['criador_id']) ? (int) $queryParams['criador_id'] : null;
            $dataInicial = isset($queryParams['data_inicial']) ? $queryParams['data_inicial'] : null;
            $dataFinal = isset($queryParams['data_final']) ? $queryParams['data_final'] : null;

            // Construir a consulta SQL com base nos parâmetros
            $sql = "SELECT 
                p.*, 
                st.descricao st_descricao, 
                sts.descricao sts_descricao, 
                st.hex_rgb st_hex_rgb, 
                sts.hex_rgb, 
                us.nome criador_nome, 
                us.avatar criador_avatar, 
                c.nome descricao_condicao_pagamento 
            FROM pedido p 
            LEFT JOIN usuario us ON us.id = p.criador_id
            LEFT JOIN pedido_status st ON st.status = p.status_faturamento 
            LEFT JOIN pedido_status_sinc sts ON sts.status = p.status 
            LEFT JOIN condicao_pagamento c ON c.id = p.condicao_pagamento 
            WHERE p.excluido = 0";

            // Condições opcionais
            if (!empty($busca)) {
                $sql .= ' AND (c.razao_social LIKE :busca OR c.nome_fantasia LIKE :busca)';
            }

            if ($status !== null) {
                $sql .= ' AND p.status =  :status ';
            }

            if ($criador_id !== null) {
                $sql .= ' AND p.criador_id = :criador_id';
            }

            if (!empty($dataInicial) && !empty($dataFinal)) {
                $sql .= ' AND p.data_emissao BETWEEN :dataInicial AND :dataFinal';
            }

            $sql .= ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($sql);
            
            //return $response->withHeader('Content-Type', 'application/json')->withJson( $sql);
            

            // Vincular os parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            if ($status !== null) {
                $stmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            if ($criador_id !== null) {
                $stmt->bindValue(':criador_id', $criador_id, PDO::PARAM_INT);
            }
            if (!empty($dataInicial) && !empty($dataFinal)) {
                $stmt->bindValue(':dataInicial', $dataInicial, PDO::PARAM_STR);
                $stmt->bindValue(':dataFinal', $dataFinal, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            // Executar a consulta
            $stmt->execute();
            $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Organizar os dados para combinar várias linhas em um formato estruturado
            $pedidosOrganizados = [];
            foreach ($pedidos as $pedido) {
                if (!isset($pedidosOrganizados[$pedido['id']])) {
                    $pedidosOrganizados[$pedido['id']] = [
                        'id' => $pedido['id'],
                        'cliente' => $pedido['cliente'] == null ? [] : json_decode($pedido['cliente']),
                        'cliente_id' => (int)$pedido['cliente_id'],
                        'condicao_pagamento' => $pedido['condicao_pagamento'] == null ? [] : $pedido['condicao_pagamento'],
                        'descricao_condicao_pagamento' => $pedido['descricao_condicao_pagamento'] == null ? [] : $pedido['descricao_condicao_pagamento'],
                        'contato_cliente' => $pedido['contato_cliente'],
                        'criador_id' => (int)$pedido['criador_id'],
                        'data_emissao' => $pedido['data_emissao'],
                        'enderecos' => $pedido['enderecos'] == null ? [] : json_decode($pedido['enderecos']),
                        'excluido' => (int)$pedido['excluido'],
                        'status' => (string)$pedido['status'],
                        'id_tabela_preco' => (int)$pedido['id_tabela_preco'],
                        'observacoes' => (string)$pedido['observacoes'],
                        'representada' => $pedido['representada'] == null ? [] : json_decode($pedido['representada']),
                        'status_faturamento' => (string)$pedido['status_faturamento'],
                        'tabela_preco' => (string)$pedido['tabela_preco'],
                        'tipo_pedido' => (string)$pedido['tipo_pedido'],
                        'total' => (float)$pedido['total'],
                        'vendedor' => $pedido['vendedor'] == null ? [] : json_decode($pedido['vendedor']),
                        'sts_descricao' => $pedido['sts_descricao'],
                        'hex_rgb' => $pedido['hex_rgb'],
                        'criador_id' => $pedido['criador_id'],
                        'criador_nome' => $pedido['criador_nome'],
                        'criador_avatar' => $pedido['criador_avatar'],
                    ];
                }
            }

            // Obter o total de registros para a paginação
            $countSql = "
                SELECT COUNT(DISTINCT p.id) AS total
                FROM pedido p
                LEFT JOIN cliente c ON c.id = p.cliente_id
                WHERE p.excluido = 0";

            // Condições opcionais
            if (!empty($busca)) {
                $countSql .= ' AND (c.razao_social LIKE :busca OR c.nome_fantasia LIKE :busca)';
            }

            if ($status !== null) {
                $countSql .= ' AND p.status = :status';
            }

            if ($criador_id !== null) {
                $countSql .= ' AND p.criador_id = :criador_id';
            }

            if (!empty($dataInicial) && !empty($dataFinal)) {
                $countSql .= ' AND p.data_emissao BETWEEN :dataInicial AND :dataFinal';
            }


            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            if ($status !== null) {
                $countStmt->bindValue(':status', $status, PDO::PARAM_STR);
            }
            if ($criador_id !== null) {
                $countStmt->bindValue(':criador_id', $criador_id, PDO::PARAM_INT);
            }
            if (!empty($dataInicial) && !empty($dataFinal)) {
                $countStmt->bindValue(':dataInicial', $dataInicial, PDO::PARAM_STR);
                $countStmt->bindValue(':dataFinal', $dataFinal, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => $pedidosOrganizados,
                    'total' => $total
                ]);
        } catch (\Exception $e) {
            return $response
                ->withStatus(500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'error' => [
                        'type' => 'SERVER_ERROR',
                        'message' => 'An error occurred while processing your request.',
                        'details' => $e->getMessage(),
                    ]
                ]);
        }
    }
}


