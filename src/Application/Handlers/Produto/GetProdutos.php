<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutos
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

            // Construir a consulta SQL para os produtos
            $sql = 'SELECT * FROM produto WHERE excluido = 0';
            if (!empty($busca)) {
                $sql .= ' AND (nome LIKE :busca OR codigo LIKE :busca)';
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
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Para cada produto, buscar suas imagens
            foreach ($produtos as &$produto) {
                $produtoId = $produto['id'];
                
                // Buscar as imagens do produto na tabela produto_imagem
                $imagemStmt = $this->pdo->prepare('SELECT imagem_base64, ordem FROM produto_imagem WHERE produto_id = :produto_id ORDER BY ordem ASC');
                $imagemStmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
                $imagemStmt->execute();
                $imagens = $imagemStmt->fetchAll(PDO::FETCH_ASSOC);

                // Adicionar as imagens ao array do produto
                $produto['imagens'] = $imagens;
            }

            // Obter o total de registros para a paginação
            $countSql = 'SELECT COUNT(*) AS total FROM produto WHERE excluido = 0';
            if (!empty($busca)) {
                $countSql .= ' AND (nome LIKE :busca OR codigo LIKE :busca)';
            }
            $countStmt = $this->pdo->prepare($countSql);
            if (!empty($busca)) {
                $countStmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Retornar os produtos com suas respectivas imagens
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withJson([
                    'data' => $produtos,
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
