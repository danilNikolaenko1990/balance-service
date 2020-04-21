<?php
declare(strict_types=1);

namespace Balance\Infrastructure\Persistence;

use Balance\Domain\TransactionManager;
use Doctrine\ORM\EntityManagerInterface;

class DoctrineTransactionManager implements TransactionManager
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Exception
     */
    public function transactional(callable $operation): void
    {
        $this->entityManager->getConnection()->beginTransaction();
        try {
            $operation();

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();

            throw $e;
        }
    }
}
