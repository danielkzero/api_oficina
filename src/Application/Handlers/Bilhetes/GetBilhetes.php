<?php
//routes.php
namespace App\Application\Handlers\Bilhetes;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetBilhetes
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

            a.nome as cliente,
            a.telefone,
            a.email,
            c.nome as campanha, 
            c.slug,
            LENGTH(c.qtd_numeros) as qtd_numeros,
            CONCAT("[",GROUP_CONCAT(b.numeros_pedido SEPARATOR ","),"]") AS numeros_pedido

            FROM clientes AS a 
            INNER JOIN pedidos AS b ON b.id_cliente=a.id 
            INNER JOIN campanhas AS c ON c.id=b.id_campanha AND c.encerrada = 0 
            WHERE a.telefone = :telefone
            GROUP BY b.id_campanha');
            $stmt->bindParam(':telefone', $id);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $response->withHeader('Content-Type', 'application/json')->withJson($result);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}