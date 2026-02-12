<?php

namespace App\Repository;

use App\Entity\Player;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class PlayerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }
    
    public function findByUsernameAndIdentifier(string $username, string $identifier): ?Player 
    {
         return $this->createQueryBuilder('p')
            ->andWhere('p.username = :username')
            ->andWhere('p.identifier_str = :identifier')
            ->setParameter('username', $username)
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult(); 
    }
    
    private function generateUniqueIdentifier(): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $entityManager = $this->getEntityManager();

        do {
            $identifier = '';
            for ($i = 0; $i < 5; $i++) {
                $identifier .= $letters[random_int(0, 25)];
            }

            $existing = $this->findOneBy(['identifier_str' => $identifier]);
        } while ($existing !== null);

        return $identifier;
    }

    public function insertPlayer(string $username, ?string $img = null): Player
    {
        $entityManager = $this->getEntityManager();

        $existing = $this->findOneBy(['username' => $username]);
        if ($existing) {
            throw new \Exception("Username '$username' already exists.");
        }

        $player = new Player();
        $player->setUsername($username);
        $player->setImg($img);
        $player->setIdentifierStr($this->generateUniqueIdentifier());

        try {
            $entityManager->persist($player);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new \Exception("Failed to insert player: " . $e->getMessage());
        }

        return $player;
    }
}
