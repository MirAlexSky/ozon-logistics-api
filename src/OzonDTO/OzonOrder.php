<?php

namespace Miralexsky\OzonApi\OzonDTO;

class OzonOrder
{
    const PERSON_TYPE_LEGAL = 'LegalPerson';
    const PERSON_TYPE_NATURAL = 'NaturalPerson';

    const PAYMENT_TYPE_PRE = 'FullPrepayment';
    const PAYMENT_TYPE_POST = 'Postpay';

    const ADDRESS_TYPE_PVZ = 'Самовывоз';

    const PATTERN_PACKAGE = [
        'dimensions' => [
            'weight' => 'required',
            'length' => 'required',
            'height' => 'required',
            'width'  => 'required',
        ],
    ];

    const PATTERN_RECIPIENT = [
        'name'      => 'require',
        'phone'     => 'require',
        'email'     => 'require',
        'type'      => 'require',
        'legalName' => 'when:type=',
    ];

    public $order = [
        'orderNumber' => null,

        'buyer' => [
            'name'      => null,
            'phone'     => null,
            'email'     => null,
            'type'      => self::PERSON_TYPE_LEGAL,
            'legalName' => null,
        ],

        'recipient' => [],

        'payment' => [
            'type'                   => self::PAYMENT_TYPE_POST,
            'prepaymentAmount'       => null,
            'recipientPaymentAmount' => null,
            'deliveryPrice'          => null,
            'deliveryVat'            => [
                'rate' => 0,
                'sum'  => 0,
            ],
        ],

        'deliveryInformation' => [
            'deliveryVariantId' => null,
            'address'           => self::ADDRESS_TYPE_PVZ,
        ],

        'packages' => [],

        'firstMileTransfer' => [
            'type'        => 'DropOff',
            'fromPlaceId' => null,
        ],

        'orderLines'        => [],
    ];

    public function addProduct($product)
    {
        $this->validate([], $product);

        $this->order['orderLines'][] = [
            'lineNumber'       => $product['lineNumber'],
            'articleNumber'    => $product['articleNumber'],
            'name'             => $product['name'],
            'weight'           => $product['weight'],
            'sellingPrice'     => $product['sellingPrice'],
            'estimatedPrice'   => $product['estimatedPrice'],
            'quantity'         => $product['quantity'],
            'resideInPackages' => $product['resideInPackages'],
        ];
    }

    public function addRecipient($recipient)
    {
        $this->validate(static::PATTERN_RECIPIENT, $recipient);

        $this->order['recipient'] = [
            'name'      => null,
            'phone'     => null,
            'email'     => null,
            'type'      => self::PERSON_TYPE_NATURAL,
            'legalName' => null,
        ];
    }

    public function addPackage($package)
    {
        $this->validate(static::PATTERN_PACKAGE, $package);

        $this->order['packages'][] = [
            'packageNumber' => count($this->order['packages']) + 1,
            'dimensions'    => [
                'weight' => $package['weight'],
                'length' => $package['length'],
                'height' => $package['height'],
                'width'  => $package['width'],
            ],
        ];
    }

    private function validate($pattern, $data)
    {
        return;
    }
}