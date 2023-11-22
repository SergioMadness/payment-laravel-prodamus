<?php namespace professionalweb\payment\drivers\prodamus;

use professionalweb\payment\drivers\receipt\Receipt as IReceipt;

/**
 * Receipt
 * @package professionalweb\payment\drivers\prodamus
 */
class Receipt extends IReceipt
{

    /**
     * Phone number
     *
     * @var string
     */
    private $email;

    /**
     * Receipt constructor.
     *
     * @param string|null $phone
     * @param string|null $email
     * @param array $items
     * @param string|null $taxSystem
     */
    public function __construct(?string $phone = null, ?string $email = null, array $items = [], ?string $taxSystem = null)
    {
        parent::__construct($phone ?? $email, $items, $taxSystem);

        $this->setEmail($email);
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param $email
     *
     * @return $this
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Receipt to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $prods = [];

        foreach ($this->getItems() as $item) {
            $prods[] = $item->toArray();
        }

        return $prods;
    }

    /**
     * Receipt to json
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }
}