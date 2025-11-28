<?php

class ParserService
{
    // Simple wrapper to call local Python parser microservice
    public static function parseText($text)
    {
        $url = 'http://127.0.0.1:5000/parse';
        $data = ['text' => $text];
        $options = [
            'http' => [
                'header'  => "Content-type: application/json\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ];
        $context  = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        if ($result === FALSE) {
            return null;
        }
        return json_decode($result, true);
    }
}
