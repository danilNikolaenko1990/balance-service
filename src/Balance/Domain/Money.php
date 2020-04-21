<?php
declare(strict_types=1);

namespace Balance\Domain;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Embeddable()
 */
class Money
{
    /**
     * @var int
     * @ORM\Column(name="amount", type="bigint")
     */
    private $amount;

    /**
     * @var string
     * @ORM\Column(name="currency", type="string")
     */
    private $currency;

    public function __construct(int $amount, string $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function multiply(float $multiplier): self
    {
        return new self((int)($this->amount * $multiplier), $this->currency);
    }
}
