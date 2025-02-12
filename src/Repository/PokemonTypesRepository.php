<?php

namespace App\Repository;

use App\Entity\PokemonTypes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PokemonTypes>
 *
 * @method PokemonTypes|null find($id, $lockMode = null, $lockVersion = null)
 * @method PokemonTypes|null findOneBy(array $criteria, array $orderBy = null)
 * @method PokemonTypes[]    findAll()
 * @method PokemonTypes[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PokemonTypesRepository extends ServiceEntityRepository {
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, PokemonTypes::class);
    }

    /**
     * @param PokemonTypes $entity
     * @param bool $flush
     * @return void
     */
    public function add(PokemonTypes $entity, bool $flush = false): void {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param PokemonTypes $entity
     * @param bool $flush
     * @return void
     */
    public function remove(PokemonTypes $entity, bool $flush = false): void {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return PokemonTypes[] Returns an array of PokemonTypes objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PokemonTypes
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
