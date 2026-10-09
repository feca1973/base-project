<?php

declare(strict_types=1);

namespace App\Command;

use App\Clock\UnixTimestampProvider;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:create-super-admin',
    description: 'Creates the first active super admin user for magic-login access.',
)]
final class CreateSuperAdminCommand extends Command
{
    private const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly UnixTimestampProvider $timestampProvider,
        private readonly ValidatorInterface $validator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Super admin email address.')
            ->addOption('display-name', null, InputOption::VALUE_REQUIRED, 'Optional display name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = strtolower(trim((string) $input->getArgument('email')));
        $displayName = $input->getOption('display-name');
        $displayName = is_string($displayName) ? trim($displayName) : null;

        if (!$this->isValidEmail($email)) {
            $io->error('Invalid email address.');

            return Command::INVALID;
        }

        if ($this->userRepository->findOneBy(['email' => $email]) instanceof User) {
            $io->error('User already exists.');

            return Command::FAILURE;
        }

        $now = $this->timestampProvider->now();
        $user = (new User())
            ->setEmail($email)
            ->setDisplayName($displayName)
            ->setRoles([self::ROLE_SUPER_ADMIN])
            ->setIsActive(true)
            ->setCreatedAt($now)
            ->setUpdatedAt($now);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success(sprintf('Super admin user created for magic-login: %s', $email));

        return Command::SUCCESS;
    }

    private function isValidEmail(string $email): bool
    {
        return count($this->validator->validate($email, new Email())) === 0;
    }
}
