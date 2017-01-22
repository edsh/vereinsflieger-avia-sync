<?php
declare(strict_types = 1);

require __DIR__.'/vendor/autoload.php';

use LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\ConsoleCommand\FlightsOfDayCommand;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\ApiClient;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\RemoteAccessToken;
use Symfony\Component\Console\Application;

$application = new Application();

$apiClient = new ApiClient();
$accessToken = new RemoteAccessToken($apiClient);

$application->add(new FlightsOfDayCommand(null, $apiClient, $accessToken));

$application->run();
