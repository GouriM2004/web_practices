<?php
require_once __DIR__ . '/../Database.php';

class Voter
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function findByName($name)
    {
        $stmt = $this->db->prepare("SELECT * FROM voters WHERE name = ? LIMIT 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    public function create($name, $passwordHash)
    {
        $stmt = $this->db->prepare("INSERT INTO voters (name, password) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $passwordHash);
        $ok = $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();
        return $ok ? $id : null;
    }
}
