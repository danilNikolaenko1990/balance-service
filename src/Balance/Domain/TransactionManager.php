<?php
declare(strict_types=1);

namespace Balance\Domain;

interface TransactionManager
{
    /**
     * @param callable $operation
     * @return void
     */
    public function transactional(callable $operation): void;
}
