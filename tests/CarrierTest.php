<?php

declare(strict_types=1);

use Omniship\Yurtici\Carrier;
use Omniship\Yurtici\Message\CancelShipmentRequest;
use Omniship\Yurtici\Message\CreateShipmentRequest;
use Omniship\Yurtici\Message\GetTrackingStatusRequest;

use function Omniship\Yurtici\Tests\createMockSoapClient;

beforeEach(function () {
    $this->carrier = new Carrier(createMockSoapClient());
    $this->carrier->initialize([
        'username' => 'YKTEST',
        'password' => 'YK',
        'testMode' => true,
    ]);
});

it('has the correct name', function () {
    expect($this->carrier->getName())->toBe('Yurtiçi Kargo');
    expect($this->carrier->getShortName())->toBe('Yurtici');
});

it('has correct default parameters', function () {
    $carrier = new Carrier(createMockSoapClient());
    $carrier->initialize();

    expect($carrier->getUsername())->toBe('')
        ->and($carrier->getPassword())->toBe('')
        ->and($carrier->getUserLanguage())->toBe('TR')
        ->and($carrier->getTestMode())->toBeFalse();
});

it('initializes with custom parameters', function () {
    expect($this->carrier->getUsername())->toBe('YKTEST')
        ->and($this->carrier->getPassword())->toBe('YK')
        ->and($this->carrier->getTestMode())->toBeTrue();
});

it('returns test WSDL URL in test mode', function () {
    $reflection = new ReflectionMethod($this->carrier, 'getWsdlUrl');

    expect($reflection->invoke($this->carrier))
        ->toContain('testwebservices.yurticikargo.com');
});

it('returns production WSDL URL in production mode', function () {
    $this->carrier->setTestMode(false);
    $reflection = new ReflectionMethod($this->carrier, 'getWsdlUrl');

    expect($reflection->invoke($this->carrier))
        ->toContain('ws.yurticikargo.com');
});

it('supports createShipment method', function () {
    expect($this->carrier->supports('createShipment'))->toBeTrue();
});

it('supports getTrackingStatus method', function () {
    expect($this->carrier->supports('getTrackingStatus'))->toBeTrue();
});

it('supports cancelShipment method', function () {
    expect($this->carrier->supports('cancelShipment'))->toBeTrue();
});

it('creates a CreateShipmentRequest', function () {
    $request = $this->carrier->createShipment([
        'cargoKey' => 'TEST123',
        'invoiceKey' => 'INV123',
    ]);

    expect($request)->toBeInstanceOf(CreateShipmentRequest::class);
});

it('creates a GetTrackingStatusRequest', function () {
    $request = $this->carrier->getTrackingStatus([
        'trackingNumber' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(GetTrackingStatusRequest::class);
});

it('creates a CancelShipmentRequest', function () {
    $request = $this->carrier->cancelShipment([
        'cargoKeys' => 'TEST123',
    ]);

    expect($request)->toBeInstanceOf(CancelShipmentRequest::class);
});

it('sets and gets userLanguage', function () {
    $this->carrier->setUserLanguage('EN');

    expect($this->carrier->getUserLanguage())->toBe('EN');
});
