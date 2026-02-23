<?php

declare(strict_types=1);

use Omniship\Yurtici\Message\GetTrackingStatusRequest;
use Omniship\Yurtici\Message\GetTrackingStatusResponse;

use function Omniship\Yurtici\Tests\createMockSoapClient;
use function Omniship\Yurtici\Tests\createMockSoapClientWithResponse;

it('builds correct SOAP data for tracking', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'userLanguage' => 'TR',
        'trackingNumber' => 'TRACK-123',
    ]);

    $data = $request->getData();

    expect($data['wsUserName'])->toBe('YKTEST')
        ->and($data['wsPassword'])->toBe('YK')
        ->and($data['wsLanguage'])->toBe('TR')
        ->and($data['keys'])->toBe('TRACK-123')
        ->and($data['keyType'])->toBe(0)
        ->and($data['addHistoricalData'])->toBeTrue()
        ->and($data['onlyTracking'])->toBeFalse();
});

it('uses wsLanguage not userLanguage for queryShipment', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'userLanguage' => 'EN',
        'trackingNumber' => 'TRACK-123',
    ]);

    $data = $request->getData();

    expect($data)->toHaveKey('wsLanguage')
        ->and($data)->not->toHaveKey('userLanguage')
        ->and($data['wsLanguage'])->toBe('EN');
});

it('allows custom keyType', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'trackingNumber' => 'TRACK-123',
        'keyType' => 1,
    ]);

    $data = $request->getData();

    expect($data['keyType'])->toBe(1);
});

it('allows disabling historical data', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'trackingNumber' => 'TRACK-123',
        'addHistoricalData' => false,
    ]);

    $data = $request->getData();

    expect($data['addHistoricalData'])->toBeFalse();
});

it('throws when tracking number is missing', function () {
    $request = new GetTrackingStatusRequest(createMockSoapClient());
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
    ]);

    $request->getData();
})->throws(\Omniship\Common\Exception\InvalidRequestException::class);

it('sends and returns GetTrackingStatusResponse', function () {
    $soapClient = createMockSoapClientWithResponse((object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'TRACK-123',
            'docId' => '3300123456789',
            'operationCode' => '5',
            'operationMessage' => 'Teslim edildi',
        ],
    ]);

    $request = new GetTrackingStatusRequest($soapClient);
    $request->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'trackingNumber' => 'TRACK-123',
    ]);

    $response = $request->send();

    expect($response)->toBeInstanceOf(GetTrackingStatusResponse::class)
        ->and($response->isSuccessful())->toBeTrue();
});
