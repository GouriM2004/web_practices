<?php
require_once __DIR__ . '/../../src/bootstrap.php';
require_once __DIR__ . '/../Models/Resume.php';

class ResumeController
{
    public static function upload($user_id, $file)
    {
        $uploadsDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);
        $filename = basename($file['name']);
        $target = $uploadsDir . time() . '_' . $filename;
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $text = null; // placeholder: ParserService should populate later
            $id = Resume::create($user_id, $filename, $target, $text);
            return $id;
        }
        throw new Exception('Upload failed');
    }

    public static function get($id)
    {
        return Resume::find($id);
    }
}
