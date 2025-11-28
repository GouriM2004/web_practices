<?php

class User
{
    public $id;
    public $name;
    public $email;
    public $password;


    public static function findByEmail($email)
    {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public static function create($name, $email, $passwordHash)
    {
        $db = get_db();
        $stmt = $db->prepare('INSERT INTO users (name,email,password) VALUES (?,?,?)');
        $stmt->execute([$name, $email, $passwordHash]);
        return $db->lastInsertId();
    }
}
