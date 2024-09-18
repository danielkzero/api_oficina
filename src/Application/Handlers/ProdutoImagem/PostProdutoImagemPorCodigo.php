<?php
namespace App\Application\Handlers\ProdutoImagem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;
use Exception;

class PostProdutoImagemPorCodigo 
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

        // Valida se o array de imagens foi enviado
        if (!is_array($data)) {
            return $response->withStatus(400)->withJson(['error' => 'O formato dos dados enviados está incorreto']);
        }

        // Array para armazenar o status de cada item (sucesso ou erro)
        $resultados = [];

        try {
            // Iniciar transação para inserir as imagens
            $this->pdo->beginTransaction();

            // Prepare a query para inserir imagens
            $stmtInsertImagem = $this->pdo->prepare("
                INSERT INTO produto_imagem (produto_id, imagem_base64, ordem)
                VALUES (:produto_id, :imagem_base64, :ordem)
            ");

            // Prepare a query para buscar o produto pelo código
            $stmtBuscaProduto = $this->pdo->prepare("
                SELECT id FROM produto WHERE codigo = :codigo
            ");

            // Loop pelos itens do array para cada imagem
            foreach ($data as $index => $item) {
                // Verifica se os campos obrigatórios estão presentes
                if (!isset($item['codigo'], $item['imagem_base64'])) {
                    $resultados[] = [
                        'codigo' => $item['codigo'] ?? null,
                        'status' => 'error',
                        'message' => "Campos 'codigo' ou 'imagem_base64' ausentes no item de índice $index"
                    ];
                    continue;
                }

                // Buscar o produto pelo código
                $stmtBuscaProduto->execute([':codigo' => $item['codigo']]);
                $produto = $stmtBuscaProduto->fetch();

                // Se o produto não for encontrado, marca como erro
                if (!$produto) {
                    $resultados[] = [
                        'codigo' => $item['codigo'],
                        'status' => 'error',
                        'message' => "Produto com código {$item['codigo']} não encontrado"
                    ];
                    continue;
                }

                // Tenta inserir a imagem na tabela 'produto_imagem'
                try {
                    $stmtInsertImagem->execute([
                        ':produto_id' => $produto['id'],
                        ':imagem_base64' => $item['imagem_base64'],
                        ':ordem' => $index + 1  // Ordem baseada no índice do array
                    ]);

                    // Sucesso no envio da imagem
                    $resultados[] = [
                        'codigo' => $item['codigo'],
                        'status' => 'success',
                        'message' => 'Imagem adicionada com sucesso'
                    ];
                } catch (Exception $e) {
                    // Falha ao inserir a imagem
                    $resultados[] = [
                        'codigo' => $item['codigo'],
                        'status' => 'error',
                        'message' => 'Erro ao adicionar a imagem',
                        'details' => $e->getMessage()
                    ];
                }
            }

            // Comitar a transação
            $this->pdo->commit();

            // Retorna o array de resultados, tanto sucesso quanto erros
            return $response->withHeader('Content-Type', 'application/json')
                            ->withJson($resultados);

        } catch (Exception $e) {
            // Reverter a transação em caso de erro
            $this->pdo->rollBack();

            // Marca todos os itens como erro, caso ocorra falha na transação geral
            return $response->withStatus(500)->withJson(['error' => 'Erro ao adicionar as imagens', 'details' => $e->getMessage()]);
        }
    }
}
