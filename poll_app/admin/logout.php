<?php
session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::logout();
header("Location: login.php");
exit;
