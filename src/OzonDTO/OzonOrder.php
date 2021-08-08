<?php

namespace Miralexsky\OzonApi\OzonDTO;

class OzonOrder
{
    public $order_api = [];

    public function getOrder(array $order)
    {
        $this->order_api['orderNumber'] = $this->getSendingId();

        array_set($this->order_api, 'buyer', $this->getBuyer());
        array_set($this->order_api, 'recipient', $this->getRecipient());

        array_set($this->order_api, 'firstMileTransfer.type', 'DropOff');
        array_set($this->order_api, 'firstMileTransfer.fromPlaceId', (new OzonApi())->from_place);

        array_set($this->order_api, 'payment', $this->getPayment());
        array_set($this->order_api, 'deliveryInformation', $this->getDeliveryInfo());
        array_set($this->order_api, 'packages', $this->getPackages());
        array_set($this->order_api, 'orderLines', $this->getOrderLines());

        return $this->order_api;
    }

    public function setOrder(array $order)
    {
        $this->order = $order;
    }

    private function getBuyer()
    {
        $buyer = [
            'name'      => 'Шаров Артем Николаевич',
            'phone'     => '83412478800',
            'email'     => 'artem.sharov@tastycoffeesale.ru',
            'type'      => 'LegalPerson',
            'legalName' => 'ООО "Кофе с доставкой"',
        ];

        return $buyer;
    }

    /**
     * @return array
     */
    private function getRecipient(): array
    {
        $names = explode(' ', $this->order['name'] ?? '');
        if (count($names) === 1) {
            array_push($names, 'TastyCoffee');
        }
        $name = implode(' ', $names);

        $recipient = [
            'name'      => $name ?? '',
            'phone'     => $this->order['phone'] ?? '',
            'email'     => $this->order['email'] ?? '',
            'type'      => $this->orderModel->is_legal ? 'LegalPerson' : 'NaturalPerson',
            'legalName' => $this->orderModel->legal_name ?? null,
        ];

        return $recipient;
    }

    /**
     * @return array
     */
    private function getPayment(): array
    {
        $deliveryPrice = $this->orderModel->getShippingPrice();

        $payment = [
            'type'                   => $this->order['is_payment'] ? 'FullPrepayment' : 'Postpay',
            'prepaymentAmount'       => $this->order['is_payment'] ? $this->orderModel->getTotalPrice() : 0,
            'recipientPaymentAmount' => !$this->order['is_payment'] ? $this->orderModel->getTotalPrice() : 0,
            'deliveryPrice'          => $deliveryPrice,
            'deliveryVat'            => [
                'rate' => 0,
                'sum'  => 0,
            ],
        ];
        return $payment;
    }

    /**
     * @return array
     */
    private function getDeliveryInfo(): array
    {
        $pvz_id = $this->orderModel->getPvzId();
        $pvz_id = optional(OzonPvz::find($pvz_id))->pvz_id;

        /*        $method = $this->orderModel->shipping_method;
                $string_with_type = mb_substr($method, mb_strpos($method, 'Россия'));
                $string_with_type = str_replace('(Пункт выдачи)', '', $string_with_type);
                $address = str_replace('(Постамат)', '', $string_with_type);
                $address = trim($address, ' ');

                $pvz = OzonPvz::where('address', 'like', "%$address%")->first();
                $pvz_id = optional($pvz)->pvz_id;*/

        if ($this->orderModel->id === 552562) {
            $pvz_id = optional(OzonPvz::find(40871))->pvz_id;;
        }

        if (!$this->orderModel->isDeliveryToPvz()) {
            $ozonCity = OzonCity::where('region_id', $this->orderModel->shipping_region_id)
                ->where('name', $this->orderModel->shipping_city)->first();

            if ($ozonCity && $ozonCity->id === 250) {
                $ozonCity = OzonPvz::find(1704);
            }

            if ($ozonCity) {
                $pvz = OzonPvz::where('city_id', $ozonCity->id)->where('type', Ozon::TO_DOOR)->first();
                $pvz_id = $pvz ? $pvz->pvz_id : null;
            }
        }

        if (!$pvz_id) {
            throw new \Exception("Не найден ПВЗ $pvz_id");
        }

        $client_address = $this->orderModel->getFullAddressString();
        $client_address = str_replace('Санкт-Петербург, Санкт-Петербург,', 'Санкт-Петербург,', $client_address);
        $client_address = str_replace('Москва, Москва,', 'Москва,', $client_address);

        $delivery = [
            'deliveryVariantId' => (string)$pvz_id,
            'address'           => $this->orderModel->isDeliveryToPvz()
                ? 'Самовывоз'
                : $client_address,
        ];

        // Возможно понадобится в будущем (желаемый интервал доставки)
//        array_set($delivery, 'desiredDeliveryTimeInterval.from', 0);
//        array_set($delivery, 'desiredDeliveryTimeInterval.to', 0);

        return $delivery;
    }

    /**
     * @return array
     */
    private function getPackages(): array
    {
        /**
         * @var BoxSize $size
         */
        $size = $this->orderModel->getSizeBox();
        $weight = (int)$this->orderModel->getWeight();

        $packages = [];
        array_push($packages, [
            'packageNumber' => "1",
            'dimensions'    => [
                'weight' => $weight,
                'length' => $size->getDepth() * 10,
                'height' => $size->getHeight() * 10,
                'width'  => $size->getWidth() * 10,
            ],
        ]);

        return $packages;
    }

    /**
     * @return array
     */
    private function getOrderLines(): array
    {
        $orderLinesApi = [];
        $orderLines = [];
        $orderProducts = $this->orderModel->order_products;

        $products_sum = array_reduce([...$orderProducts], function ($result, $product) {
            return bcadd($result, bcmul($product->price, $product->count, 2), 2);
        });

        if (bccomp($this->orderModel->getPriceProducts(), $products_sum, 2)) {

            $fullDiscount = abs(bcsub($this->orderModel->getPriceProducts(), $products_sum, 2));

        } else {
            $fullDiscount = 0;
            $addFee = 0;
        }

        // созданим объект с ценой, с количеством, со скидкой - всё нужно для orderLines и тут же делаем расчёт
        // опираясь на процентное соотношение цен
        foreach ($orderProducts as $index => $product) {
            $ozonProductLine = new OzonProductLine();
            $ozonProductLine->lineNumber = $index;
            $ozonProductLine->articleNumber = "TK-" . $product->product_id;
            $ozonProductLine->name = $product->product_name;
            $ozonProductLine->weight = $product->weight * 1000;
            $ozonProductLine->quantity = $product->count;

            $share = ceil(bcdiv($product->price * $product->count, $products_sum, 5) * 10000) / 10000;
            $discount = ceil(bcmul($fullDiscount, $share, 5) * 10000) / 10000;

            $ozonProductLine->setPrice($product->price, $discount);
            $orderLines[] = $ozonProductLine;

            $linesSum = ($linesSum ?? 0) + ($ozonProductLine->getPrice() * $ozonProductLine->quantity);
        }

        $addFee = bcsub($this->orderModel->getPriceProducts(), $linesSum ?? 0, 2);

        if ($addFee < 0) {
            $this->subtractionFromDelivery($addFee);
        }

        if ($addFee) {
            foreach ($orderLines as $orderLine) {

                $addFee = $orderLine->addToPrice($addFee);

                if (!$addFee) {
                    break;
                }
            }
        }

        $linesSum = 0;
        foreach ($orderLines as $productLine) {

            $orderLinesApi[] = [
                'lineNumber'       => $productLine->lineNumber,
                'articleNumber'    => $productLine->articleNumber,
                'name'             => $productLine->name,
                'weight'           => $productLine->weight,
                'sellingPrice'     => $productLine->getPrice(),
                'estimatedPrice'   => $productLine->getPrice(),
                'quantity'         => $productLine->quantity,
                /*'vat'                 => [
                    'rate' => $weight,
                    'sum'  => $size->getDepth(),
                ],*/
                'resideInPackages' => ["1"],
            ];

            $linesSum = ($linesSum ?? 0) + ($productLine->getPrice() * $productLine->quantity);
        }

        $addFee = bcsub($this->orderModel->getPriceProducts(), $linesSum ?? 0, 2);

        if ($addFee > 0) {
            $orderLinesApi[] = [
                'lineNumber'       => $productLine->lineNumber + 1,
                'articleNumber'    => "0",
                'name'             => "add-fee",
                'weight'           => 0,
                'sellingPrice'     => abs($addFee),
                'estimatedPrice'   => abs($addFee),
                'quantity'         => 1,
                'resideInPackages' => ['1'],
            ];
        }

        return $orderLinesApi;
    }

    private function calculatePrices()
    {

    }

    public function subtractionFromDelivery($addFee)
    {
        $delivery_price = array_get($this->order_api, 'payment.deliveryPrice');
        $delivery_price = bcadd($addFee, $delivery_price, 2);

        if ($delivery_price < 0) {
            $delivery_price = 0;
        }

        array_set($this->order_api, 'payment.deliveryPrice', (float)$delivery_price);
    }
}