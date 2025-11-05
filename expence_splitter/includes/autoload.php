<?php
// includes/autoload.php
spl_autoload_register(function ($class) {
    $map = [
        'User' => __DIR__ . '/Models/User.php',
        'Group' => __DIR__ . '/Models/Group.php',
        'Expense' => __DIR__ . '/Models/Expense.php',
        'Settlement' => __DIR__ . '/Models/Settlement.php',
        'ExpensePayment' => __DIR__ . '/Models/ExpensePayment.php',
        'Notification' => __DIR__ . '/Models/Notification.php',
        'Report' => __DIR__ . '/Models/Report.php',
        'Log' => __DIR__ . '/Models/Log.php',
        'Database' => __DIR__ . '/Database.php',
        'Config' => __DIR__ . '/Config.php',
        'Calculator' => __DIR__ . '/Helpers/Calculator.php',
    ];
    if (isset($map[$class]) && file_exists($map[$class])) {
        require_once $map[$class];
    }
});
