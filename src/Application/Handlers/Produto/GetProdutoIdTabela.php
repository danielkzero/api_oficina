<?php
namespace App\Application\Handlers\Produto;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetProdutoIdTabela
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $id = $args['id'];
            // Obter parâmetros da requisição
            $queryParams = $request->getQueryParams();
            $busca = isset($queryParams['busca']) ? $queryParams['busca'] : '';
            $limit = isset($queryParams['limit']) ? (int) $queryParams['limit'] : 10;
            $offset = isset($queryParams['offset']) ? (int) $queryParams['offset'] : 0;
            $UF = isset($queryParams['UF']) ? $queryParams['UF'] : '';

            // Construir a consulta SQL para os produtos
            $sql = 'SELECT 
                    
                    (SELECT imagem_base64 FROM produto_imagem WHERE produto_id = p.id LIMIT 1) as imagem_base64,

                    p.*, tp.nome as tabela_nome, tp.acrescimo, tp.desconto, tpp.preco, 
                    ist.codigo_ncm, ist.nome_excecao_fiscal, ist.estado_destino, ist.tipo_st,
                    ist.valor_mva, ist.valor_pmc, ist.icms_credito, ist.icms_destino 
                    FROM produto p
                    LEFT JOIN tabela_preco_produto tpp ON p.id = tpp.produto_id
                    LEFT JOIN tabela_preco tp ON tpp.tabela_id = tp.id
                    LEFT JOIN icms_st ist ON ist.codigo_ncm=p.codigo_ncm 
                    WHERE p.excluido = 0 AND tp.id=:id AND ist.estado_destino=:UF';

            if (!empty($busca)) {
                $sql .= ' AND (p.nome LIKE :busca OR p.codigo LIKE :busca)';
            }
            $sql .= ' ORDER BY p.id DESC LIMIT :limit OFFSET :offset';

            $stmt = $this->pdo->prepare($sql);

            // Vincular os parâmetros
            if (!empty($busca)) {
                $stmt->bindValue(':busca', '%' . $busca . '%', PDO::PARAM_STR);
            }            
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':UF', $UF, PDO::PARAM_STR);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Para cada produto, buscar suas imagens
            /*foreach ($produtos as &$produto) {
                $produtoId = $produto['id'];
                
                // Buscar as imagens do produto na tabela produto_imagem
                $imagemStmt = $this->pdo->prepare('SELECT imagem_base64, ordem FROM produto_imagem WHERE produto_id = :produto_id ORDER BY ordem ASC');
                $imagemStmt->bindValue(':produto_id', $produtoId, PDO::PARAM_INT);
                $imagemStmt->execute();
                $imagens = $imagemStmt->fetchAll(PDO::FETCH_ASSOC);

                // Adicionar as imagens ao array do produto
                $produto['imagens'] = $imagens;
            }*/

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

            // Retornar os produtos com suas respectivas imagens e tabelas de preços
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
