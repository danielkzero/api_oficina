<?php
//routes.php
namespace App\Application\Handlers\Campanha;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;

class PostCampanha
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $body = $request->getParsedBody();

            $codigo = uniqid();
            $slug = $this->convertToTag($body['nome']) . '_' . $codigo;
            
            $stmt = $this->pdo->prepare('INSERT INTO campanhas (nome, codigo, qtd_numeros, telefone_suporte, preco, slug) 
            VALUES (:nome, :codigo, :qtd_numeros, :telefone_suporte, :preco, :slug)');
            $stmt->bindParam(':nome', $body['nome']);
            $stmt->bindParam(':codigo', $codigo);
            $stmt->bindParam(':qtd_numeros', $body['qtd_numeros']);
            $stmt->bindParam(':telefone_suporte', $body['telefone_suporte']);
            $stmt->bindParam(':preco', $body['preco']);
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();

            $lastId = $this->pdo->lastInsertId();

            return $response->withHeader('Content-Type', 'application/json')->withJson(
                [
                    'success' => true, 
                    'id' => $lastId
                ]
            );

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }

    public function convertToTag($string) {
        // Converte todos os caracteres para minúsculas
        $string = strtolower($string);
    
        // Remove acentos e caracteres especiais
        $unwantedArray = array(
            'á'=>'a', 'à'=>'a', 'ã'=>'a', 'â'=>'a', 'ä'=>'a',
            'é'=>'e', 'è'=>'e', 'ê'=>'e', 'ë'=>'e',
            'í'=>'i', 'ì'=>'i', 'î'=>'i', 'ï'=>'i',
            'ó'=>'o', 'ò'=>'o', 'õ'=>'o', 'ô'=>'o', 'ö'=>'o',
            'ú'=>'u', 'ù'=>'u', 'û'=>'u', 'ü'=>'u',
            'ç'=>'c', 'ñ'=>'n'
        );
        $string = strtr($string, $unwantedArray);
    
        // Substitui espaços e caracteres não alfanuméricos por underscores (_)
        $string = preg_replace('/[^a-z0-9]/', '_', $string);
    
        // Remove underscores consecutivos
        $string = preg_replace('/_+/', '_', $string);
    
        // Remove underscores no início e no fim da string
        $string = trim($string, '_');
    
        return $string;
    }
}