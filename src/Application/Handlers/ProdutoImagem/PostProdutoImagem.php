<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Exception;

class PostProdutoImagem
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response)
    {
        // Pega os dados enviados na requisição
        $data = $request->getParsedBody();

        // Valida os campos obrigatórios
        if (!isset($data['produto_id'], $data['imagens']) || !is_array($data['imagens'])) {
            return $response->withStatus(400)->withJson(['error' => 'Campos obrigatórios ausentes ou inválidos']);
        }

        $produtoId = (int)$data['produto_id'];
        $imagens = $data['imagens'];

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO produto_imagem (produto_id, imagem_base64, ordem)
                VALUES (:produto_id, :imagem_base64, :ordem)
            ");

            foreach ($imagens as $imagem) {
                if (!isset($imagem['imagem_base64'], $imagem['ordem'])) {
                    return $response->withStatus(400)->withJson(['error' => 'Campos de imagem ausentes']);
                }

                $stmt->execute([
                    ':produto_id' => $produtoId,
                    ':imagem_base64' => $imagem['imagem_base64'],
                    ':ordem' => (int)$imagem['ordem']
                ]);
            }

            $this->pdo->commit();

            // Retorna a resposta em JSON
            return $response->withHeader('Content-Type', 'application/json')
                            ->withJson(['status' => 'Imagens adicionadas com sucesso']);

        } catch (Exception $e) {
            $this->pdo->rollBack();
            // Em caso de erro, retorna uma mensagem de erro
            return $response->withStatus(500)->withJson(['error' => 'Erro ao adicionar as imagens', 'details' => $e->getMessage()]);
        }
    }
}
