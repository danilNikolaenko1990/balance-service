<?php
declare(strict_types=1);

namespace Balance\Application\Request;

class DepositRequest
{
    /**
     * @var int
     */
    private $userId;

    /**
     * @var Money
     */
    private $amount;

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }
}
