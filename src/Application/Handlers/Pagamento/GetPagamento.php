<?php
//routes.php
namespace App\Application\Handlers\Pagamento;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetPagamento
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

            $stmt = $this->pdo->prepare('SELECT 
                a.*, 
                b.*, 
                c.qtd_numeros
                FROM pagamentos AS a
                INNER JOIN pedidos AS b ON b.id=a.id_pedido 
                INNER JOIN campanhas AS c ON c.id=b.id_campanha AND c.encerrada = 0 
                WHERE a.url_ref=:url_ref');
            $stmt->bindParam(':url_ref', $id);

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}