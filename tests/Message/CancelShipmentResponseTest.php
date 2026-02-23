<?php

declare(strict_types=1);

use Omniship\Yurtici\Message\CancelShipmentResponse;

use function Omniship\Yurtici\Tests\createMockRequest;

it('parses successful cancel response', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingCancelDetailVO' => (object) [
            'cargoKey' => 'CANCEL-001',
            'operationCode' => '0',
            'operationMessage' => 'İptal işlemi başarılı',
            'operationStatus' => 'OK',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue()
        ->and($response->getMessage())->toBe('İptal işlemi başarılı')
        ->and($response->getCode())->toBe('0');
});

it('parses failed cancel response (outFlag error)', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '1',
        'outResult' => 'İptal işlemi yapılamadı',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->isCancelled())->toBeFalse()
        ->and($response->getMessage())->toBe('İptal işlemi yapılamadı');
});

it('handles nested ShippingOrderResultVO wrapper', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'ShippingOrderResultVO' => (object) [
            'outFlag' => '0',
            'outResult' => 'OK',
            'shippingCancelDetailVO' => (object) [
                'cargoKey' => 'WRAP-CANCEL',
                'operationCode' => '0',
                'operationMessage' => 'Başarılı',
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue();
});

it('handles already shipped cancel attempt', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingCancelDetailVO' => (object) [
            'cargoKey' => 'SHIPPED-001',
            'operationCode' => '1',
            'operationMessage' => 'Kargo zaten taşıma aşamasında, iptal edilemez',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeFalse()
        ->and($response->getMessage())->toBe('Kargo zaten taşıma aşamasında, iptal edilemez');
});

it('handles batch cancel with array of details', function () {
    $response = new CancelShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'shippingCancelDetailVO' => [
            (object) [
                'cargoKey' => 'BATCH-C01',
                'operationCode' => '0',
                'operationMessage' => 'İptal edildi',
            ],
            (object) [
                'cargoKey' => 'BATCH-C02',
                'operationCode' => '0',
                'operationMessage' => 'İptal edildi',
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->isCancelled())->toBeTrue();
});

it('returns raw data', function () {
    $data = (object) ['outFlag' => '0', 'outResult' => 'OK'];
    $response = new CancelShipmentResponse(createMockRequest(), $data);

    expect($response->getData())->toBe($data);
});
