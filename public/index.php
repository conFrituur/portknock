<?php

declare(strict_types=1);

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

$logger = new Logger("PortKnockLog");
$logger->pushHandler(new StreamHandler(__DIR__.'/../data/history.log', Level::Debug));
Log::setLogger($logger);

switch ($uri) {
    case '/':
        new KnockController()->knock($headers);
        break;
    case '/view':
        new TableViewController()->showList($headers);
        break;
    default:
        new OutputHandler()->die(404);
}


