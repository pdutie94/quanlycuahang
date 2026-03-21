<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use Firebase\JWT\JWT;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends BaseController
{
    public function __construct(private readonly array $config)
    {
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = (array) ($request->getParsedBody() ?? []);
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->error($response, 'Username and password are required', 400, [
                'username' => $username === '' ? 'Required' : null,
                'password' => $password === '' ? 'Required' : null,
            ]);
        }

        $user = $this->findByUsername($username);
        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return $this->error($response, 'Sai tài khoản hoặc mật khẩu', 401);
        }

        $token = $this->generateToken([
            'id' => (int) $user['id'],
            'username' => (string) $user['username'],
            'name' => (string) $user['name'],
        ]);

        return $this->success($response, [
            'token' => $token,
            'user' => [
                'id' => (int) $user['id'],
                'username' => (string) $user['username'],
                'full_name' => (string) $user['name'],
            ],
        ], 'Login successful');
    }

    public function me(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        if ($auth === null) {
            return $this->error($response, 'Unauthorized', 401);
        }

        return $this->success($response, [
            'id' => (int) ($auth->sub ?? 0),
            'username' => (string) ($auth->username ?? ''),
            'full_name' => (string) ($auth->name ?? ''),
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->success($response, [], 'Logout successful');
    }

    private function findByUsername(string $username): array|false
    {
        $pdo = $this->getPdo();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, name FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    private function generateToken(array $user): string
    {
        $now = time();
        $ttl = (int) ($this->config['jwt']['ttl'] ?? 3600);
        $payload = [
            'iss' => (string) ($this->config['jwt']['issuer'] ?? 'quanlycuahang-local'),
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => (int) $user['id'],
            'username' => (string) $user['username'],
            'name' => (string) $user['name'],
        ];

        return JWT::encode($payload, (string) $this->config['jwt']['secret'], 'HS256');
    }

    private function getPdo(): PDO
    {
        return Database::getInstance($this->config['db']);
    }
}
