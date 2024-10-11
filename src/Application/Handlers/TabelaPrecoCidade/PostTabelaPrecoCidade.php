<?php
namespace App\Application\Handlers\TabelaPrecoCidade;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostTabelaPrecoCidade
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Obtém os dados da requisição
            $data = $request->getParsedBody();
            $tabela = $data['tabela'];
            $regioes = $data['regioes']; // array de regiões

            // Verifica se as regiões estão presentes
            if (empty($regioes) || empty($tabela)) {
                return $response->withHeader('Content-Type', 'application/json')
                                ->withStatus(400)
                                ->getBody()->write(json_encode(['status' => 'error', 'message' => 'Tabela e Regiões são obrigatórias.']));
            }

            // Prepara a query SQL para inserção com ON DUPLICATE KEY
            $stmt = $this->pdo->prepare("
                INSERT INTO tabela_preco_cidade (id_tabela_preco, id_ibge_cidade) 
                VALUES (:id_tabela_preco, :id_ibge_cidade)
                ON DUPLICATE KEY UPDATE 
                    id_tabela_preco = VALUES(id_tabela_preco), 
                    id_ibge_cidade = VALUES(id_ibge_cidade)
            ");

            // Itera sobre cada região e executa a inserção
            foreach ($regioes as $regiao) {
                $stmt->bindParam(':id_tabela_preco', $tabela);
                $stmt->bindParam(':id_ibge_cidade', $regiao['id']); // Assumindo que cada região tem um campo 'id'
                $stmt->execute();
            }

            // Retorna sucesso
            $response->getBody()->write(json_encode(['status' => 'success']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);

        } catch (Exception $e) {
            // Retorna erro em caso de exceção
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
