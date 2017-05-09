<?php
declare(strict_types = 1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Console\Application;
use LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\ConsoleCommand\FlightsOfDayCommand;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\ApiClient;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\DefaultCredentials;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\RemoteAccessToken;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\RemoteAuthenticatedAccessToken;

require __DIR__.'/vendor/autoload.php';

if (!getenv('APP_ENV')) {
    (new Dotenv())->load(__DIR__.'/.env');
}

$application = new Application();

$apiClient = new ApiClient();
$accessToken =
    new RemoteAuthenticatedAccessToken(
        $apiClient,
        new DefaultCredentials(getenv('VF_USERNAME'), getenv('VF_PASSWORD')),
        new RemoteAccessToken($apiClient)
    );

$application->add(new FlightsOfDayCommand(null, $apiClient, $accessToken));

$application->run();
