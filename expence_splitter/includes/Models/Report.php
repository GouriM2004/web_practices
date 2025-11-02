<?php
// Model: Report
// Stores generated report metadata and allows lookup by token
class Report
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function createReport($group_id, $created_by, $type, $file_path, $expires_at = null)
    {
        $token = bin2hex(random_bytes(16));
        $stmt = $this->db->prepare("INSERT INTO reports (group_id, created_by, type, file_path, token, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iissss', $group_id, $created_by, $type, $file_path, $token, $expires_at);
        if ($stmt->execute()) {
            return ['id' => $this->db->insert_id, 'token' => $token];
        }
        return false;
    }

    public function getByToken($token)
    {
        $stmt = $this->db->prepare("SELECT * FROM reports WHERE token = ? LIMIT 1");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM reports WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_assoc();
    }
}
