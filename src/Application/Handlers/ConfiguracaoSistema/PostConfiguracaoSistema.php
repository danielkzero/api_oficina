<?php
namespace App\Application\Handlers\ConfiguracaoSistema;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostConfiguracaoSistema 
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO configuracao_sistema (periodo_sync, email, senha, servidor_smtp, porta, usa_tls) VALUES (:periodo_sync, :email, :senha, :servidor_smtp, :porta, :usa_tls)");
            $stmt->bindParam(':periodo_sync', $data['periodo_sync']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':senha', $data['senha']);
            $stmt->bindParam(':servidor_smtp', $data['servidor_smtp']);
            $stmt->bindParam(':porta', $data['porta']);
            $stmt->bindParam(':usa_tls', $data['usa_tls']);
            $stmt->execute();

            return $response->withStatus(201)->withJson(['message' => 'Configuração criada com sucesso.']);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
