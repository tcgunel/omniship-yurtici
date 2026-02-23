<?php

declare(strict_types=1);

use Omniship\Yurtici\Message\CancelShipmentRequest;
use Omniship\Yurtici\Message\CancelShipmentResponse;

use function Omniship\Yurtici\Tests\createMockSoapClient;
use function Omniship\Yurtici\Tests\createMockSoapClientWithResponse;

it('builds correct SOAP data for cancel', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'userLanguage' => 'TR',
        'cargoKeys' => 'CARGO-001',
    ]);

    $data = $request->getData();

    expect($data['wsUserName'])->toBe('YKTEST')
        ->and($data['wsPassword'])->toBe('YK')
        ->and($data['userLanguage'])->toBe('TR')
        ->and($data['cargoKeys'])->toBe('CARGO-001');
});

it('uses trackingNumber as fallback for cargoKeys', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'trackingNumber' => 'TRACK-FALLBACK',
    ]);

    $data = $request->getData();

    expect($data['cargoKeys'])->toBe('TRACK-FALLBACK');
});

it('throws when both cargoKeys and trackingNumber are missing', function () {
    $request = new CancelShipmentRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns CancelShipmentResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingCancelDetailVO' => (object) [
            'cargoKey' => 'CARGO-001',
            'operationCode' => '0',
            'operationMessage' => 'İptal edildi',
        ],
    ]);

    $request = new CancelShipmentRequest($soapClient);
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'cargoKeys' => 'CARGO-001',
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(CancelShipmentResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
