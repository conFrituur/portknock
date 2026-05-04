<?php

declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Portknock\Controller\Knock as KnockController;
use Portknock\Controller\TableView as TableViewController;
use Portknock\Helper\Log;
use Portknock\Helper\OutputHandler;

require '../vendor/autoload.php';

// get path without query string
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$headers = $_SERVER;

$dateFormat = "Y-m-d H:i:s";
$output = "[%datetime%] %level_name%: %message% %context%" . PHP_EOL;
$formatter = new LineFormatter($output, $dateFormat);

$logger = new Logger("PortKnockLog");
$steamHandler = new StreamHandler(__DIR__.'/../data/history.log', Level::Debug);
$steamHandler->setFormatter($formatter);

$logger->pushHandler($steamHandler);
Log::setLogger($logger);

switch ($uri) {
    case '/':
        new KnockController($headers)->knock();
        break;
    case '/view':
        new TableViewController($headers)->showList();
        break;
    default:
        new OutputHandler()->die(404);
}


