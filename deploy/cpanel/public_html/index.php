<?php

/**
 * cPanel main-domain front controller.
 *
 * voxsign.co.ug is locked to ~/public_html. That folder must be a real
 * directory (LiteSpeed often 404s when public_html is a symlink). This file
 * bootstraps the PearlEdu/VoxSign Laravel app that lives next door in
 * ~/pearledu-app, so marketing (voxsign.co.ug) and the school app
 * (pearledu.voxsign.co.ug) share one codebase while using different document roots.
 *
 * Deploy replaces ___APP_ROOT___ via scripts/cpanel-deploy.sh.
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$appRoot = getenv('VOXSIGN_APP_ROOT') ?: '___APP_ROOT___';

if ($appRoot === '' || $appRoot === '___APP_ROOT___' || ! is_dir($appRoot)) {
    $guess = dirname(__DIR__).'/pearledu-app';
    if (is_dir($guess)) {
        $appRoot = $guess;
    }
}

if (! is_dir($appRoot) || ! is_file($appRoot.'/bootstrap/app.php')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "VoxSign app root not found. Expected Laravel at {$appRoot}.\n";
    exit(1);
}

if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
