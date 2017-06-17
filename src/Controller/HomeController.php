<?php
declare(strict_types=1);

namespace LuftsportvereinBacknangHeiningen\VereinsfliegerAviaSync\Controller;

use Symfony\Component\HttpFoundation\Response;
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

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EngineInterface $templatingEngine)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->templatingEngine = $templatingEngine;
    }

    public function indexAction(): Response
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            throw new AccessDeniedException();
        }

        return
            new Response(
                $this->templatingEngine->render('home/index.html.twig')
            );
    }
}
