<?php

declare(strict_types=1);

namespace App\Api\Auth;

use App\Entity\AuthSession;
use App\Entity\MagicLoginToken;
use App\Entity\User;
use App\Repository\AuthSessionRepository;
use App\Repository\MagicLoginTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;

final readonly class AuthFlowService
{
    private const REGISTRATION_TTL = 600;
    private const LOGIN_TTL = 900;
    private const SESSION_TTL = 604800;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $users,
        private MagicLoginTokenRepository $tokens,
        private AuthSessionRepository $sessions,
        private MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')]
        private string $fromEmail,
        #[Autowire('%env(APP_FRONTEND_URL)%')]
        private string $frontendUrl,
    ) {
    }

    public function register(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $nameLength = preg_match_all('/./us', $name);

        if (
            $name === ''
            || $nameLength === false
            || $nameLength > 180
            || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || strlen($email) > 180
        ) {
            return new JsonResponse(['message' => 'Adj meg érvényes nevet és email címet.'], 422);
        }
        if (($data['termsAccepted'] ?? false) !== true) {
            return new JsonResponse(['message' => 'A regisztrációhoz el kell fogadnod az ÁSZF-et.'], 422);
        }

        $existingUser = $this->users->findOneBy(['email' => $email]);
        if ($existingUser?->isActive()) {
            return new JsonResponse(['message' => 'Ezzel az email címmel már létezik fiók.'], 409);
        }

        $now = time();
        if ($existingUser === null) {
            $user = (new User())
                ->setEmail($email)
                ->setDisplayName($name)
                ->setRoles(['ROLE_USER'])
                ->setIsActive(false)
                ->setTermsAcceptedAt($now)
                ->setCreatedAt($now)
                ->setUpdatedAt($now);
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } else {
            $user = $existingUser
                ->setDisplayName($name)
                ->setRoles(['ROLE_USER'])
                ->setTermsAcceptedAt($now)
                ->setUpdatedAt($now);
        }

        $this->sendToken($user, 'register', self::REGISTRATION_TTL, 'registration');

        return new JsonResponse(['message' => 'Küldtünk egy jóváhagyó emailt a megadott címre.']);
    }

    public function requestLogin(Request $request): JsonResponse
    {
        $data = $request->toArray();
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) <= 180) {
            $user = $this->users->findActiveByEmail($email);
            if ($user !== null) {
                $this->sendToken($user, 'login', self::LOGIN_TTL, 'login');
            }
        }

        return new JsonResponse([
            'message' => 'Ha van aktív fiók ehhez a címhez, elküldtük a belépési linket.',
        ]);
    }

    public function confirmRegistration(string $plainToken): RedirectResponse
    {
        $token = $this->consumeToken($plainToken, 'register');
        if ($token === null || $token->getUser() === null) {
            return new RedirectResponse($this->frontendPath('/login?error=registration-link'));
        }

        $now = time();
        $token->getUser()
            ->setIsActive(true)
            ->setEmailVerifiedAt($now)
            ->setUpdatedAt($now);
        $this->entityManager->flush();

        return new RedirectResponse($this->frontendPath('/login?registered=1'));
    }

    public function confirmLogin(string $plainToken): Response
    {
        $token = $this->consumeToken($plainToken, 'login');
        if ($token === null || $token->getUser() === null) {
            return new RedirectResponse($this->frontendPath('/login?error=login-link'));
        }

        $user = $token->getUser();
        $now = time();
        $plainSession = bin2hex(random_bytes(32));
        $session = (new AuthSession())
            ->setUser($user)
            ->setTokenHash(hash('sha256', $plainSession))
            ->setCreatedAt($now)
            ->setExpiresAt($now + self::SESSION_TTL);
        $user->setLastLoginAt($now)->setUpdatedAt($now);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $response = new RedirectResponse($this->frontendPath('/'));
        $response->headers->setCookie(Cookie::create(
            'base_project_auth',
            $plainSession,
            $now + self::SESSION_TTL,
            '/',
            null,
            str_starts_with($this->frontendUrl, 'https://'),
            true,
            false,
            Cookie::SAMESITE_LAX,
        ));

        return $response;
    }

    public function currentUser(Request $request): JsonResponse
    {
        $plainSession = $request->cookies->get('base_project_auth');
        $session = is_string($plainSession) && $plainSession !== ''
            ? $this->sessions->findValidByTokenHash(hash('sha256', $plainSession), time())
            : null;
        $user = $session?->getUser();
        if ($user === null) {
            return new JsonResponse(['authenticated' => false], 401);
        }

        return new JsonResponse([
            'authenticated' => true,
            'name' => $user->getDisplayName(),
            'email' => $user->getEmail(),
        ]);
    }

    public function logout(Request $request): Response
    {
        $plainSession = $request->cookies->get('base_project_auth');
        if (is_string($plainSession) && $plainSession !== '') {
            $session = $this->sessions->findOneBy(['tokenHash' => hash('sha256', $plainSession)]);
            if ($session !== null) {
                $this->entityManager->remove($session);
                $this->entityManager->flush();
            }
        }

        $response = new JsonResponse(['message' => 'Sikeresen kiléptél.']);
        $response->headers->clearCookie(
            'base_project_auth',
            '/',
            null,
            str_starts_with($this->frontendUrl, 'https://'),
            true,
            Cookie::SAMESITE_LAX,
        );

        return $response;
    }

    private function sendToken(User $user, string $purpose, int $ttl, string $path): void
    {
        $plainToken = bin2hex(random_bytes(32));
        $now = time();
        $token = (new MagicLoginToken())
            ->setUser($user)
            ->setTokenHash(hash('sha256', $plainToken))
            ->setPurpose($purpose)
            ->setCreatedAt($now)
            ->setExpiresAt($now + $ttl);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $link = $this->frontendPath('/api/auth/' . $path . '/confirm?token=' . $plainToken);
        $this->mailer->send((new TemplatedEmail())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject($purpose === 'register' ? 'Regisztráció jóváhagyása' : 'Belépés jóváhagyása')
            ->htmlTemplate('emails/auth_link.html.twig')
            ->context([
                'name' => $user->getDisplayName() ?? $user->getEmail(),
                'action' => $purpose === 'register' ? 'regisztráció jóváhagyása' : 'belépés',
                'link' => $link,
                'minutes' => (int) ceil($ttl / 60),
            ]));
    }

    private function consumeToken(string $plainToken, string $purpose): ?MagicLoginToken
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $plainToken)) {
            return null;
        }

        $token = $this->tokens->findConsumableByTokenHash(
            hash('sha256', $plainToken),
            $purpose,
            time(),
        );
        if ($token === null) {
            return null;
        }

        $token->setConsumedAt(time());
        $this->entityManager->flush();

        return $token;
    }

    private function frontendPath(string $path): string
    {
        return rtrim($this->frontendUrl, '/') . $path;
    }
}
