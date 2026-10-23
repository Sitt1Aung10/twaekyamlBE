<?php
declare(strict_types=1);

function allow_cors(): void
{
    header('Access-Control-Allow-Origin: https://twaekyaml.hermesmyanmar.com/');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}