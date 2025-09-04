<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:create-admin',
    description: 'Create or update an admin user (ROLE_ADMIN)'
)]
class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Plain password (unsafe to pass in CI logs)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = (string)($input->getArgument('email') ?? '');
        $plainPassword = (string)($input->getOption('password') ?? '');
        $nonInteractive = (bool)$input->getOption('no-interaction');

        if ($email === '' && !$nonInteractive) {
            $question = new Question('Admin email: ');
            $email = (string)$this->getHelper('question')->ask($input, $output, $question);
        }

        $email = strtolower(trim($email));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $io->error('A valid email is required.');
            return Command::INVALID;
        }

        $user = $this->users->findOneBy(['email' => $email]);
        $isNew = false;
        if (!$user) {
            $user = (new User())->setEmail($email);
            $isNew = true;
        }

        if ($plainPassword === '' && !$nonInteractive) {
            $q = new Question($isNew ? 'Admin password (hidden): ' : 'New password (leave blank to keep): ');
            $q->setHidden(true);
            $q->setHiddenFallback(false);
            $q->setValidator(function ($value) use ($isNew) {
                $value = (string)$value;
                if ($isNew && $value === '') {
                    throw new \RuntimeException('Password cannot be empty for a new user.');
                }
                if ($value !== '' && strlen($value) < 8) {
                    throw new \RuntimeException('Password must be at least 8 characters.');
                }
                return $value;
            });
            $plainPassword = (string)$this->getHelper('question')->ask($input, $output, $q);
        }

        if ($isNew && $plainPassword === '') {
            $io->error('Password is required to create a new user in non-interactive mode.');
            return Command::INVALID;
        }

        // Ensure ROLE_ADMIN is present; avoid storing ROLE_USER explicitly
        $roles = array_values(array_unique(array_merge($user->getRoles(), ['ROLE_ADMIN'])));
        $roles = array_values(array_filter($roles, static fn(string $r) => $r !== 'ROLE_USER'));
        $user->setRoles($roles);

        if ($plainPassword !== '') {
            $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
        }

        $this->users->save($user, true);

        $io->success(sprintf('%s admin user %s', $isNew ? 'Created' : 'Updated', $email));
        return Command::SUCCESS;
    }
}

