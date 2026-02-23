<?php

declare(strict_types=1);

use Omniship\Yurtici\Message\CreateShipmentResponse;

use function Omniship\Yurtici\Tests\createMockRequest;

it('parses successful response', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'İşlem başarılı',
        'jobId' => '98765',
        'shippingOrderDetailVO' => (object) [
            'cargoKey' => 'CARGO-001',
            'invoiceKey' => 'INV-001',
            'operationCode' => '0',
            'operationMessage' => 'Kayıt oluşturuldu',
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('CARGO-001')
        ->and($response->getShipmentId())->toBe('98765')
        ->and($response->getBarcode())->toBe('0')
        ->and($response->getLabel())->toBeNull()
        ->and($response->getTotalCharge())->toBeNull()
        ->and($response->getCurrency())->toBeNull();
});

it('parses nested ShippingOrderResultVO wrapper', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'ShippingOrderResultVO' => (object) [
            'outFlag' => '0',
            'outResult' => 'OK',
            'jobId' => '11111',
            'shippingOrderDetailVO' => (object) [
                'cargoKey' => 'NESTED-001',
                'invoiceKey' => 'INV-N01',
                'operationCode' => '0',
                'operationMessage' => 'Başarılı',
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('NESTED-001')
        ->and($response->getShipmentId())->toBe('11111');
});

it('parses error response', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '1',
        'outResult' => 'Hata oluştu',
        'shippingOrderDetailVO' => (object) [
            'cargoKey' => 'ERR-001',
            'invoiceKey' => 'INV-E01',
            'errCode' => '100',
            'errMessage' => 'Alıcı telefon numarası geçersiz',
        ],
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('Alıcı telefon numarası geçersiz');
});

it('handles batch response with array of details', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '0',
        'outResult' => 'OK',
        'jobId' => '22222',
        'shippingOrderDetailVO' => [
            (object) [
                'cargoKey' => 'BATCH-001',
                'invoiceKey' => 'INV-B01',
                'operationCode' => '0',
                'operationMessage' => 'OK',
            ],
            (object) [
                'cargoKey' => 'BATCH-002',
                'invoiceKey' => 'INV-B02',
                'operationCode' => '0',
                'operationMessage' => 'OK',
            ],
        ],
    ]);

    expect($response->isSuccessful())->toBeTrue()
        ->and($response->getTrackingNumber())->toBe('BATCH-001');
});

it('returns outResult as message when no detail errMessage', function () {
    $response = new CreateShipmentResponse(createMockRequest(), (object) [
        'outFlag' => '1',
        'outResult' => 'Genel hata mesajı',
    ]);

    expect($response->isSuccessful())->toBeFalse()
        ->and($response->getMessage())->toBe('Genel hata mesajı');
});

it('returns raw data', function () {
    $data = (object) ['outFlag' => '0', 'outResult' => 'OK'];
    $response = new CreateShipmentResponse(createMockRequest(), $data);

    expect($response->getData())->toBe($data);
});
