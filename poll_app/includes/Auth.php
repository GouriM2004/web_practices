<?php
// includes/Auth.php
require_once __DIR__ . '/Models/Admin.php';

class Auth
{
    public static function login($username, $password)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $adminModel = new Admin();
        $adminModel->ensureDefaultAdmin();
        $admin = $adminModel->findByUsername($username);
        // Accept hashed passwords (expected) and gracefully allow plain-text seeds if present.
        $isValid = false;
        if ($admin) {
            $isValid = password_verify($password, $admin['password']);
            if (!$isValid && hash_equals($admin['password'], $password)) {
                $isValid = true; // fallback for plain-text seeded passwords
            }
        }

        if ($admin && $isValid) {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            return true;
        }
        return false;
    }

    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['admin_id']);
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION = [];
        if (session_id()) session_destroy();
    }
}
