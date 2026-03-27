<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 *
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Returns the Setting entity whose cle matches the given key, or null if not found.
     */
    public function findByKey(string $key): ?Setting
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.cle = :key')
            ->setParameter('key', $key)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Returns the raw value (valeur) string for the given key, or $default if the key does not exist.
     */
    public function getValue(string $key, ?string $default = null): ?string
    {
        $setting = $this->findByKey($key);

        if ($setting === null) {
            return $default;
        }

        return $setting->getValeur() ?? $default;
    }
}
