<?php

namespace App\Repository;

use App\Entity\Utm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class UtmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utm::class);
    }

    public function find_by_id(int $id): ?Utm
    {
        return $this->find($id);
    }

    public function find_by_source_campaign_medium (
        string $utmSource,
        string $utmCampaign,
        string $utmMedium
    ): ?Utm {
        return $this->createQueryBuilder('u')
            ->andWhere('u.utm_source = :utm_source')
            ->andWhere('u.utm_campaign = :utm_campaign')
            ->andWhere('u.utm_medium = :utm_medium')
            ->setParameter('utm_source', $utmSource)
            ->setParameter('utm_campaign', $utmCampaign)
            ->setParameter('utm_medium', $utmMedium)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function find_by_source(
        string $utm_source,
    ): ?Utm {
        return $this->createQueryBuilder('u')
            ->andWhere('u.utm_source = :utm_source')
            ->setParameter('utm_source', $utm_source)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function find_by_campaign(
        string $utm_campaign,
    ): ?Utm {
        return $this->createQueryBuilder('u')
            ->andWhere('u.utm_source = :utm_source')
            ->setParameter('utm_campaign', $utm_campaign)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function find_by_medium(
        string $utm_medium,
    ): ?Utm {
        return $this->createQueryBuilder('u')
            ->andWhere('u.utm_source = :utm_source')
            ->setParameter('utm_campaign', $utm_medium)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function insert_utm(
        string $utm_source,
        string $utm_campaign,
        string $utm_medium
    ): Utm {
        $entityManager = $this->getEntityManager();

        $existing = $this->find_by_source_campaign_medium(
            $utm_source,
            $utm_campaign,
            $utm_medium
        );

        if ($existing) {
            throw new \Exception("UTM entry already exists.");
        }

        $utm = new Utm();
        $utm->set_utm_source($utm_source);
        $utm->set_utm_campaign($utm_campaign);
        $utm->set_utm_medium($utm_medium);

        try {
            $entityManager->persist($utm);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new \Exception("failed to insert utm");
        }

        return $utm;
    }
}