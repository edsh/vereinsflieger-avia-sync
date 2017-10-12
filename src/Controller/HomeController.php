<?php
declare(strict_types=1);

namespace LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\Controller;

use LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\EdshAviaFlightDataAdapter;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Application\Flight\Data\FlightsData;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Application\Flight\FlightApiService;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\ApiClient;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Infrastructure\AuthenticatedAccessTokenInterface;
use LuftsportvereinBacknangHeiningen\VereinsfliegerDeSdk\Port\Adapter\Service\AmeAviaFlightDataCsvAdapter;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Stopwatch\Stopwatch;
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
                [
                    'dateFrom' => $request->query->get('dateFrom'),
                    'dateUntil' => $request->query->get('dateUntil')
                ])
            );
    }

    public function downloadFlightsForAmeAction(Request $request): Response
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        $queryService =
            new FlightApiService($this->apiClient, $this->accessToken);

        $dayFrom =
            \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $request->request->get('dateFrom')
            );
        $dayUntil =
            \DateTimeImmutable::createFromFormat(
                'Y-m-d',
                $request->request->get('dateUntil')
            );

        $period =
            new \DatePeriod(
                $dayFrom,
                new \DateInterval('P1D'),
                $dayUntil
            );

        $flights = new FlightsData();

        foreach ($period as $day) {
            $flightsThatDay =
                $queryService
                    ->allFlightsDataOfDay($day);
            $flights = $flights->withFurther($flightsThatDay);
        }

        $stringlyDateRange =
            ($dayUntil > $dayFrom
                ? sprintf('%s--%s', $dayFrom->format('Y-m-d'), $dayUntil->format('Y-m-d'))
                : $dayFrom->format('Y-m-d')
            );

        if (count($flights) === 0) {
            $request->getSession()->getFlashBag()->add('notice', 'Es wurden keine Flüge zum Export gefunden.');
            return
                new RedirectResponse(
                    $this->router->generate('home_index', ['date' => $stringlyDateRange])
                );
        }

        $responseBody =
            array_map(
                function ($flightData) {
                    return
                        (string)
                        new EdshAviaFlightDataAdapter(
                            new AmeAviaFlightDataCsvAdapter($flightData)
                        );
                }, iterator_to_array($flights->getIterator())
                );

        $response =
            new Response(
                AmeAviaFlightDataCsvAdapter::headers() .
                AmeAviaFlightDataCsvAdapter::NEWLINE .
                implode(AmeAviaFlightDataCsvAdapter::NEWLINE, $responseBody),
                200,
                [
                    'Content-Type' => 'text/csv'
                ]
            );
        $response->headers->set(
            'Content-Disposition',
            $response
                ->headers
                ->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    sprintf('vf-ame-%s.csv', $stringlyDateRange)
                )
        );

        return
            $response;
    }
}
