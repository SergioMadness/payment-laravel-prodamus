<?php namespace professionalweb\payment\drivers\prodamus;

use Illuminate\Http\Response;
use professionalweb\payment\contracts\Form;
use professionalweb\payment\contracts\Receipt;
use professionalweb\payment\contracts\PayService;
use professionalweb\payment\contracts\PayProtocol;
use professionalweb\payment\models\PayServiceOption;
use professionalweb\payment\interfaces\ProdamusService;

class ProdamusDriver implements PayService, ProdamusService
{
    /**
     * Module config
     *
     * @var array
     */
    private $config;

    /**
     * @var array
     */
    private $response;

    public function __construct(array $config = [])
    {
        $this->setConfig($config);
    }

    /**
     * Get name of payment service
     *
     * @return string
     */
    public function getName(): string
    {
        return self::PAYMENT_PRODAMUS;
    }

    /**
     * Pay
     *
     * @param mixed $orderId
     * @param mixed $paymentId
     * @param float $amount
     * @param string $currency
     * @param string $paymentType
     * @param string $successReturnUrl
     * @param string $failReturnUrl
     * @param string $description
     * @param array $extraParams
     * @param Receipt|null $receipt
     *
     * @return string
     * @throws \Exception
     */
    public function getPaymentLink($orderId, $paymentId, float $amount, string $currency = self::CURRENCY_RUR, string $paymentType = self::PAYMENT_TYPE_CARD, string $successReturnUrl = '', string $failReturnUrl = '', string $description = '', array $extraParams = [], Receipt $receipt = null): string
    {
        $base64 = base64_encode($this->config['login'] . ':' . $this->config['password']);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . $base64,
        ];

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->config['apiUrl'] . '/info/settings/token/');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);

        // Инициируем запрос к API
        $response = curl_exec($curl);
        $tokenResponse = json_decode($response, true);

        if (!isset($tokenResponse['token'])) {
            throw new \Exception();
        }

        $paymentParameters = [
            'clientid'     => $extraParams['customerName'] ?? $extraParams['user_id'],
            'client_email' => $extraParams['email'],
            'orderid'      => $orderId,
            'pay_amount'   => $amount,
            'client_phone' => $extraParams['phone'] ?? '',
            'cart'         => json_encode($receipt->toArray()),
            'token'        => $tokenResponse['token'],
        ];
        $request = http_build_query($paymentParameters);

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_URL, $this->config['apiUrl'] . '/change/invoice/preview/');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        $response = json_decode(curl_exec($curl), true);
        if (!isset($response['invoice_id'])) {
            throw new \Exception();
        }

        return $response['invoice_url'];
    }

    /**
     * Payment system need form
     * You can not get url for redirect
     *
     * @return bool
     */
    public function needForm(): bool
    {
        return false;
    }

    /**
     * Generate payment form
     *
     * @param mixed $orderId
     * @param mixed $paymentId
     * @param float $amount
     * @param string $currency
     * @param string $paymentType
     * @param string $successReturnUrl
     * @param string $failReturnUrl
     * @param string $description
     * @param array $extraParams
     * @param Receipt $receipt
     *
     * @return Form
     */
    public function getPaymentForm($orderId, $paymentId, float $amount, string $currency = self::CURRENCY_RUR, string $paymentType = self::PAYMENT_TYPE_CARD, string $successReturnUrl = '', string $failReturnUrl = '', string $description = '', array $extraParams = [], Receipt $receipt = null): Form
    {
        throw new \Exception();
    }

    /**
     * Validate request
     *
     * @param array $data
     *
     * @return bool
     */
    public function validate(array $data): bool
    {
        return md5($data['id'] . $data['sum'] . $data['clientid'] . $data['orderid'] . $this->config['secret']) === $data['key'];
    }

    /**
     * Parse notification
     *
     * @param array $data
     *
     * @return $this
     */
    public function setResponse(array $data): PayService
    {
        $this->response = $data;

        return $this;
    }

    /**
     * Get order ID
     *
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->response['orderid'] ?? '';
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->response['paymentid'] ?? '';
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return 'success';
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return true;
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->response['RRN'] ?? '';
    }

    /**
     * Get transaction amount
     *
     * @return float
     */
    public function getAmount(): float
    {
        return $this->response['sum'] ?? '';
    }

    /**
     * Get error code
     *
     * @return string
     */
    public function getErrorCode(): string
    {
        return '';
    }

    /**
     * Get payment provider
     *
     * @return string
     */
    public function getProvider(): string
    {
        return self::PAYMENT_PRODAMUS;
    }

    /**
     * Get PAN
     *
     * @return string
     */
    public function getPan(): string
    {
        return $this->response['card_number'] ?? '';
    }

    /**
     * Get payment datetime
     *
     * @return string
     */
    public function getDateTime(): string
    {
        return $this->response['obtain_datetime'] ?? '';
    }

    /**
     * Get payment currency
     *
     * @return string
     */
    public function getCurrency(): string
    {
        return 'RUB';
    }

    /**
     * Get card type. Visa, MC etc
     *
     * @return string
     */
    public function getCardType(): string
    {
        return '';
    }

    /**
     * Get card expiration date
     *
     * @return string
     */
    public function getCardExpDate(): string
    {
        return $this->response['card_expiry'] ?? '';
    }

    /**
     * Get cardholder name
     *
     * @return string
     */
    public function getCardUserName(): string
    {
        return $this->response['card_holder'] ?? '';
    }

    /**
     * Get card issuer
     *
     * @return string
     */
    public function getIssuer(): string
    {
        return $this->response['bank_id'] ?? '';
    }

    /**
     * Get e-mail
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->response['client_email'] ?? '';
    }

    /**
     * Get payment type. 'GooglePay' for example
     *
     * @return string
     */
    public function getPaymentType(): string
    {
        return '';
    }

    /**
     * Set transport/protocol wrapper
     *
     * @param PayProtocol $protocol
     *
     * @return $this
     */
    public function setTransport(PayProtocol $protocol): PayService
    {
        // TODO: Implement setTransport() method.
    }

    /**
     * Prepare response on notification request
     *
     * @param int $errorCode
     *
     * @return Response
     */
    public function getNotificationResponse(int $errorCode = null): Response
    {
        $result = new Response();

        if ($errorCode === self::RESPONSE_SUCCESS) {
            $result->setContent('OK ' . md5($this->getParam('id') . $this->config['secret']));
        } else {
            $result->setContent('NOT OK')->setStatusCode(400);
        }

        return $result;
    }

    /**
     * Prepare response on check request
     *
     * @param int $errorCode
     *
     * @return Response
     */
    public function getCheckResponse(int $errorCode = null): Response
    {
        return $this->getNotificationResponse($errorCode);
    }

    /**
     * Get last error code
     *
     * @return int
     */
    public function getLastError(): int
    {
        return 0;
    }

    /**
     * Get param by name
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParam(string $name)
    {
        return $this->response[$name] ?? null;
    }

    /**
     * Get pay service options
     *
     * @return array
     */
    public static function getOptions(): array
    {
        return [
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Домен')->setAlias('domain'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Код')->setAlias('sys'),
            (new PayServiceOption())->setType(PayServiceOption::TYPE_STRING)->setLabel('Секретный код')->setAlias('secret'),
        ];
    }

    /**
     * Set driver configuration
     *
     * @param array $config
     *
     * @return $this
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }
}
