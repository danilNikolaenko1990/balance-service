<?php
declare(strict_types=1);

namespace Balance\Application\Request;

class TransferRequest
{
    /**
     * @var int
     */
    private $userFrom;

    /**
     * @var int
     */
    private $userTo;

    /**
     * @var Money
     */
    private $amountToTransfer;

    public function getUserFrom(): int
    {
        return $this->userFrom;
    }

    public function getUserTo(): int
    {
        return $this->userTo;
    }

    public function getAmountToTransfer(): Money
    {
        return $this->amountToTransfer;
    }
}
