<?php
// src/routes/auth.php

use Slim\App;
use Slim\Http\ServerRequest as Request;
use Slim\Http\Response as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Application\Settings\SettingsInterface;

return function (App $app) {
    $container = $app->getContainer();
    $app->group('/auth', function () use ($container) {

        // Criar conta (POST)
        $this->post('/register', function (Request $request, Response $response) use ($container) {
            $pdo = $container->get(PDO::class);
            $data = $request->getParsedBody();

            $email = $data['email'] ?? null;
            $senha = $data['senha'] ?? null;

            if (!$email || !$senha) {
                return $response->withStatus(400)->withJson(['error' => 'Email e senha são obrigatórios.']);
            }

            // Verifica se o usuário já existe
            $stmt = $pdo->prepare("SELECT id FROM mod_usuarios WHERE email = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                return $response->withStatus(409)->withJson(['error' => 'Usuário já existe.']);
            }

            // Criação de IV e criptografia de senha
            $iv = bin2hex(random_bytes(16));
            $hashedPassword = password_hash($senha, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO mod_usuarios (email, senha, iv) VALUES (:email, :senha, :iv)");
            $stmt->execute([
                'email' => $email,
                'senha' => $hashedPassword,
                'iv' => $iv
            ]);

            return $response->withJson(['success' => true]);
        });

        // Login (POST)
        $this->post('/login', function (Request $request, Response $response) use ($container) {
            $pdo = $container->get(PDO::class);
            $settings = $container->get(SettingsInterface::class);
            $secretKey = $settings->get('secret_key');
            $data = $request->getParsedBody();

            $email = $data['email'] ?? null;
            $senha = $data['senha'] ?? null;

            $stmt = $pdo->prepare("SELECT id, senha FROM mod_usuarios WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($senha, $user['senha'])) {
                return $response->withStatus(401)->withJson(['error' => 'Credenciais inválidas.']);
            }

            $token = JWT::encode([
                'iat' => time(),
                'exp' => time() + (60 * 60 * 8),
                'sub' => $user['id']
            ], $secretKey, 'HS256');

            return $response->withJson(['token' => $token]);
        });

        // Obter usuário logado (GET)
        $this->get('/me', function (Request $request, Response $response) use ($container) {
            $user_id = ValidarToken($request);
            $pdo = $container->get(PDO::class);
            $stmt = $pdo->prepare("SELECT id, email FROM mod_usuarios WHERE id = :id");
            $stmt->execute(['id' => $user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $response->withJson($user);
        });

        // Atualizar senha (PUT)
        $this->put('/update', function (Request $request, Response $response) use ($container) {
            $pdo = $container->get(PDO::class);
            $user_id = ValidarToken($request);
            $data = $request->getParsedBody();

            $novaSenha = $data['senha'] ?? null;
            if (!$novaSenha) {
                return $response->withStatus(400)->withJson(['error' => 'Nova senha é obrigatória.']);
            }

            $hashedPassword = password_hash($novaSenha, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE mod_usuarios SET senha = :senha WHERE id = :id");
            $stmt->execute(['senha' => $hashedPassword, 'id' => $user_id]);

            return $response->withJson(['success' => true]);
        });

        // Deletar conta (DELETE)
        $this->delete('/delete', function (Request $request, Response $response) use ($container) {
            $pdo = $container->get(PDO::class);
            $user_id = ValidarToken($request);

            $stmt = $pdo->prepare("DELETE FROM mod_usuarios WHERE id = :id");
            $stmt->execute(['id' => $user_id]);

            return $response->withJson(['success' => true]);
        });
    });
};