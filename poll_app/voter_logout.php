<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
VoterAuth::logout();
header('Location: index.php');
exit;
