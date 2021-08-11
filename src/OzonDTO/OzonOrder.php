<?php

namespace Miralexsky\OzonApi\OzonDTO;

class OzonOrder
{
    public $order_api = [];

    public $sendingId;
    public $buyer;
    public $recipient;
    public $fromPlaceId;
    public $payment;
    public $deliveryInfo;
    public $packages;
    public $orderLines;

    public function getOrderForApi()
    {
        $this->order_api['orderNumber'] = $this->sendingId;

        $this->order_api['buyer'] = $this->getBuyer();
        $this->order_api['recipient'] = $this->getRecipient();

        $this->order_api['firstMileTransfer'] = [
            'type' => 'DropOff',
            'fromPlaceId' => ''
        ];

        $this->order_api['payment'] = $this->getPayment();
        $this->order_api['deliveryInformation'] = $this->getDeliveryInfo();
        $this->order_api['packages'] = $this->getPackages();
        $this->order_api['orderLines'] = $this->getOrderLines();

        return $this->order_api;
    }

    public function setOrder(array $order)
    {
        $this->order = $order;
    }

    private function getBuyer()
    {
        return $this->buyer;
    }

    /**
     * @return array
     */
    private function getRecipient()
    {
        return $this->recipient;
    }

    /**
     * @return array
     */
    private function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return array
     */
    private function getDeliveryInfo()
    {
        return $this->deliveryInfo;
    }

    /**
     * @return array
     */
    private function getPackages()
    {
        return $this->packages;
    }

    /**
     * @return array|null
     */
    private function getOrderLines()
    {
        foreach ($this->orderLines as $productLine) {

            $orderLinesApi[] = [
                'lineNumber'       => $productLine->lineNumber,
                'articleNumber'    => $productLine->articleNumber,
                'name'             => $productLine->name,
                'weight'           => $productLine->weight,
                'sellingPrice'     => $productLine->price,
                'estimatedPrice'   => $productLine->price,
                'quantity'         => $productLine->quantity,
                'resideInPackages' => ["1"],
            ];
        }

        return isset($orderLinesApi) ? $orderLinesApi : null;
    }
}