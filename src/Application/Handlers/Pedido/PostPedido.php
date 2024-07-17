<?php
namespace App\Application\Handlers\Pedido;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;
use PDO;
use MercadoPago\SDK;
use MercadoPago\Payment;
use MercadoPago\Payer;

class PostPedido
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
            $nome = $body['nome'];
            $email = $body['email'];
            $telefone = $body['telefone'];
            $quantidade = $body['quantidade'];
            $idCampanha = $body['id_campanha'];
            $preco = $body['preco'];
            $tempo_pagamento = $body['tempo_pagamento'];

            // Verificar se o cliente já existe pelo telefone
            $stmt = $this->pdo->prepare('SELECT id FROM clientes WHERE telefone = :telefone');
            $stmt->bindParam(':telefone', $telefone);
            $stmt->execute();
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($cliente) {
                // Cliente existe, retornar o ID
                $idCliente = $cliente['id'];
            } else {
                // Cliente não existe, criar um novo cliente
                $stmt = $this->pdo->prepare('INSERT INTO clientes (nome, telefone, email, cadastrado_em, atualizado_em) 
                                             VALUES (:nome, :telefone, :email, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)');
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':telefone', $telefone);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                $idCliente = $this->pdo->lastInsertId();
            }

            // Adicionar o pedido
            $stmt = $this->pdo->prepare('INSERT INTO 
                pedidos (id_cliente, id_campanha, quantidade, pedido_expira_em, preco) 
                VALUES (:id_cliente, :id_campanha, :quantidade, :pedido_expira_em, :preco)');
            $stmt->bindParam(':id_cliente', $idCliente);
            $stmt->bindParam(':id_campanha', $idCampanha);
            $stmt->bindParam(':quantidade', $quantidade);
            $stmt->bindParam(':pedido_expira_em', $tempo_pagamento);
            $stmt->bindParam(':preco', $preco);
            $stmt->execute();
            $idPedido = $this->pdo->lastInsertId();

            // Obter Access Token do Mercado Pago
            $stmt = $this->pdo->prepare('SELECT meta_value FROM informacoes_sistema WHERE meta_field = "mercadopago_access_token"');
            $stmt->execute();
            $accessToken = $stmt->fetchColumn();

            // Configurar Mercado Pago SDK
            SDK::setAccessToken($accessToken);

            // Criar um novo pagamento
            $payment = new Payment();
            $payment->transaction_amount = (float)$preco;
            $payment->description = 'Pagamento do Pedido #' . $idPedido;
            $payment->payment_method_id = "pix";
            
            // Configurar o pagador
            $payer = new Payer();
            $payer->email = $email;
            $payer->first_name = $nome;
            $payer->identification = ['type' => 'phone', 'number' => $telefone];
            $payment->payer = $payer;

            // Salvar o pagamento
            $payment->save();

            if ($payment->status == 'pending') {
                $referenciaPagamento = [
                    'mercado_pago_id' => $payment->id,
                    'qr_code' => $payment->point_of_interaction->transaction_data->qr_code,
                    'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64,
                    'copia_e_cola' => $payment->point_of_interaction->transaction_data->ticket_url
                ];

                // Gerar URL Ref única
                $urlRef = uniqid();

                $status = $payment->status;
                $array_ref = json_encode($referenciaPagamento);

                // Salvar os dados na tabela pagamentos
                $stmt = $this->pdo->prepare('INSERT INTO 
                    pagamentos (id_pedido, url_ref, referencia_pagamento, status) 
                    VALUES (:id_pedido, :url_ref, :referencia_pagamento, :status)');
                $stmt->bindParam(':id_pedido', $idPedido);
                $stmt->bindParam(':url_ref', $urlRef);
                $stmt->bindParam(':referencia_pagamento', $array_ref);
                $stmt->bindParam(':status', $status);
                $stmt->execute();

                return $response->withHeader('Content-Type', 'application/json')->withJson(['success' => true, 'url_ref' => $urlRef]);
            } else {
                throw new Exception('Erro ao gerar o pagamento: ' . $payment->status);
            }

        } catch (Exception $e) {
            return $response->withStatus($e->getCode())->withJson(['error' => $e->getMessage()]);
        }
    }
}
