<?php
namespace App\Application\Handlers\Cliente;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class GetCliente
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            // Pegar parâmetros de paginação
            $queryParams = $request->getQueryParams();
            $limit = isset($queryParams['limit']) ? (int)$queryParams['limit'] : 100; // Default: 100
            $offset = isset($queryParams['offset']) ? (int)$queryParams['offset'] : 0; // Default: 0

            // Certificar que o limit não é maior que 100
            if ($limit > 100) {
                $limit = 100;
            }

            // Buscar os dados principais dos clientes com limit e offset
            $stmt = $this->pdo->prepare("
                SELECT id, tipo, razao_social, nome_fantasia, cnpj, inscricao_estadual, rua, numero, complemento, bairro, cidade, estado, cep, suframa, observacao, ultima_alteracao, excluido, bloqueado 
                FROM cliente
                LIMIT :limit OFFSET :offset
            ");
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$clientes) {
                return $response->withStatus(404)->withJson(['error' => 'Nenhum cliente encontrado']);
            }

            // Iterar sobre os clientes para buscar telefones, emails, endereços e contatos associados
            foreach ($clientes as &$cliente) {
                // Buscar telefones do cliente
                $stmt = $this->pdo->prepare("
                    SELECT id, numero, tipo
                    FROM cliente_telefone
                    WHERE cliente_id = :cliente_id
                ");
                $stmt->execute([':cliente_id' => $cliente['id']]);
                $cliente['telefones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar emails do cliente
                $stmt = $this->pdo->prepare("
                    SELECT id, email, tipo
                    FROM cliente_email
                    WHERE cliente_id = :cliente_id
                ");
                $stmt->execute([':cliente_id' => $cliente['id']]);
                $cliente['emails'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar endereços do cliente
                $stmt = $this->pdo->prepare("
                    SELECT id, endereco, numero, complemento, bairro, cidade, estado, cep, ultima_alteracao
                    FROM cliente_endereco
                    WHERE cliente_id = :cliente_id
                ");
                $stmt->execute([':cliente_id' => $cliente['id']]);
                $cliente['enderecos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Buscar contatos do cliente
                $stmt = $this->pdo->prepare("
                    SELECT id, nome, cargo, excluido
                    FROM cliente_contato
                    WHERE cliente_id = :cliente_id
                ");
                $stmt->execute([':cliente_id' => $cliente['id']]);
                $cliente['contatos'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Para cada contato, buscar os telefones e emails associados
                foreach ($cliente['contatos'] as &$contato) {
                    // Buscar telefones do contato
                    $stmt = $this->pdo->prepare("
                        SELECT id, numero, tipo
                        FROM cliente_contato_telefone
                        WHERE contato_id = :contato_id
                    ");
                    $stmt->execute([':contato_id' => $contato['id']]);
                    $contato['telefones'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Buscar emails do contato
                    $stmt = $this->pdo->prepare("
                        SELECT id, email, tipo
                        FROM cliente_contato_email
                        WHERE contato_id = :contato_id
                    ");
                    $stmt->execute([':contato_id' => $contato['id']]);
                    $contato['emails'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                // Buscar dados extras do cliente
                $stmt = $this->pdo->prepare("
                    SELECT id, campo_extra_id, nome, valor_texto, valor_data, nome_arquivo, valor_arquivo, valor_decimal
                    FROM cliente_extra
                    WHERE cliente_id = :cliente_id
                ");
                $stmt->execute([':cliente_id' => $cliente['id']]);
                $cliente['extras'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }

            // Retornar os dados dos clientes em formato JSON
            return $response->withHeader('Content-Type', 'application/json')->withJson($clientes, 200);

        } catch (Exception $e) {
            return $response->withStatus(500)->withJson(['error' => $e->getMessage()]);
        }
    }
}
