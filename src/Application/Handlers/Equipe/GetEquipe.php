<?php
namespace App\Application\Handlers\Equipe;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

class GetEquipe
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        // Consulta SQL para buscar equipes e seus usuários
        $stmt = $this->pdo->prepare("
            SELECT e.*, 
                   ue.id_usuario, ue.responsavel, u.nome AS usuario_nome
            FROM usuario_equipe e
            LEFT JOIN usuario_equipe_usuario ue ON e.id = ue.id_equipe
            LEFT JOIN usuario u ON ue.id_usuario = u.id
            WHERE e.excluido = 0 ORDER BY e.id DESC
        ");
        $stmt->execute();
        $equipesData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$equipesData) {
            return $response->withStatus(404)
                            ->withHeader('Content-Type', 'application/json')
                            ->withJson(['status' => 'Nenhuma equipe encontrada']);
        }

        // Mapeando equipes e seus usuários
        $equipes = [];
        foreach ($equipesData as $row) {
            // Se a equipe ainda não estiver no array $equipes, adiciona
            if (!isset($equipes[$row['id']])) {
                $equipes[$row['id']] = [
                    'id' => $row['id'],
                    'nome' => $row['nome'],
                    'cadastrado_em' => $row['cadastrado_em'],
                    'atualizado_em' => $row['atualizado_em'],
                    'usuarios' => []
                ];
            }

            // Se o usuário está associado à equipe, adiciona na lista de usuários
            if ($row['id_usuario']) {
                $equipes[$row['id']]['usuarios'][] = [
                    'id_usuario' => $row['id_usuario'],
                    'nome' => $row['usuario_nome'],
                    'responsavel' => $row['responsavel'] ? true : false
                ];
            }
        }

        // Transformar o array indexado por ID em um array simples
        $resultado = array_values($equipes);

        return $response->withHeader('Content-Type', 'application/json')
                        ->withJson($resultado);
    }
}
