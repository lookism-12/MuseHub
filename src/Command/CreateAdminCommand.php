<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin',
    description: 'Create an admin user account',
)]
class CreateAdminCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('email', null, InputOption::VALUE_OPTIONAL, 'Admin email', 'admin@musehub.com')
            ->addOption('password', null, InputOption::VALUE_OPTIONAL, 'Admin password', 'admin123')
            ->addOption('username', null, InputOption::VALUE_OPTIONAL, 'Admin username', 'admin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getOption('email');
        $password = $input->getOption('password');
        $username = $input->getOption('username');

        // Vérifier si l'utilisateur existe déjà
        $existingUser = $this->userRepository->findOneByEmail($email);
        if ($existingUser) {
            $io->warning("User with email {$email} already exists!");
            $io->info("Email: {$email}");
            $io->info("Username: {$existingUser->getUsername()}");
            $io->info("Roles: " . implode(', ', $existingUser->getRoles()));
            return Command::SUCCESS;
        }

        // Créer l'utilisateur admin
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_ADMIN']);
        $user->setIsActive(true);
        $user->setBio('Administrator account');

        $this->em->persist($user);
        $this->em->flush();

        $io->success('Admin user created successfully!');
        $io->table(
            ['Property', 'Value'],
            [
                ['Email', $email],
                ['Username', $username],
                ['Password', $password],
                ['Roles', 'ROLE_ADMIN'],
            ]
        );

        $io->note('You can now login at: http://localhost:8000/login');

        return Command::SUCCESS;
    }
}


