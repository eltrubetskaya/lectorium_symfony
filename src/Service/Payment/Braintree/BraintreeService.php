<?php

namespace App\Service\Payment\Braintree;

use App\Entity\User;
use Braintree\Customer;
use Braintree\Gateway;
use Braintree\Result\Error;
use Braintree\Result\Successful;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class BraintreeService
{
    /**
     * The first name value must be less than or equal to 255 characters.
     */
    public const FIRST_NAME = 'firstName';

    /**
     * The last name value must be less than or equal to 255 characters.
     */
    public const LAST_NAME = 'lastName';

    /**
     * The customer’s company. 255 character maximum.
     */
    private const COMPANY = 'company';

    /**
     * The customer’s email address, comprised of ASCII characters.
     */
    public const EMAIL = 'email';

    /**
     * Phone number. Phone must be 10-14 characters and can
     * only contain numbers, dashes, parentheses and periods.
     */
    public const PHONE = 'phone';

    /**
     * Website URL. Must be less than or equal to 255 characters.
     * Website must be well-formed. The URL scheme is optional.
     */
    private const WEBSITE = 'website';

    public const CUSTOMER_ID = 'customerId';

    private const PAYMENT_METHOD_NONCE = 'paymentMethodNonce';

    /**
     * BillingAddress block name
     */
    private const BILLING_ADDRESS = 'billingAddress';

    public const TYPE_CREDIT_CARD = 'CreditCard';

    public const TYPE_PAYPAL_ACCOUNT= 'PayPalAccount';

    public const TYPE_APPLE_PAY_CARD= 'ApplePayCard';

    public const TYPE_ANDROID_PAY_CARD= 'AndroidPayCard';

    public const TYPE_AMEX_EXPRESS_CHECKOUT_CARD = 'amexExpressCheckoutCard';

    public const TYPE_EUROPE_BANK_ACCOUNT = 'europeBankAccount';

    public const TYPE_US_BANK_ACCOUNT = 'usBankAccount';

    public const TYPE_VENMO_ACCOUNT = 'venmoAccount';

    public const TYPE_VISA_CHECKOUT_CARD = 'visaCheckoutCard';

    public const TYPE_MASTERPASS_CARD= 'masterpassCard';

    public const TYPE_SAMSUNG_PAY_CARD= 'samsungPayCard';

    /**
     * @var string
     */
    private $environment;

    /**
     * @var string
     */
    private $merchantId;

    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var string
     */
    private $privateKey;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Gateway
     */
    protected $gateway;

    /**
     * @param string $environment
     * @param string $merchantId
     * @param string $publicKey
     * @param string $privateKey
     * @param ManagerRegistry $doctrine
     * @param LoggerInterface $logger
     */
    public function __construct(string $environment, string $merchantId, string $publicKey, string $privateKey, ManagerRegistry $doctrine, LoggerInterface $logger)
    {
        $this->gateway = new Gateway([
            'environment' => $environment,
            'merchantId' => $merchantId,
            'publicKey' => $publicKey,
            'privateKey' => $privateKey
        ]);
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    /**
     * Generated customer
     *
     * @return Error|Successful|null
     */
    public function createCustomer(User $user)
    {
        try {
            $result = $this->gateway->customer()->create(
                [
                    self::FIRST_NAME => $user->getFirstName(),
                    self::LAST_NAME => $user->getLastName(),
                    self::EMAIL => $user->getEmail(),
                    self::PHONE => $user->getPhone(),
                    self::WEBSITE => 'med-service.ck.ua'
                ]
            );

            return $result;
        } catch (\Exception $e) {
            $this->logger->critical('Braintree_Exception: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param $customerId
     * @return bool|Customer|null
     */
    public function getCustomer($customerId)
    {
        try {
            return $this->gateway->customer()->find($customerId);
        } catch (\Exception $e) {
            $this->logger->critical('Braintree_Exception: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Returns a string which contains all authorization and
     * configuration information your client needs to initialize
     * the client SDK to communicate with Braintree.
     *
     * @param array $params
     * @return string|array
     */
    public function generate(array $params = []): ?string
    {
        try {
            return $this->gateway->clientToken()->generate($params);
        } catch (\Exception $e) {
            $this->logger->critical('Braintree_Exception: ' . $e->getMessage());

            return ['message' => $e->getMessage()];
        }
    }

    /**
     * @param string $nonce
     * @return Error|Successful|array
     */
    public function sale(string $nonce, float $amount)
    {
        try {

            return $this->gateway->transaction()->sale([
                'paymentMethodNonce' => $nonce,
                'amount' => $amount,
                'orderId' => Uuid::uuid4(),
                'options' => [
                    'submitForSettlement' => true
                ]
            ]);
        } catch (\Exception $e) {
            $this->logger->critical('Braintree_Exception: ' . $e->getMessage());

            return ['message' => $e->getMessage()];
        }
    }

    /**
     * @param PaymentReceipt $receipt
     * @return Error|Successful|array
     */
    public function refund(PaymentReceipt $receipt)
    {
        try {
            return $this->gateway->transaction()->refund(
                $receipt->getTransactionId(),
                [
                    'amount' => $receipt->getAmount(),
                    'orderId' => $receipt->getOrderId()
                ]
            );
        } catch (\Exception $e) {
            $this->logger->critical('Braintree_Exception: ' . $e->getMessage());

            return ['message' => $e->getMessage()];
        }
    }

    /**
     * @param string $transactionId
     * @return Error|Successful|array
     */
    public function void(string  $transactionId)
    {
        try {
            return $this->gateway->transaction()->void($transactionId);
        } catch (\Exception $e) {
            $this->logger->critical('Braintree_Exception: ' . $e->getMessage());

            return ['message' => $e->getMessage()];
        }
    }
}

