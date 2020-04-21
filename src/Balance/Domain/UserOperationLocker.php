<?php
declare(strict_types=1);

namespace Balance\Domain;

interface UserOperationLocker
{
    /**
     * @param int $userId
     * @throws UserOperationLockingException
     * @return void
     */
    public function lock(int $userId): void;
}
