<?php

declare(strict_types=1);

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\Yurtici\Message\GetTrackingStatusResponse;

use function Omniship\Yurtici\Tests\createMockRequest;

it('parses successful delivery response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'TRACK-001',
            'docId' => '3300123456789',
            'operationCode' => '5',
            'operationMessage' => 'Teslim edildi',
            'invDocCargoVOArray' => [
                (object) [
                    'operationCode' => '1',
                    'operationMessage' => 'Kabul edildi',
                    'operationDate' => '2026-02-20T10:00:00',
                    'unitName' => 'İstanbul Kadıköy Şube',
                ],
                (object) [
                    'operationCode' => '2',
                    'operationMessage' => 'Aktarmada',
                    'operationDate' => '2026-02-21T08:00:00',
                    'unitName' => 'Ankara Aktarma',
                ],
                (object) [
                    'operationCode' => '4',
                    'operationMessage' => 'Dağıtımda',
                    'operationDate' => '2026-02-22T09:00:00',
                    'unitName' => 'Ankara Çankaya Şube',
                ],
                (object) [
                    'operationCode' => '5',
                    'operationMessage' => 'Teslim edildi',
                    'operationDate' => '2026-02-22T14:30:00',
                    'unitName' => 'Ankara Çankaya Şube',
                ],
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue();

    $info = $response->getTrackingInfo();

    expect($info->trackingNumber)->toBe('3300123456789')
        ->and($info->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->carrier)->toBe('Yurtiçi Kargo')
        ->and($info->events)->toHaveCount(4);

    expect($info->events[0]->status)->toBe(ShipmentStatus::PICKED_UP)
        ->and($info->events[0]->description)->toBe('Kabul edildi')
        ->and($info->events[0]->location)->toBe('İstanbul Kadıköy Şube');

    expect($info->events[3]->status)->toBe(ShipmentStatus::DELIVERED)
        ->and($info->events[3]->description)->toBe('Teslim edildi');
});

it('parses in-transit response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'TRACK-002',
            'docId' => '3300987654321',
            'operationCode' => '2',
            'operationMessage' => 'Aktarmada',
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::IN_TRANSIT)
        ->and($info->trackingNumber)->toBe('3300987654321')
        ->and($info->events)->toHaveCount(1);
});

it('parses cancelled response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'TRACK-003',
            'operationCode' => '3',
            'operationMessage' => 'İptal edildi',
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::CANCELLED);
});

it('parses returned response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'TRACK-004',
            'operationCode' => '6',
            'operationMessage' => 'İade edildi',
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::RETURNED);
});

it('parses pre-transit response (not yet processed)', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'TRACK-005',
            'operationCode' => '0',
            'operationMessage' => 'Henüz işlem yapılmadı',
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->status)->toBe(ShipmentStatus::PRE_TRANSIT);
});

it('handles nested ShippingDeliveryVO wrapper', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'ShippingDeliveryVO' => (object) [
            'outFlag' => '0',
            'outResult' => 'OK',
            'shippingDeliveryDetailVO' => (object) [
                'cargoKey' => 'WRAP-001',
                'docId' => '3300111222333',
                'operationCode' => '5',
                'operationMessage' => 'Teslim edildi',
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue();
    $info = $response->getTrackingInfo();
    expect($info->trackingNumber)->toBe('3300111222333')
        ->and($info->status)->toBe(ShipmentStatus::DELIVERED);
});

it('parses error response', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '1',
        'outResult' => 'Kargo bulunamadı',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('Kargo bulunamadı');
});

it('maps all known operation codes', function () {
    expect(GetTrackingStatusResponse::mapStatus(0))->toBe(ShipmentStatus::PRE_TRANSIT)
        ->and(GetTrackingStatusResponse::mapStatus(1))->toBe(ShipmentStatus::PICKED_UP)
        ->and(GetTrackingStatusResponse::mapStatus(2))->toBe(ShipmentStatus::IN_TRANSIT)
        ->and(GetTrackingStatusResponse::mapStatus(3))->toBe(ShipmentStatus::CANCELLED)
        ->and(GetTrackingStatusResponse::mapStatus(4))->toBe(ShipmentStatus::OUT_FOR_DELIVERY)
        ->and(GetTrackingStatusResponse::mapStatus(5))->toBe(ShipmentStatus::DELIVERED)
        ->and(GetTrackingStatusResponse::mapStatus(6))->toBe(ShipmentStatus::RETURNED)
        ->and(GetTrackingStatusResponse::mapStatus(99))->toBe(ShipmentStatus::UNKNOWN);
});

it('uses cargoKey as tracking number when docId is missing', function () {
    $response = new GetTrackingStatusResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingDeliveryDetailVO' => (object) [
            'cargoKey' => 'CARGO-ONLY',
            'operationCode' => '1',
            'operationMessage' => 'Kabul edildi',
        ],
    ]);

    $info = $response->getTrackingInfo();

    expect($info->trackingNumber)->toBe('CARGO-ONLY');
});
