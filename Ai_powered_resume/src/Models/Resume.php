<?php

class Resume
{
    public $id;
    public $user_id;
    public $filename;
    public $file_path;
    public $text;
    public $parsed_json;

    public static function create($user_id, $filename, $file_path, $text = null)
    {
        $db = get_db();
        // validate user exists to provide clearer error messages before relying on FK
        $check = $db->prepare('SELECT id FROM users WHERE id = ?');
        $check->execute([$user_id]);
        $found = $check->fetch();
        if (!$found) {
            throw new Exception('Invalid user_id: no such user exists (id=' . intval($user_id) . '). Create or login a user first.');
        }
        $stmt = $db->prepare('INSERT INTO resumes (user_id, filename, file_path, text) VALUES (?,?,?,?)');
        $stmt->execute([$user_id, $filename, $file_path, $text]);
        return $db->lastInsertId();
    }

    public static function find($id)
    {
        $db = get_db();
        $stmt = $db->prepare('SELECT * FROM resumes WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
