<?php
// includes/Config.php
class Config
{
    const DB_HOST = 'localhost';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_NAME = 'poll_app';
    const VOTE_CHANGE_WINDOW_MINUTES = 5;

    // Weights applied to each voter type when calculating weighted results
    const VOTER_TYPE_WEIGHTS = [
        'expert' => 2.0,
        'student' => 1.5,
        'public' => 1.0,
    ];
}
