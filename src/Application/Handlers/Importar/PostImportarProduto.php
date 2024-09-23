<?php

namespace App\Application\Handlers\Importar;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PostImportarProduto
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Capturar o valor do campo "option" enviado no FormData
            $parsedBody = $request->getParsedBody();
            $option = isset($parsedBody['option']) ? $parsedBody['option'] : null;

            // Verificar se o valor do option foi enviado
            if (!$option) {
                throw new Exception("Nenhum valor 'option' enviado.", 400);
            }

            // Agora você pode usar o valor de $option como quiser no seu código
            // Exemplo: logar o valor ou passar para outra lógica
            error_log("Valor de option: " . $option);

            // Verifique se um arquivo foi enviado
            $uploadedFiles = $request->getUploadedFiles();
            if (!isset($uploadedFiles['files'])) {  // Mudança de 'file' para 'files'
                throw new Exception("Nenhum arquivo enviado.", 400);
            }

            // Lidar com o envio de múltiplos arquivos
            $arquivos = $uploadedFiles['files']; // Obtém o array de arquivos enviados

            foreach ($arquivos as $uploadedFile) {
                if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
                    throw new Exception("Erro ao carregar o arquivo.", 400);
                }

                // Caminho temporário do arquivo enviado
                $filePath = $uploadedFile->getStream()->getMetadata('uri');

                // Carregar a planilha usando PhpSpreadsheet
                $spreadsheet = IOFactory::load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();

                if ($option === 'substituir') {
                    $stmt = $this->pdo->prepare("DELETE FROM produto_bkp");
                    $stmt->execute();
                }

                // Iterar pelas linhas da planilha
                foreach ($worksheet->getRowIterator(2) as $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);

                    // Extraindo os dados da linha
                    $rowData = [];
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getCalculatedValue();
                    }

                    // Mapear as colunas da planilha aos campos do banco de dados
                    $codigo = $rowData[0];
                    $nome = $rowData[1];
                    $codigo_ncm = $rowData[5];
                    $preco_tabela = $rowData[2];

                    // Inserir os dados no banco de dados
                    $stmt = $this->pdo->prepare("
                        INSERT INTO produto_bkp (codigo, nome, codigo_ncm, preco_tabela)
                        VALUES (:codigo, :nome, :codigo_ncm, :preco_tabela)
                        ON DUPLICATE KEY UPDATE nome = :nome, codigo_ncm = :codigo_ncm, preco_tabela = :preco_tabela
                    ");
                    $stmt->bindParam(':codigo', $codigo);
                    $stmt->bindParam(':nome', $nome);
                    $stmt->bindParam(':codigo_ncm', $codigo_ncm);
                    $stmt->bindParam(':preco_tabela', $preco_tabela);

                    // Executar a inserção no banco de dados
                    $stmt->execute();
                }
            }

            // Retornar sucesso
            return $response->withHeader('Content-Type', 'application/json')->withJson(['status' => 'success'], 201);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode() ?: 500)
                ->withHeader('Content-Type', 'application/json')
                ->withJson(['error' => $e->getMessage()]);
        }
    }
}
