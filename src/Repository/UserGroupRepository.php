<?php

namespace App\Repository;

use App\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<UserGroup>
 */
class UserGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, UserGroup::class);
    }


    public function save(UserGroup $entity, bool $flush = false): void
    {
        $this->em->persist($entity);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function remove(UserGroup $entity, bool $flush = false): void
    {
        $this->em->remove($entity);
        if ($flush) {            $this->em->flush();
        }
    }
}

