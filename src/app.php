<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Tprl\DeclarationLocator;
use Temporal\Client\GRPC\ServiceClient;
use Symfony\Component\Console\Application;
use Utils\Command;

require __DIR__ . '/vendor/autoload.php';

// finds all available workflows, activity types and commands in a given directory
$declarations = DeclarationLocator::create(__DIR__ . '/Temporal/');

$host = getenv('TEMPORAL_ADDRESS') ?: getenv('TEMPORAL_CLI_ADDRESS');
if (empty($host)) {
    $host = 'temporal:7233';
}

$app = new Application('Temporal PHP Smart Delivery');

foreach ($declarations->getCommands() as $command) {
    $app->add(Command::create($command, ServiceClient::create($host)));
}

$app->run();
