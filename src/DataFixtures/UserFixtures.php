<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur administrateur
        $admin = new User();
        $admin->setEmail('admin@gmail.com');
        $admin->setFirstname('Admin');
        $admin->setLastname('System');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsVerified(true);
        $admin->setCreateAt(new \DateTimeImmutable());
        $hashedPassword = $this->passwordHasher->hashPassword($admin, 'Azerty11');
        $admin->setPassword($hashedPassword);
        $manager->persist($admin);

        // Créer quelques utilisateurs standards
        $users = [
            [
                'email' => 'user@gmail.com',
                'firstname' => 'John',
                'lastname' => 'Doe',
                'password' => 'Azerty11',
                'roles' => ['ROLE_USER']
            ],
            [
                'email' => 'owner@gmail.com',
                'firstname' => 'Jane',
                'lastname' => 'Smith',
                'password' => 'Azerty11',
                'roles' => ['ROLE_OWNER']
            ],
            [
                'email' => 'banned@gmail.com',
                'firstname' => 'Bob',
                'lastname' => 'Wilson',
                'password' => 'Azerty11',
                'roles' => ['ROLE_BANNED']
            ]
        ];

        foreach ($users as $userData) {
            $user = new User();
            $user->setEmail($userData['email']);
            $user->setFirstname($userData['firstname']);
            $user->setLastname($userData['lastname']);
            $user->setRoles($userData['roles']);
            $user->setIsVerified(true);
            $user->setCreateAt(new \DateTimeImmutable());
            $hashedPassword = $this->passwordHasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
