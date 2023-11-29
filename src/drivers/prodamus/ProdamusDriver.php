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
        $linktoform = $this->config['domain'];

        // Секретный ключ. Можно найти на странице настроек,
        // в личном кабинете платежной формы.
        $secret_key = $this->config['secret'];

        $data = [
            // хххх - номер заказ в системе интернет-магазина
            'order_id'        => $orderId,

            // +7хххххххххх - мобильный телефон клиента
            'customer_phone'  => $extraParams['phone'] ?? '',

            // ИМЯ@prodamus.ru - e-mail адрес клиента
            'customer_email'  => $extraParams['email'] ?? '',

            // перечень товаров заказа
            'products'        => $receipt->toArray(),

            // дополнительные данные
            'customer_extra'  => $description,

            // для интернет-магазинов доступно только действие "Оплата"
            'do'              => 'link',

            // url-адрес для возврата пользователя без оплаты
            //           (при необходимости прописать свой адрес)
            'urlReturn'       => $failReturnUrl,

            // url-адрес для возврата пользователя при успешной оплате
            //           (при необходимости прописать свой адрес)
            'urlSuccess'      => $successReturnUrl,

            // код системы интернет-магазина, запросить у поддержки,
            //     для самописных систем можно оставлять пустым полем
            //     (при необходимости прописать свой код)
            'sys'             => $this->config['sys'],

            // служебный url-адрес для уведомления интернет-магазина
            //           о поступлении оплаты по заказу
            // 	         пока реализован только для Advantshop,
            //           формат данных настроен под систему интернет-магазина
            //           (при необходимости прописать свой адрес)
            'urlNotification' => $this->config['notificationUrl'],
        ];


        $data['signature'] = Hmac::create($data, $secret_key);

        $link = sprintf('%s?%s', $linktoform, http_build_query($data));

        return file_get_contents($link);
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
     * @param Receipt|null $receipt
     *
     * @return Form
     * @throws \Exception
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
        return Hmac::verify($data, $this->config['secret'], apache_request_headers()['Sign'] ?? '');
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
        return $this->response['order_num'] ?? '';
    }

    /**
     * Get payment id
     *
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->response['payment_id'] ?? '';
    }

    /**
     * Get operation status
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->response['payment_status'];
    }

    /**
     * Is payment succeed
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->getStatus() === 'success';
    }

    /**
     * Get transaction ID
     *
     * @return string
     */
    public function getTransactionId(): string
    {
        return $this->response['order_id'] ?? '';
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
        return $this->response['date'] ?? '';
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
        return $this->response['customer_email'] ?? '';
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
            $result->setContent('OK ');
        } else {
            $result->setContent('NOT OK')->setStatusCode(400);
        }

        return $result;
    }

    /**
     * Prepare response on check request
     *
     * @param int|null $errorCode
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
