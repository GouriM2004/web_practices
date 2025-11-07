<?php

namespace Controllers;

class AuthController
{
    protected $pdo;
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // Example: register logic (used by api.php currently)
    public function register(array $data)
    {
        // validate and insert
    }

    // Example: login logic (used by api.php currently)
    public function login(array $data)
    {
        // verify and start session
    }
}
