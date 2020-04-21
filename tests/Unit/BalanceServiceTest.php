<?php
declare(strict_types=1);

namespace Tests\Unit;

use Balance\Application\BalanceService;
use Balance\Application\Request\DepositRequest;
use Balance\Application\Request\Money as MoneyRequest;
use Balance\Application\Request\TransferRequest;
use Balance\Application\Request\WithdrawRequest;
use Balance\Domain\Money;
use Balance\Domain\Transaction;
use Balance\Domain\TransactionRepository;
use Balance\Domain\UserOperationLocker;
use Balance\Domain\UserOperationLockingException;
use Balance\Infrastructure\Persistence\DoctrineTransactionManager;
use Test\Utils\DoctrineRepositoryTestCase;

class BalanceServiceTest extends DoctrineRepositoryTestCase
{
    private const DEFAULT_USER_ID = 1;
    private const USER_FROM = 2;
    private const USER_TO = 3;
    private const DEFAULT_AMOUNT = 10;
    private const DEFAULT_CURRENCY = 'USD';

    /**
     * @var BalanceService
     */
    private $balanceService;

    /**
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * @var UserOperationLocker
     */
    private $userOperationLocker;

    private $recordedLockedUsersIds;

    protected function setUp()
    {
        parent::setUp();

        $this->transactionRepository = self::$entityManager->getRepository(Transaction::class);

        $this->userOperationLocker = $this->createUserOperationLocker();

        $this->balanceService = new BalanceService(
            $this->transactionRepository,
            new DoctrineTransactionManager(self::$entityManager),
            $this->userOperationLocker
        );
    }

    /**
     * @test
     */
    public function withdraw_GivenWithdrawRequest_WithdrawsFromUser()
    {
        $amount = $this->createAmountRequest(self::DEFAULT_AMOUNT);

        $withdrawRequest = $this->createWithdrawRequest($amount);

        $this->balanceService->withdraw($withdrawRequest);

        /** @var Transaction $transaction */
        $actualTransaction = $this->transactionRepository->findAll()[0];
        $this->assertThatWithdrawTransactionWasCreatedForCertainUserId(self::DEFAULT_USER_ID, $actualTransaction);

        $this->assertThatUserOperationLockerWasCalledForCertainUserIds([self::DEFAULT_USER_ID]);
    }

    /**
     * @test
     */
    public function deposit_GivenDepositRequest_DepositsToUser()
    {
        $amount = $this->createAmountRequest(self::DEFAULT_AMOUNT);

        $depositRequest = $this->createDepositRequest($amount);

        $this->balanceService->deposit($depositRequest);

        /** @var Transaction $transaction */
        $actualTransaction = $this->transactionRepository->findAll()[0];
        $this->assertThatDepositTransactionWasCreatedForCertainUserId(self::DEFAULT_USER_ID, $actualTransaction);

        $this->assertThatUserOperationLockerWasCalledForCertainUserIds([self::DEFAULT_USER_ID]);
    }

    /**
     * @test
     */
    public function transfer_GivenTransferRequest_CreatesTwoTransactions()
    {
        $amountToTransfer = $this->createAmountRequest(self::DEFAULT_AMOUNT);

        $transferRequest = $this->createTransferRequest($amountToTransfer);

        $this->balanceService->transfer($transferRequest);

        $this->assertThatWithdrawTransactionWasCreatedForCertainUserId(
            self::USER_FROM,
            $actualTransaction = $this->transactionRepository->findAll()[0]
        );

        $this->assertThatDepositTransactionWasCreatedForCertainUserId(
            self::USER_TO,
            $actualTransaction = $this->transactionRepository->findAll()[1]
        );

        $this->assertThatUserOperationLockerWasCalledForCertainUserIds([self::USER_FROM, self::USER_TO]);
    }

    /**
     * @test
     */
    public function transfer_GivenTransferRequestAndUserOperationLockerThrowsException_TransactionsNotPersisted()
    {
        $this->givenUserOperationThrowsException();

        $amountToTransfer = $this->createAmountRequest(self::DEFAULT_AMOUNT);
        $transferRequest = $this->createTransferRequest($amountToTransfer);

        try {
            $this->balanceService->transfer($transferRequest);
        } catch (UserOperationLockingException $e) {
            //$this->expectException() делает бессмысленными последующие проверки, поэтому как-то так
            $this->assertThatTransactionWasNotPersisted();

            return;
        }

        $this->fail('Failed asserting that exception of type "UserOperationLockingException" is thrown.');
    }

    /**
     * @test
     */
    public function transfer_GivenTransferRequestHasNegativeAmount_ThrowsInvalidArgumentException()
    {
        $amountToTransfer = $this->createRequestWithNegativeAmount();;
        $transferRequest = $this->createTransferRequest($amountToTransfer);

        try {
            $this->balanceService->transfer($transferRequest);
        } catch (\InvalidArgumentException $e) {
            $this->assertThatTransactionWasNotPersisted();

            return;
        }

        $this->fail('Failed asserting that exception of type "InvalidArgumentException" is thrown.');
    }

    /**
     * @test
     */
    public function withdraw_GivenWithdrawRequestHasNegativeAmount_ThrowsInvalidArgumentException()
    {
        $amount = $this->createRequestWithNegativeAmount();;
        $withdrawRequest = $this->createWithdrawRequest($amount);

        try {
            $this->balanceService->withdraw($withdrawRequest);
        } catch (\InvalidArgumentException $e) {
            $this->assertThatTransactionWasNotPersisted();

            return;
        }

        $this->fail('Failed asserting that exception of type "InvalidArgumentException" is thrown.');
    }

    /**
     * @test
     */
    public function deposit_GivenDepositRequestHasNegativeAmount_ThrowsInvalidArgumentException()
    {
        $amount = $this->createRequestWithNegativeAmount();
        $depositRequest = $this->createDepositRequest($amount);

        try {
            $this->balanceService->deposit($depositRequest);
        } catch (\InvalidArgumentException $e) {
            $this->assertThatTransactionWasNotPersisted();

            return;
        }

        $this->fail('Failed asserting that exception of type "InvalidArgumentException" is thrown.');
    }


    /**
     * @test
     */
    public function deposit_GivenDepositRequestAndUserOperationLockerThrowsException_TransactionsNotPersisted()
    {
        $this->givenUserOperationThrowsException();

        $amount = $this->createAmountRequest(self::DEFAULT_AMOUNT);
        $depositRequest = $this->createDepositRequest($amount);

        try {
            $this->balanceService->deposit($depositRequest);
        } catch (UserOperationLockingException $e) {
            $this->assertThatTransactionWasNotPersisted();

            return;
        }

        $this->fail('Failed asserting that exception of type "UserOperationLockingException" is thrown.');
    }

    /**
     * @test
     */
    public function withdraw_GivenWithdrawRequestAndUserOperationLockerThrowsException_TransactionsNotPersisted()
    {
        $this->givenUserOperationThrowsException();

        $amount = $this->createAmountRequest(self::DEFAULT_AMOUNT);
        $withdrawRequest = $this->createWithdrawRequest($amount);

        try {
            $this->balanceService->withdraw($withdrawRequest);
        } catch (UserOperationLockingException $e) {
            $this->assertThatTransactionWasNotPersisted();

            return;
        }

        $this->fail('Failed asserting that exception of type "UserOperationLockingException" is thrown.');
    }

    private function createRequestWithNegativeAmount(): MoneyRequest
    {
        return $this->createAmountRequest(-self::DEFAULT_AMOUNT);
    }

    private function givenUserOperationThrowsException()
    {
        \Phake::when($this->userOperationLocker)
            ->lock(\Phake::anyParameters())
            ->thenThrow(new UserOperationLockingException);
    }

    private function assertThatTransactionWasNotPersisted()
    {
        $this->assertEmpty($this->transactionRepository->findAll());
    }

    private function createTransferRequest(MoneyRequest $amount): TransferRequest
    {
        $transferRequest =  new TransferRequest;

        $this->setObjectField($transferRequest, 'amountToTransfer', $amount);
        $this->setObjectField($transferRequest, 'userFrom', self::USER_FROM);
        $this->setObjectField($transferRequest, 'userTo', self::USER_TO);

        return $transferRequest;
    }

    private function assertThatDepositTransactionWasCreatedForCertainUserId($userId, Transaction $actualTransaction)
    {
        $this->assertThatTransactionHasCertainUserIdAndAmount(
            $userId,
            self::DEFAULT_AMOUNT,
            $actualTransaction
        );
    }

    private function assertThatWithdrawTransactionWasCreatedForCertainUserId($userId, Transaction $actualTransaction)
    {
        $this->assertThatTransactionHasCertainUserIdAndAmount(
            $userId,
            -self::DEFAULT_AMOUNT,
            $actualTransaction
        );
    }

    private function assertThatTransactionHasCertainUserIdAndAmount(
        int $userId,
        int $amount,
        Transaction $actualTransaction
    ) {
        $actualUserId = $this->extractObjectField($actualTransaction, 'userId');
        /** @var Money $actualAmountMoney */
        $actualAmountMoney = $this->extractObjectField($actualTransaction, 'amount');

        $this->assertEquals($userId, $actualUserId);

        $this->assertEquals(
            $amount,
            $this->extractObjectField($actualAmountMoney, 'amount')
        );

        $this->assertEquals(
            self::DEFAULT_CURRENCY,
            $this->extractObjectField($actualAmountMoney, 'currency')
        );

        $this->assertInstanceOf(\DateTimeInterface::class, $this->extractObjectField($actualTransaction, 'createdAt'));
    }

    private function extractObjectField($object, $fieldName)
    {
        $property = new \ReflectionProperty(get_class($object), $fieldName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }

    private function createWithdrawRequest(
        MoneyRequest $amount,
        $userId = self::DEFAULT_USER_ID
    ): WithdrawRequest {
        $request = new WithdrawRequest();

        $this->setObjectField($request, 'userId', $userId);
        $this->setObjectField($request, 'amount', $amount);

        return $request;
    }

    private function createDepositRequest(
        MoneyRequest $amount,
        $userId = self::DEFAULT_USER_ID
    ): DepositRequest {
        $request = new DepositRequest();

        $this->setObjectField($request, 'userId', $userId);
        $this->setObjectField($request, 'amount', $amount);

        return $request;
    }

    private function createAmountRequest(int $amount, string $currency = self::DEFAULT_CURRENCY): MoneyRequest
    {
        $amountRequest = new MoneyRequest();

        $this->setObjectField($amountRequest, 'amount', $amount);
        $this->setObjectField($amountRequest, 'currency', $currency);

        return $amountRequest;
    }

    private function setObjectField(object $object, string $fieldName, $value)
    {
        $property = new \ReflectionProperty(get_class($object), $fieldName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }

    private function assertThatUserOperationLockerWasCalledForCertainUserIds(array $expectedUserIds)
    {
        $this->assertEquals($expectedUserIds, $this->recordedLockedUsersIds);
    }

    private function createUserOperationLocker(): UserOperationLocker
    {
        $userOperationLocker = \Phake::mock(UserOperationLocker::class);

        \Phake::when($userOperationLocker)
            ->lock(\Phake::anyParameters())
            ->thenReturnCallback(
                function ($userId) {
                    $this->recordedLockedUsersIds[] = $userId;
                }
            );

        return $userOperationLocker;
    }

    protected static function getAnnotationMetadataConfigurationPaths(): array
    {
        return [
            self::getClassDirectory(Transaction::class)
        ];
    }
}
