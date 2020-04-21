<?php
declare(strict_types=1);

namespace Balance\Domain;

interface TransactionRepository
{
    public function add(Transaction $transaction): void;
}
