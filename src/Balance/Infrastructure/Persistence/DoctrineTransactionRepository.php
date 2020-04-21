<?php
declare(strict_types=1);

namespace Balance\Infrastructure\Persistence;

use Balance\Domain\Transaction;
use Balance\Domain\TransactionRepository;
use Doctrine\ORM\EntityRepository;

class DoctrineTransactionRepository extends EntityRepository implements TransactionRepository
{
    public function add(Transaction $transaction): void
    {
        $this->getEntityManager()->persist($transaction);
    }
}
