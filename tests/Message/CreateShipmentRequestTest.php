<?php

declare(strict_types=1);

use Omniship\Common\Address;
use Omniship\Common\Package;
use Omniship\Yurtici\Message\CreateShipmentRequest;
use Omniship\Yurtici\Message\CreateShipmentResponse;

use function Omniship\Yurtici\Tests\createMockSoapClient;
use function Omniship\Yurtici\Tests\createMockSoapClientWithResponse;

beforeEach(function () {
    $this->request = new CreateShipmentRequest(createMockSoapClient());
    $this->request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'userLanguage' => 'TR',
        'cargoKey' => 'ORDER-001',
        'invoiceKey' => 'INV-001',
        'shipTo' => new Address(
            name: 'Mehmet Demir',
            street1: 'Kızılay Mah. 123. Sok. No:5',
            city: 'Ankara',
            district: 'Çankaya',
            postalCode: '06420',
            country: 'TR',
            phone: '05559876543',
        ),
        'packages' => [
            new Package(weight: 2.5, desi: 3),
        ],
    ]);
});

it('builds correct SOAP data', function () {
    $data = $this->request->getData();

    expect($data['wsUserName'])->toBe('YKTEST')
        ->and($data['wsPassword'])->toBe('YK')
        ->and($data['userLanguage'])->toBe('TR')
        ->and($data['ShippingOrderVO'])->toBeArray();

    $order = $data['ShippingOrderVO'];

    expect($order['cargoKey'])->toBe('ORDER-001')
        ->and($order['invoiceKey'])->toBe('INV-001')
        ->and($order['receiverCustName'])->toBe('Mehmet Demir')
        ->and($order['receiverPhone1'])->toBe('05559876543')
        ->and($order['cityName'])->toBe('Ankara')
        ->and($order['townName'])->toBe('Çankaya')
        ->and($order['desi'])->toBe(3.0)
        ->and($order['kg'])->toBe(2.5)
        ->and($order['cargoCount'])->toBe(1);
});

it('builds address from street1', function () {
    $data = $this->request->getData();

    expect($data['ShippingOrderVO']['receiverAddress'])
        ->toBe('Kızılay Mah. 123. Sok. No:5');
});

it('concatenates street1 and street2 for address', function () {
    $this->request->setShipTo(new Address(
        name: 'Test',
        street1: 'Line 1',
        street2: 'Line 2',
        city: 'Istanbul',
        phone: '05551234567',
    ));

    $data = $this->request->getData();

    expect($data['ShippingOrderVO']['receiverAddress'])->toBe('Line 1 Line 2');
});

it('calculates totals from multiple packages', function () {
    $this->request->setPackages([
        new Package(weight: 1.5, desi: 2),
        new Package(weight: 3.0, desi: 4, quantity: 2),
    ]);

    $data = $this->request->getData();
    $order = $data['ShippingOrderVO'];

    expect($order['desi'])->toBe(6.0)
        ->and($order['kg'])->toBe(4.5)
        ->and($order['cargoCount'])->toBe(3);
});

it('includes COD fields when cash on delivery is set', function () {
    $this->request->setCashOnDelivery(true);
    $this->request->setCodAmount(150.50);
    $this->request->setCodCollectionType(0);

    $data = $this->request->getData();
    $order = $data['ShippingOrderVO'];

    expect($order['ttInvoiceAmount'])->toBe(150.50)
        ->and($order['ttCollectionType'])->toBe(0);
});

it('excludes COD fields when not set', function () {
    $data = $this->request->getData();
    $order = $data['ShippingOrderVO'];

    expect($order)->not->toHaveKey('ttInvoiceAmount')
        ->and($order)->not->toHaveKey('ttCollectionType');
});

it('includes email when provided', function () {
    $this->request->setShipTo(new Address(
        name: 'Test',
        street1: 'Address',
        city: 'Istanbul',
        phone: '05551234567',
        email: 'test@example.com',
    ));

    $data = $this->request->getData();

    expect($data['ShippingOrderVO']['emailAddress'])->toBe('test@example.com');
});

it('includes tax number when provided', function () {
    $this->request->setShipTo(new Address(
        name: 'Test Corp',
        street1: 'Address',
        city: 'Istanbul',
        phone: '05551234567',
        taxId: '1234567890',
    ));

    $data = $this->request->getData();

    expect($data['ShippingOrderVO']['taxNumber'])->toBe('1234567890');
});

it('includes special fields when set', function () {
    $this->request->setSpecialField1('value1');
    $this->request->setSpecialField2('value2');
    $this->request->setSpecialField3('value3');

    $data = $this->request->getData();
    $order = $data['ShippingOrderVO'];

    expect($order['specialField1'])->toBe('value1')
        ->and($order['specialField2'])->toBe('value2')
        ->and($order['specialField3'])->toBe('value3');
});

it('includes description when set', function () {
    $this->request->setDescription('Fragile goods');

    $data = $this->request->getData();

    expect($data['ShippingOrderVO']['description'])->toBe('Fragile goods');
});

it('throws when required parameters are missing', function () {
    $request = new CreateShipmentRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CreateShipmentResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'outFlag' => '0',
        'outResult' => 'Success',
        'jobId' => '12345',
        'shippingOrderDetailVO' => (object) [
            'cargoKey' => 'ORDER-001',
            'invoiceKey' => 'INV-001',
            'operationCode' => '0',
            'operationMessage' => 'İşlem başarılı',
        ],
    ]);

    $request = new CreateShipmentRequest($soapClient);
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'userLanguage' => 'TR',
        'cargoKey' => 'ORDER-001',
        'invoiceKey' => 'INV-001',
        'shipTo' => new Address(
            name: 'Test',
            street1: 'Address',
            city: 'Istanbul',
            phone: '05551234567',
        ),
        'packages' => [new Package(weight: 1.0)],
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(CreateShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('ORDER-001')
        ->and($response->getShipmentId())->toBe('12345');
});
