<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->em->remove($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function upgradePassword(User $user, string $newHashedPassword): void
    {
        $user->setPassword($newHashedPassword);
        $this->save($user, true);
    }
}

