# Omniship Yurtiçi Kargo

Yurtiçi Kargo carrier driver for [Omniship](https://github.com/tcgunel/omniship-common).

Uses the **Giden Kargo** (outbound) SOAP API via `ShippingOrderDispatcherServices` WSDL.

## Installation

```bash
composer require tcgunel/omniship-yurtici
```

## Quick Start

```php
use Omniship\Omniship;
use Omniship\Common\Address;
use Omniship\Common\Package;

$carrier = Omniship::create('Yurtici');
$carrier->initialize([
    'username' => 'YKTEST',     // Test credentials
    'password' => 'YK',
    'userLanguage' => 'TR',     // TR or EN
    'testMode' => true,
]);
```

## Operations

### Create Shipment

```php
$response = $carrier->createShipment([
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
        email: 'mehmet@example.com',
        taxId: '1234567890',
    ),
    'packages' => [
        new Package(weight: 2.5, desi: 3),
    ],
    'description' => 'Elektronik ürün',
    'specialField1' => 'Özel alan 1',
])->send();

if ($response->isSuccessful()) {
    echo $response->getShipmentId();     // jobId
    echo $response->getTrackingNumber(); // cargoKey
}
```

### Cash on Delivery (Kapıda Ödeme)

```php
$response = $carrier->createShipment([
    'cargoKey' => 'COD-001',
    'invoiceKey' => 'INV-COD',
    'cashOnDelivery' => true,
    'codAmount' => 150.50,
    'codCollectionType' => 0,         // Collection type
    'codDocumentId' => 'DOC-001',     // Optional: document reference
    'codDocumentSaveType' => '1',     // Optional: save type
    'shipTo' => new Address(/* ... */),
    'packages' => [new Package(weight: 1.0)],
])->send();
```

### Track Shipment

```php
$response = $carrier->getTrackingStatus([
    'trackingNumber' => '330012345678',
    'keyType' => 0,                // 0 = cargoKey (default)
    'addHistoricalData' => true,   // Include movement history (default: true)
    'onlyTracking' => false,       // Tracking data only (default: false)
])->send();

if ($response->isSuccessful()) {
    $info = $response->getTrackingInfo();
    echo $info->trackingNumber;     // docId (YK tracking number)
    echo $info->status->name;       // DELIVERED, IN_TRANSIT, etc.

    foreach ($info->events as $event) {
        echo $event->occurredAt->format('Y-m-d H:i');
        echo $event->description;   // "Teslim edildi"
        echo $event->location;      // "Ankara Çankaya Şube"
        echo $event->status->name;  // DELIVERED
    }
}
```

### Cancel Shipment

```php
$response = $carrier->cancelShipment([
    'cargoKeys' => 'ORDER-001',
    // or use 'trackingNumber' as fallback
])->send();

if ($response->isSuccessful() && $response->isCancelled()) {
    echo 'Shipment cancelled successfully';
}
```

## API Details

### WSDL Endpoints

| Environment | URL |
|-------------|-----|
| Test | `http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices?wsdl` |
| Production | `https://ws.yurticikargo.com/KOPSWebServices/ShippingOrderDispatcherServices?wsdl` |

### Test Credentials

| Field | Value |
|-------|-------|
| Username | `YKTEST` |
| Password | `YK` |

### SOAP Methods

| Method | Description |
|--------|-------------|
| `createShipment` | Create one or more shipments (ShippingOrderVO) |
| `queryShipment` | Track shipment status (ShippingDeliveryVO) |
| `cancelShipment` | Cancel shipment(s) |

### Tracking Status Codes (operationCode)

| Code | Status | Description |
|------|--------|-------------|
| 0 | PRE_TRANSIT | Kayıt alındı / Henüz işlem yapılmadı |
| 1 | PICKED_UP | Kabul edildi |
| 2 | IN_TRANSIT | Aktarmada / Şubede |
| 3 | CANCELLED | İptal edildi |
| 4 | OUT_FOR_DELIVERY | Dağıtımda |
| 5 | DELIVERED | Teslim edildi |
| 6 | RETURNED | İade edildi |

### Response Format

All responses include:
- `outFlag`: `0` = at least one success, `1` = all errors, `2` = unexpected error
- `outResult`: Human-readable result message
- Detail VO with per-shipment `operationCode` and `operationMessage`

### ShippingOrderVO Fields

Required: `cargoKey`, `invoiceKey`, `receiverCustName`, `receiverAddress`, `receiverPhone1`, `cityName`, `townName`

Optional: `emailAddress`, `taxNumber`, `taxOfficeId`, `taxOfficeName`, `desi`, `kg`, `cargoCount`, `specialField1/2/3`, `description`, `custProdId`, `waybillNo`

COD: `ttInvoiceAmount`, `ttCollectionType`, `ttDocumentId`, `ttDocumentSaveType`

## Notes

- Yurtiçi has two APIs: **Giden Kargo** (outbound, this package) and **Öder Modeli** (inbound/NgiShipment, not implemented)
- The `queryShipment` method uses `wsLanguage` parameter (not `userLanguage`)
- Barcode/tracking number (`docId`) is only available after `queryShipment`, not at creation time
- Batch operations are supported: pass multiple ShippingOrderVO items

## Testing

```bash
vendor/bin/pest
```

## License

MIT
