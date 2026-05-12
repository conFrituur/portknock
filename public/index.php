<?php

declare(strict_types=1);

use Monolog\ErrorHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Portknock\Controller\Knock as KnockController;
use Portknock\Controller\TableView as TableViewController;
use Portknock\Helper\Log;
use Portknock\Helper\OutputHandler;
use Portknock\Model\HttpHeaders;

require '../vendor/autoload.php';

$headers = new HttpHeaders($_SERVER);

$dateFormat = "Y-m-d H:i:s";
$output = "[%datetime%] %level_name%: %message% %context%" . PHP_EOL;
$formatter = new LineFormatter($output, $dateFormat);

$logger = new Logger("PortknockLog");
$steamHandler = new StreamHandler(__DIR__ . '/../data/history.log', Log::getLogLevelFromEnvironment());
$steamHandler->setFormatter($formatter);

$logger->pushHandler($steamHandler);
Log::setLogger($logger);
ErrorHandler::register($logger);

switch ($headers->getRoutingUri()) {
    case '':
        new KnockController($headers)->knock();
        break;
    case 'view':
        new TableViewController($headers)->showList();
        break;
    default:
        new OutputHandler()->die(404);
}
