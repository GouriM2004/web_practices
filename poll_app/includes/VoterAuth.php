<?php
require_once __DIR__ . '/Models/Voter.php';

class VoterAuth
{
    public static function loginOrRegister($name, $password)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $voterModel = new Voter();
        $voter = $voterModel->findByName($name);

        if ($voter) {
            if (!password_verify($password, $voter['password'])) {
                return false;
            }
            session_regenerate_id(true);
            $_SESSION['voter_id'] = $voter['id'];
            $_SESSION['voter_name'] = $voter['name'];
            return true;
        }

        // create new voter if not found
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $voterId = $voterModel->create($name, $hash);
        if ($voterId) {
            session_regenerate_id(true);
            $_SESSION['voter_id'] = $voterId;
            $_SESSION['voter_name'] = $name;
            return true;
        }

        return false;
    }

    public static function check()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return isset($_SESSION['voter_id']);
    }

    public static function id()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['voter_id'] ?? null;
    }

    public static function name()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['voter_name'] ?? null;
    }

    public static function logout()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['voter_id'], $_SESSION['voter_name']);
    }
}
