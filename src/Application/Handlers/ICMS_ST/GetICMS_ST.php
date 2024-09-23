<?php
namespace App\Application\Handlers\ICMS_ST;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetICMS_ST
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Obter parâmetros da requisição
            $queryParams = $request->getQueryParams();
            $busca = isset($queryParams['busca']) ? $queryParams['busca'] : '';
            $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;

            // Construir a consulta SQL para ICMS_ST
            $sql = 'SELECT * FROM icms_st WHERE excluido = 0';
            if (!empty($busca)) {
                $sql .= ' AND (codigo_ncm LIKE :busca OR estado_destino LIKE :busca)'; // Substitua campo1 e campo2 pelos campos apropriados
            }
            $sql .= ' LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($sql);

            // Vincular os parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Obter o total de registros para a paginação
            $countSql = 'SELECT COUNT(*) AS total FROM icms_st WHERE excluido = 0';
            if (!empty($busca)) {
                $countSql .= ' AND (codigo_ncm LIKE :busca OR estado_destino LIKE :busca)'; // Mesmo ajuste aqui
            }
            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            if ($data) {
                return $response->withHeader('Content-Type', 'application/json')->withJson([
                    'data' => $data,
                    'total' => $total
                ]);
            } else {
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'ICMS_ST não encontrado']);
            }

        } catch (\Exception $e) {
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json')->withJson(['status' => 'Erro ao buscar ICMS_ST', 'error' => $e->getMessage()]);
        }
    }
}
