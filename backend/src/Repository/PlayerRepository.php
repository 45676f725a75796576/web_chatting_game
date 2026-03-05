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

    public function find_by_id(int $id): ?Player
    {
        return $this->find($id);
    }

    
    public function find_by_username_and_identifier(string $username, string $identifier): ?Player 
    {
         return $this->createQueryBuilder('p')
            ->andWhere('p.username = :username')
            ->andWhere('p.identifier_str = :identifier')
            ->setParameter('username', $username)
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult(); 
    }
    
    public function find_by_username(string $username): ?Player
    {
        return $this->findOneBy(['username' => $username]);
    }
    
    private function generate_unique_identifier(): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $identifier = '';
            for ($i = 0; $i < 5; $i++) {
                $identifier .= $letters[random_int(0, 51)];
            }

            $existing = $this->findOneBy(['identifier_str' => $identifier]);
        } while ($existing !== null);

        return $identifier;
    }

    public function insert_player(string $username, ?string $img = null): Player
    {
        $entityManager = $this->getEntityManager();

        $existing = $this->findOneBy(['username' => $username]);
        if ($existing) {
            throw new \Exception("username '$username' already exists.");
        }

        $player = new Player();
        $player->set_username($username);
        $player->set_img($img);
        $player->set_identifier_str($this->generate_unique_identifier());

        try {
            $entityManager->persist($player);
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new \Exception("failed to insert player");
        }

        return $player;
    }
}
