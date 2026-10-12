<?php

declare(strict_types=1);

namespace App\Tests\Api\Auth;

use App\Api\Auth\AuthFlowService;
use App\Entity\AuthSession;
use App\Entity\MagicLoginToken;
use App\Entity\User;
use App\Repository\AuthSessionRepository;
use App\Repository\MagicLoginTokenRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

final class AuthFlowServiceTest extends TestCase
{
    public function testRegistrationRequiresTermsAndSendsTenMinuteConfirmation(): void
    {
        $users = new AuthFlowTestUserRepository();
        $tokens = new AuthFlowTestTokenRepository();
        $sessions = new AuthFlowTestSessionRepository();
        $persisted = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::exactly(4))->method('flush');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (Email $email): bool {
                return $email->getSubject() === 'Regisztráció jóváhagyása';
            }));
        $flow = $this->createFlow($entityManager, $users, $tokens, $sessions, $mailer);

        $rejected = $flow->register($this->jsonRequest([
            'name' => 'Teszt Elek',
            'email' => 'teszt@example.com',
            'termsAccepted' => false,
        ]));
        self::assertSame(422, $rejected->getStatusCode());

        $response = $flow->register($this->jsonRequest([
            'name' => 'Teszt Elek',
            'email' => 'TESZT@example.com',
            'termsAccepted' => true,
        ]));

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(2, $persisted);
        self::assertInstanceOf(User::class, $persisted[0]);
        self::assertFalse($persisted[0]->isActive());
        self::assertContains('ROLE_USER', $persisted[0]->getRoles());
        self::assertSame($persisted[0]->getCreatedAt(), $persisted[0]->getTermsAcceptedAt());
        self::assertInstanceOf(MagicLoginToken::class, $persisted[1]);
        self::assertSame('register', $persisted[1]->getPurpose());
        self::assertSame(600, $persisted[1]->getExpiresAt() - $persisted[1]->getCreatedAt());

        $tokens->consumable = $persisted[1];
        $confirmation = $flow->confirmRegistration(str_repeat('a', 64));
        self::assertSame('/login', parse_url($confirmation->getTargetUrl(), PHP_URL_PATH));
        self::assertSame('registered=1', parse_url($confirmation->getTargetUrl(), PHP_URL_QUERY));
        self::assertTrue($persisted[0]->isActive());
        self::assertNotNull($persisted[0]->getEmailVerifiedAt());
        self::assertNotNull($persisted[1]->getConsumedAt());
    }

    public function testLoginCreatesSevenDaySessionAndLogoutRevokesIt(): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setDisplayName('Teszt Elek')
            ->setIsActive(true);
        $users = new AuthFlowTestUserRepository();
        $users->activeUser = $user;
        $tokens = new AuthFlowTestTokenRepository();
        $sessions = new AuthFlowTestSessionRepository();
        $persisted = [];
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::exactly(2))
            ->method('persist')
            ->willReturnCallback(static function (object $entity) use (&$persisted): void {
                $persisted[] = $entity;
            });
        $entityManager->expects(self::exactly(4))->method('flush');
        $entityManager->expects(self::once())->method('remove');
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');
        $flow = $this->createFlow($entityManager, $users, $tokens, $sessions, $mailer);

        $flow->requestLogin($this->jsonRequest(['email' => 'USER@example.com']));
        self::assertInstanceOf(MagicLoginToken::class, $persisted[0]);
        self::assertSame('login', $persisted[0]->getPurpose());
        self::assertSame(900, $persisted[0]->getExpiresAt() - $persisted[0]->getCreatedAt());
        $tokens->consumable = $persisted[0];

        $loginResponse = $flow->confirmLogin(str_repeat('b', 64));
        self::assertSame('https://example.test/', $loginResponse->headers->get('Location'));
        self::assertInstanceOf(AuthSession::class, $persisted[1]);
        self::assertSame(604800, $persisted[1]->getExpiresAt() - $persisted[1]->getCreatedAt());
        self::assertTrue($loginResponse->headers->getCookies()[0]->isHttpOnly());

        $sessions->session = $persisted[1];
        $logout = $flow->logout(Request::create('/api/auth/logout', 'POST', [], [
            'base_project_auth' => $loginResponse->headers->getCookies()[0]->getValue(),
        ]));
        self::assertSame(200, $logout->getStatusCode());
        self::assertEmpty($logout->headers->getCookies()[0]->getValue());
        self::assertLessThan(time(), $logout->headers->getCookies()[0]->getExpiresTime());
    }

    private function createFlow(
        EntityManagerInterface $entityManager,
        UserRepository $users,
        MagicLoginTokenRepository $tokens,
        AuthSessionRepository $sessions,
        MailerInterface $mailer,
    ): AuthFlowService {
        return new AuthFlowService(
            $entityManager,
            $users,
            $tokens,
            $sessions,
            $mailer,
            'no-reply@example.com',
            'https://example.test',
        );
    }

    private function jsonRequest(array $data): Request
    {
        return Request::create(
            '/api/auth',
            'POST',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }
}

final class AuthFlowTestUserRepository extends UserRepository
{
    public ?User $activeUser = null;

    public function __construct()
    {
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        return $this->activeUser;
    }

    public function findActiveByEmail(string $email): ?User
    {
        return $this->activeUser;
    }
}

final class AuthFlowTestTokenRepository extends MagicLoginTokenRepository
{
    public ?MagicLoginToken $consumable = null;

    public function __construct()
    {
    }

    public function findConsumableByTokenHash(
        string $tokenHash,
        string $purpose,
        int $now,
    ): ?MagicLoginToken {
        return $this->consumable;
    }
}

final class AuthFlowTestSessionRepository extends AuthSessionRepository
{
    public ?AuthSession $session = null;

    public function __construct()
    {
    }

    public function findOneBy(array $criteria, ?array $orderBy = null): ?object
    {
        return $this->session;
    }
}
