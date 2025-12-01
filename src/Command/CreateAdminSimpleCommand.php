<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-admin-simple',
    description: 'Create an admin user account (simple version)',
)]
class CreateAdminSimpleCommand extends Command
{
    public function __construct(
        private Connection $connection,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = 'admin@musehub.com';
        $password = 'admin123';
        $username = 'admin';

        // Vérifier si l'utilisateur existe
        $existing = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM user WHERE email = ?',
            [$email]
        );

        if ($existing > 0) {
            $io->warning("Admin user already exists!");
            $io->info("Email: {$email}");
            $io->info("Password: {$password}");
            return Command::SUCCESS;
        }

        // Générer UUID
        $uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // Créer un utilisateur temporaire pour hasher le mot de passe
        $tempUser = new \App\Entity\User();
        $hashedPassword = $this->passwordHasher->hashPassword($tempUser, $password);

        // Insérer l'utilisateur
        try {
            $this->connection->executeStatement(
                'INSERT INTO user (uuid, email, password, username, roles, is_active, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $uuid,
                    $email,
                    $hashedPassword,
                    $username,
                    json_encode(['ROLE_ADMIN']),
                    1,
                    date('Y-m-d H:i:s')
                ]
            );

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
        } catch (\Exception $e) {
            $io->error('Failed to create admin user: ' . $e->getMessage());
            $io->note('You may need to add the uuid column first. Run:');
            $io->text('ALTER TABLE user ADD COLUMN uuid VARCHAR(36) UNIQUE AFTER id;');
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}


