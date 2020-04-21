<?php
declare(strict_types=1);

namespace Balance\Application;

use Balance\Application\Request\DepositRequest;
use Balance\Application\Request\TransferRequest;
use Balance\Application\Request\WithdrawRequest;
use Balance\Application\Request\Money as AmountRequest;
use Balance\Domain\Transaction;
use Balance\Domain\TransactionManager;
use Balance\Domain\TransactionRepository;
use Balance\Domain\Money;
use Balance\Domain\UserOperationLocker;

class BalanceService
{
    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var TransactionManager
     */
    private $transactionManager;

    /**
     * @var UserOperationLocker
     */
    private $userOperationLocker;

    public function __construct(
        TransactionRepository $transactionRepository,
        TransactionManager $transactionManager,
        UserOperationLocker $userOperationLocker
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->transactionManager = $transactionManager;
        $this->userOperationLocker = $userOperationLocker;
    }

    public function withdraw(WithdrawRequest $withdrawRequest): void
    {
        $amountRequest = $withdrawRequest->getAmount();
        $this->assertThatAmountIsPositive($amountRequest);

        $transaction = Transaction::createWithdraw(
            new Money($amountRequest->getAmount(), $amountRequest->getCurrency()),
            $withdrawRequest->getUserId()
        );

        $this->persistTransactions([$transaction]);
    }

    public function deposit(DepositRequest $depositRequest): void
    {
        $amountRequest = $depositRequest->getAmount();
        $this->assertThatAmountIsPositive($amountRequest);

        $transaction = Transaction::createDeposit(
            new Money($amountRequest->getAmount(), $amountRequest->getCurrency()),
            $depositRequest->getUserId()
        );

        $this->persistTransactions([$transaction]);
    }

    public function transfer(TransferRequest $transferRequest): void
    {
        $amountToTransfer = $transferRequest->getAmountToTransfer();
        $this->assertThatAmountIsPositive($amountToTransfer);

        $transactionsToPersist[] = Transaction::createWithdraw(
            new Money($amountToTransfer->getAmount(), $amountToTransfer->getCurrency()),
            $transferRequest->getUserFrom()
        );

        $transactionsToPersist[] = Transaction::createDeposit(
            new Money($amountToTransfer->getAmount(), $amountToTransfer->getCurrency()),
            $transferRequest->getUserTo()
        );

        $this->persistTransactions($transactionsToPersist);
    }

    /**
     * @param Transaction[] $transactions
     */
    private function persistTransactions(array $transactions): void
    {
        $this->transactionManager->transactional(
            function () use ($transactions) {
                /** @var Transaction[] $transactions */
                foreach ($transactions as $transaction) {
                    $this->userOperationLocker->lock($transaction->getUserId());
                    $this->transactionRepository->add($transaction);
                }
            }
        );
    }

    /**
     * @param AmountRequest $amountRequest
     * @throws \InvalidArgumentException
     */
    private function assertThatAmountIsPositive(AmountRequest $amountRequest)
    {
        if ($amountRequest->getAmount() < 0) {
            throw new \InvalidArgumentException('only positive amount allowed');
        }
    }
}
