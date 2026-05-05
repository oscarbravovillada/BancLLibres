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
}
