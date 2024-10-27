<?php
namespace App\Application\Handlers\TabelaPreco;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetTabelaPreco
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
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0;

            // Construir a consulta SQL para as tabelas de preços
            /*$sql = 'SELECT tp.*, tpp.preco 
                    FROM tabela_preco tp
                    LEFT JOIN tabela_preco_produto tpp ON tp.id = tpp.tabela_id
                    WHERE tp.excluido = 0';*/

            $sql = 'SELECT tp.* 
                FROM tabela_preco tp
                WHERE tp.excluido = 0';

            if (!empty($busca)) {
                $sql .= ' AND (tp.nome LIKE :busca OR tp.tipo LIKE :busca)';
            }
            $sql .= ' ORDER BY tp.id DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($sql);

            // Vincular os parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $tabelasPreco = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obter o total de registros para a paginação
            $countSql = 'SELECT COUNT(*) AS total FROM tabela_preco WHERE excluido = 0';
            if (!empty($busca)) {
                $countSql .= ' AND (nome LIKE :busca OR tipo LIKE :busca)';
            }
            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Retornar as tabelas de preço com total para paginação
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => $tabelasPreco,
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
