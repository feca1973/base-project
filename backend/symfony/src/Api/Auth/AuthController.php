<?php

declare(strict_types=1);

namespace App\Api\Auth;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth', name: 'api_auth_')]
final readonly class AuthController
{
    public function __construct(private AuthFlowService $authFlow)
    {
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        return $this->authFlow->register($request);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function requestLogin(Request $request): JsonResponse
    {
        return $this->authFlow->requestLogin($request);
    }

    #[Route('/registration/confirm', name: 'registration_confirm', methods: ['GET'])]
    public function confirmRegistration(Request $request): Response
    {
        return $this->authFlow->confirmRegistration((string) $request->query->get('token', ''));
    }

    #[Route('/login/confirm', name: 'login_confirm', methods: ['GET'])]
    public function confirmLogin(Request $request): Response
    {
        return $this->authFlow->confirmLogin((string) $request->query->get('token', ''));
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function currentUser(Request $request): JsonResponse
    {
        return $this->authFlow->currentUser($request);
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): Response
    {
        return $this->authFlow->logout($request);
    }
}
