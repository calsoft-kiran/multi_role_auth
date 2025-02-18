<?php

namespace App\Repository;

use App\Entity\Ticket;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ticket>
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findAllTickets(): array
    {
        return $this->createQueryBuilder('t')
        ->orderBy('t.id', 'ASC')
        ->getQuery()
        ->setMaxResults(10)
        ->getArrayResult();
    }

    public function findTicketById(int $id): ?Ticket
    {
        return $this->find($id);
    }

   /**
    * @return Ticket[] Returns an array of Ticket objects
    */
   public function findByExampleField($value): array
   {
       return $this->createQueryBuilder('t')
        ->andWhere('t.created_by = :val')
        ->setParameter('val', $value)
        ->orderBy('t.id', 'ASC')
        ->setMaxResults(10)
        ->getQuery()
        ->getArrayResult();
   }
}
