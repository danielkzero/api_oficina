<?php
// src/Auth/functions.php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;



function generate_jwt_token(int $user_id, string $secret_key): string {
    $issued_at = time();
    $expiration_time = $issued_at + (60 * 60 * 8); // 8 horas

    $payload = [
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'sub' => $user_id
    ];

    return JWT::encode($payload, $secret_key, 'HS256');
}
function validate_jwt_token(string $jwt_token, string $secret_key) {
    try {
        return JWT::decode($jwt_token, new Key($secret_key, 'HS256'));
    } catch (ExpiredException|SignatureInvalidException|BeforeValidException|Exception $e) {
        throw new Exception('Token inválido: ' . $e->getMessage(), 401);
    }
}


function ValidarToken($request) {
    global $container;

    if (!$request->hasHeader('Authorization')) {
        throw new Exception('Acesso não autorizado!', 401);
    }

    $token = $request->getHeader('Authorization')[0];
    $jwt_token = str_replace('Bearer ', '', $token);
    $settings = $container->get(\App\Application\Settings\SettingsInterface::class);
    $secret_key = $settings->get('secret_key');

    return validate_jwt_token($jwt_token, $secret_key)->sub;
}

function hashPassword(string $senha): string {
    return password_hash($senha, PASSWORD_BCRYPT);
}

function verifyPassword(string $senha, string $hash): bool {
    return password_verify($senha, $hash);
}

function encrypt_content(string $plaintext, string $key, string $iv): string {
    return openssl_encrypt($plaintext, 'AES-256-CBC', $key, 0, base64_decode($iv));
}

function decrypt_content(string $ciphertext, string $key, string $iv): string {
    return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, base64_decode($iv));
}

function generate_iv(): string {
    return base64_encode(openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-256-CBC')));
}

function generate_user_key(string $secret, string $iv): string {
    return hash('sha256', $secret . $iv, true); // chave derivada
}  

// Exemplo de registro
function registrarUsuario(PDO $pdo, string $nome, string $email, string $senha): bool {
    $iv = generate_iv();
    $hash = hashPassword($senha);

    $stmt = $pdo->prepare("INSERT INTO mod_usuarios (nome, email, senha, iv, role, status) VALUES (:nome, :email, :senha, :iv, 'user', 'active')");
    return $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':senha' => $hash,
        ':iv' => $iv
    ]);
}

// Exemplo de login
function authenticateUser(PDO $pdo, string $usuario, string $senha, string $secret_key): ?string {
    $stmt = $pdo->prepare("SELECT * FROM mod_usuarios WHERE email = :usuario");
    $stmt->bindParam(':usuario', $usuario);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && verifyPassword($senha, $user['senha'])) {
        return generate_jwt_token($user['id'], $secret_key);
    }

    return null;
}

// Exemplo de uso para páginas criptografadas
function salvarPaginaCriptografada(PDO $pdo, int $user_id, string $titulo, string $slug, string $conteudo, string $chave_usuario, string $iv): bool {
    $conteudo_criptografado = encrypt_content($conteudo, $chave_usuario, $iv);

    $stmt = $pdo->prepare("INSERT INTO mod_pages (user_id, title, slug, content, iv, status) VALUES (:user_id, :title, :slug, :content, :iv, 'draft')");
    return $stmt->execute([
        ':user_id' => $user_id,
        ':title' => $titulo,
        ':slug' => $slug,
        ':content' => $conteudo_criptografado,
        ':iv' => $iv
    ]);
}

function lerPaginaDescriptografada(array $pagina, string $chave_usuario): string {
    return decrypt_content($pagina['content'], $chave_usuario, $pagina['iv']);
}
