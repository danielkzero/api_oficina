<?php
namespace App\Application\Handlers\ConfiguracaoSistema;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PutConfiguracaoSistema 
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();
        $id = $args['id']; // ID da configuração a ser atualizada

        try {
            $stmt = $this->pdo->prepare("UPDATE configuracao_sistema SET periodo_sync = :periodo_sync, email = :email, senha = :senha, servidor_smtp = :servidor_smtp, porta = :porta, usa_tls = :usa_tls WHERE id = :id");
            $stmt->bindParam(':periodo_sync', $data['periodo_sync']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':senha', $data['senha']);
            $stmt->bindParam(':servidor_smtp', $data['servidor_smtp']);
            $stmt->bindParam(':porta', $data['porta']);
            $stmt->bindParam(':usa_tls', $data['usa_tls']);
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            return $response->withJson(['message' => 'Configuração atualizada com sucesso.']);
        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
