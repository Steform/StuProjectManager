<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start the session at the beginning of the script to track user sessions across pages
session_start();

// Compute the root path (one level up from public/)
$root = dirname(__DIR__);

$controller = $_GET['controller'] ?? 'project';
$action = $_GET['action'] ?? 'list';

$controllerClass = ucfirst($controller) . 'Controller';
$controllerFile = $root . '/app/controllers/' . $controllerClass . '.php';

if (file_exists($controllerFile)) {
    require_once $controllerFile;
    $ctrl = new $controllerClass();
    if (method_exists($ctrl, $action)) {
        $ctrl->$action();
    } else {
        http_response_code(404);
        echo 'Action not found';
    }
} else {
    http_response_code(404);
    echo 'Controller not found';
} 