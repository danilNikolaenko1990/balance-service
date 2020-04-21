<?php
declare(strict_types=1);

namespace Balance\Domain;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Balance\Infrastructure\Persistence\DoctrineTransactionRepository")
 * @ORM\Table(name="user_transaction")
 * @ORM\HasLifecycleCallbacks
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(name="amount", type="bigint")
     */
    private $userId;

    /**
     * @var Money
     * @ORM\Embedded(class="Money")
     */
    private $amount;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    public static function createDeposit(Money $amount, int $userId): self
    {
        $it = new self;

        $it->amount = $amount;
        $it->userId = $userId;

        return $it;
    }

    public static function createWithdraw(Money $amount, int $userId): self
    {
        $it = new self;

        $it->amount = $amount->multiply(-1);
        $it->userId = $userId;

        return $it;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->createdAt = new \DateTime("now");
    }

    private function __construct()
    {
    }
}
