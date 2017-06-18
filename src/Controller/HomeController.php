<?php
declare(strict_types=1);

namespace LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\Controller;

use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Application\Flight\FlightApiService;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\ApiClient;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\AuthenticatedAccessTokenInterface;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Port\Adapter\Service\AmeAviaFlightDataCsvAdapter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Templating\EngineInterface;

final class HomeController
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var ApiClient
     */
    private $apiClient;

    /**
     * @var AuthenticatedAccessTokenInterface
     */
    private $accessToken;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EngineInterface $templatingEngine,
        RouterInterface $router,
        ApiClient $apiClient,
        AuthenticatedAccessTokenInterface $accessToken
    )
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->templatingEngine = $templatingEngine;
        $this->router = $router;
        $this->apiClient = $apiClient;
        $this->accessToken = $accessToken;
    }

    public function indexAction(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        return
            new Response(
                $this->templatingEngine->render('home/index.html.twig',
                ['date' => $request->query->get('date')])
            );
    }

    public function downloadFlightsForAmeAction(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $queryService =
            new FlightApiService($this->apiClient, $this->accessToken);
        $flightsThatDay =
            $queryService
                ->allFlightsDataOfDay(
                    \DateTimeImmutable::createFromFormat(
                        'Y-m-d',
                        $request->request->get('date')
                    )
                );

        if (count($flightsThatDay) === 0) {
            $request->getSession()->getFlashBag()->add('notice', 'Es wurden keine Flüge zum Export gefunden.');
            return
                new RedirectResponse(
                    $this->router->generate('home_index', ['date' => $request->request->get('date')])
                );
        }

        $responseData =
            array_map(function ($flightData) {
                return (string) new AmeAviaFlightDataCsvAdapter($flightData);
            },
            iterator_to_array($flightsThatDay->getIterator())
            );

        return
            new Response(
                implode("\n", $responseData),
                200,
                ['Content-Type' => 'text/csv']
            );
    }
}