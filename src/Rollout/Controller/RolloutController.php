<?php

declare(strict_types=1);

namespace EnterpriseToolingForSymfony\SharedBundle\Rollout\Controller;

use EnterpriseToolingForSymfony\SharedBundle\Rollout\Service\RolloutService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

class RolloutController extends AbstractController
{
    private const SECRET_HEADER = 'X-ETFS-ROLLOUT-SIGNAL-SECRET';
    private const SECRET_ENV    = 'ROLLOUT_SIGNAL_SECRET';

    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route(
        path   : '/.well-known/rollout-signal',
        name   : 'shared.rollout.get_signal',
        methods: [Request::METHOD_GET]
    )]
    public function getAction(
        Request        $request,
        RolloutService $rolloutService
    ): Response {
        if ($response = $this->validateSecret($request)) {
            return $response;
        }

        return new Response(
            $rolloutService->rolloutIsInProgress() ? 'Rollout in progress.' : 'No rollout in progress.',
            $rolloutService->rolloutIsInProgress() ? Response::HTTP_FOUND : Response::HTTP_NOT_FOUND
        );
    }

    #[Route(
        path   : '/.well-known/rollout-signal',
        name   : 'shared.rollout.set_signal',
        methods: [Request::METHOD_PUT]
    )]
    public function setAction(
        Request        $request,
        RolloutService $rolloutService
    ): Response {
        if ($response = $this->validateSecret($request)) {
            return $response;
        }

        try {
            $rolloutService->setRolloutSignal();

            return new Response(
                'Rollout signal has been set.',
                Response::HTTP_CREATED
            );
        } catch (Throwable $t) {
            return new Response(
                "Error setting rollout signal: {$t->getMessage()}",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route(
        path   : '/.well-known/rollout-signal',
        name   : 'shared.rollout.remove_signal',
        methods: [Request::METHOD_DELETE]
    )]
    public function removeAction(
        Request        $request,
        RolloutService $rolloutService
    ): Response {
        if ($response = $this->validateSecret($request)) {
            return $response;
        }

        try {
            $rolloutService->removeRolloutSignal();

            return new Response(
                'Rollout signal has been unset.',
                Response::HTTP_OK
            );
        } catch (Throwable $t) {
            return new Response(
                "Error unsetting rollout signal: {$t->getMessage()}",
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function validateSecret(Request $request): ?Response
    {
        if (!array_key_exists(self::SECRET_ENV, $_ENV)) {
            $this->logger->warning(self::SECRET_ENV . ' env var not set.');

            return null;
        }

        $secretHeader   = $request->headers->get(self::SECRET_HEADER);
        $expectedSecret = $_ENV[self::SECRET_ENV];

        if ($secretHeader !== $expectedSecret) {
            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        return null;
    }
}
