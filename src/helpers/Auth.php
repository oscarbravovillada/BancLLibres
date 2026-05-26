<?php
// src/helpers/Auth.php

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/Database.php';

class Auth {

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function login(array $user): void {
        self::start();
        session_regenerate_id(true);

        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_nom']   = $user['nom'] . ' ' . $user['cognoms'];
        $_SESSION['user_rol']   = $user['rol'];
        $_SESSION['user_email'] = $user['email'];
    }

    public static function logout(): void {
        self::start();
        session_unset();
        session_destroy();
    }

    public static function check(): bool {
        self::start();
        return isset($_SESSION['user_id']);
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: ' . BASE_URL . '/login.php');
            exit;
        }
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > 7200) {
            self::logout();
            header('Location: ' . BASE_URL . '/login.php?timeout=1');
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if ($_SESSION['user_rol'] !== 'admin') {
            header('Location: ' . BASE_URL . '/index.php?error=no_permisos');
            exit;
        }
    }

    public static function id(): ?int {
        return $_SESSION['user_id'] ?? null;
    }

    public static function rol(): ?string {
        return $_SESSION['user_rol'] ?? null;
    }

    public static function nom(): string {
        return $_SESSION['user_nom'] ?? '';
    }

    public static function csrfToken(): string {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::csrfToken(), ENT_QUOTES) . '">';
    }

    public static function csrfCheck(): void {
        self::start();
        $token = trim($_POST['csrf_token'] ?? '');
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            die('Petició no vàlida. Torna enrere i reintenta l\'acció.');
        }
    }

    public static function canAccessAlumne(int $alumne_id): bool {
        if (self::rol() === 'admin') return true;

        $row = Database::fetchOne(
            "SELECT 1
             FROM alumnes a
             JOIN professor_classe pc ON pc.classe_id = a.classe_id
             WHERE a.id = ? AND pc.professor_id = ?",
            [$alumne_id, self::id()]
        );
        return (bool)$row;
    }

    public static function requireAccessToAlumne(int $alumne_id): void {
        if (!self::canAccessAlumne($alumne_id)) {
            header('Location: ' . BASE_URL . '/index.php?error=no_permisos');
            exit;
        }
    }
}
