<?php namespace professionalweb\payment\drivers\prodamus;

use professionalweb\payment\drivers\receipt\ReceiptItem as IReceiptItem;

/**
 * Receipt item
 * @package professionalweb\payment\drivers\prodamus
 */
class ReceiptItem extends IReceiptItem
{

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'     => mb_substr($this->getName(), 0, 128),
            'price'    => $this->getPrice(),
            'quantity' => $this->getQty(),
            'sku'      => '',
        ];
    }
}