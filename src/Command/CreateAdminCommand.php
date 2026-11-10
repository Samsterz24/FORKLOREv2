<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:create-admin', description: 'Create an admin user')]
class CreateAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = 'Samsterz24';
        $email = 'samsterz24@example.com';
        $password = '1234';

        $existing = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($existing) {
            $output->writeln('Admin already exists.');
            return Command::SUCCESS;
        }

        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($this->hasher->hashPassword($user, $password));
        $user->setRole('ROLE_ADMIN');
        $this->em->persist($user);
        $this->em->flush();

        $output->writeln('Admin created.');
        return Command::SUCCESS;
    }
}