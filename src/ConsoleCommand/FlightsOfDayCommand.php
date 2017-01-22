<?php
declare(strict_types = 1);

namespace LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\ConsoleCommand;

use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\AccessTokenInterface;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\ApiClient;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\AuthenticatedAccessTokenInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FlightsOfDayCommand extends Command
{
    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var AccessTokenInterface
     */
    private $accessToken;

    public function __construct($name = null, ApiClient $apiClient, AuthenticatedAccessTokenInterface $accessToken)
    {
        parent::__construct($name);
        $this->apiClient = $apiClient;
        $this->accessToken = $accessToken;
    }

    protected function configure()
    {
        $this
            ->setName('flight:flights-of-day')
            ->setDescription('Lists the flight data sets of a particular day')
            ->addArgument('date', InputArgument::REQUIRED, 'The date in ISO-8601 format, i.e. YYYY-mm-dd');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln('Test test, token: ' . $this->accessToken);
    }
}
