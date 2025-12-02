<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../Models/User.php';

class AuthController
{
    public static function register($name, $email, $password)
    {
        $exists = User::findByEmail($email);
        if ($exists) {
            throw new Exception('Email already exists');
        }
        $hash = password_hash($password, PASSWORD_BCRYPT);
        return User::create($name, $email, $hash);
    }

    public static function login($email, $password)
    {
        $user = User::findByEmail($email);
        if (!$user) return false;
        if (password_verify($password, $user['password'])) {
            // very simple session handling
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'] ?? $user['email'];
            return $user;
        }
        return false;
    }
}
