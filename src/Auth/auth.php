<?php
//Auth/auth.php
use Firebase\JWT\JWT;

function generate_jwt_token($user_id, $secret_key) {
    $issued_at = time();
    //$expiration_time = $issued_at + (60 * 60 * 8); 
    $expiration_time = $issued_at + (60 * 60 * 8); 

    $payload = array(
        'iat' => $issued_at,
        'exp' => $expiration_time,
        'sub' => $user_id
    );

    return JWT::encode($payload, $secret_key, 'HS256');
}

function authenticateUser(PDO $pdo, string $email, string $senha): ?string
{
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND senha = :senha");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':senha', $senha);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return $user['id'];
    }

    return null;
}

function verifyCode(PDO $pdo, string $email, string $codigo, string $secret_key): ?array
{
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = :email AND codigo = :codigo");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':codigo', $codigo);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        return [
            'token' =>generate_jwt_token(['usuario' => $user['usuario']], $secret_key), 
            'usuario' => $user['usuario'] 
        ];
    }

    return null;
}
