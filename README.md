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

> **Note:** Payment type is not sent as a parameter — different users (credentials) are assigned to different payment types (Sender Pays, Receiver Pays, COD Cash, COD Credit Card). Your integration must support multiple credentials.

## Operations

### Create Shipment

Two working modes are supported:

#### Invoice-Based (Fatura Bazlı)

One record per shipment. Each `cargoKey` = one shipment. YK branch handles piece count and desi manually.

```php
$response = $carrier->createShipment([
    'cargoKey' => 'ORDER-001',          // Required, unique, YK branch scans this barcode
    'invoiceKey' => 'INV-001',          // Required, unique per shipment
    'shipTo' => new Address(
        name: 'Mehmet Yılmaz',          // Min 5 chars, min 4 letters
        street1: 'Eski Büyükdere Cad. No:3',  // Min 5 chars, max 200. Do NOT include city/district here
        city: 'Istanbul',
        district: 'Maslak',
        phone: '02123652426',           // 10 digits with area code
        email: 'mehmet@example.com',
    ),
    'packages' => [
        new Package(weight: 2.5, desi: 3),
    ],
    'description' => 'Elektronik ürün',
    'waybillNo' => 'IRN-001',          // Waybill number (required for commercial shipments)
    'specialField1' => '1$134096$',    // Custom fields (see Special Fields section)
])->send();

if ($response->isSuccessful()) {
    echo $response->getShipmentId();     // jobId
    echo $response->getTrackingNumber(); // cargoKey
}
```

#### Package-Based (Kargo Bazlı)

For multi-piece shipments. Each piece gets a unique `cargoKey`, all sharing the same `invoiceKey`.

```php
// 3-piece shipment: each piece has unique cargoKey, same invoiceKey
$response = $carrier->createShipment([
    // Piece 1
    ['cargoKey' => '10012', 'invoiceKey' => 'A123456', 'cargoCount' => 3,
     'shipTo' => $address, 'waybillNo' => 'A123456'],
    // Piece 2
    ['cargoKey' => '10013', 'invoiceKey' => 'A123456', 'cargoCount' => 3,
     'shipTo' => $address, 'waybillNo' => 'A123456'],
    // Piece 3
    ['cargoKey' => '10014', 'invoiceKey' => 'A123456', 'cargoCount' => 3,
     'shipTo' => $address, 'waybillNo' => 'A123456'],
])->send();
```

> YK branch scans each piece barcode individually, printing a YK barcode for each. The shipping document is generated after the last piece is scanned.

### Cash on Delivery (Tahsilatlı Teslimat)

#### Cash COD (Nakit)

```php
$response = $carrier->createShipment([
    'cargoKey' => 'COD-001',
    'invoiceKey' => 'INV-COD',
    'shipTo' => $address,
    'packages' => [new Package(weight: 1.0)],
    'cashOnDelivery' => true,
    'codAmount' => 45.35,                // ttInvoiceAmount - separator must be "."
    'codCollectionType' => '0',          // 0 = Cash
    'codDocumentId' => '5146846',        // ttDocumentId - invoice number
    'codDocumentSaveType' => '0',        // 0 = Same invoice, 1 = Separate invoice
])->send();
```

#### Credit Card COD (Kredi Kartı)

```php
$response = $carrier->createShipment([
    'cargoKey' => 'COD-CC-001',
    'invoiceKey' => 'INV-CC',
    'shipTo' => $address,
    'packages' => [new Package(weight: 1.0)],
    'cashOnDelivery' => true,
    'codAmount' => 45.35,
    'codCollectionType' => '1',          // 1 = Credit Card
    'codDocumentId' => '5146846',
    'codDocumentSaveType' => '0',
    'dcSelectedCredit' => 5,             // Installment count
    'dcCreditRule' => 1,                 // 0 = Customer choice required, 1 = Allow single payment
])->send();
```

### Track Shipment (queryShipment)

Query shipment status and movement history. Rate limited: repeated queries within 1 minute are blocked.

```php
$response = $carrier->getTrackingStatus([
    'trackingNumber' => 'ORDER-001',
    'keyType' => 0,                // 0 = cargoKey (default), 1 = invoiceKey
    'addHistoricalData' => true,   // Include transport movement history
    'onlyTracking' => false,       // Only return tracking link
])->send();

if ($response->isSuccessful()) {
    $info = $response->getTrackingInfo();
    echo $info->trackingNumber;     // docId (YK shipment number)
    echo $info->status->name;       // DELIVERED, IN_TRANSIT, etc.

    foreach ($info->events as $event) {
        echo $event->occurredAt->format('Y-m-d H:i');
        echo $event->description;   // "Kargo Indirildi"
        echo $event->location;      // "AFSIN IRTIBAT"
        echo $event->status->name;
    }
}
```

#### queryShipment Response Fields

The response includes detailed shipment information:

| Field | Description |
|-------|-------------|
| `docid` | YK shipment number |
| `operationCode/Status` | Current status (see status codes below) |
| `deliveryDate/Time` | Delivery date/time (YYYYMMDD / HHMMSS) |
| `totalDesi` | Total desi |
| `totalKg` | Total weight |
| `totalAmount` | Total charge |
| `totalPrice` | Transport fee |
| `totalVat` | VAT amount |
| `trackingUrl` | Self-service tracking link |
| `receiverInfo` | Delivery confirmation info |
| `rejectStatus` | Return status (see below) |
| `returnStatus` | Return delivery status (see below) |

#### Transport Movement History (invDocCargoVOArray)

When `addHistoricalData=true`, each movement event includes:

| Field | Description | Example |
|-------|-------------|---------|
| `unitId` | YK unit code | 8070 |
| `unitName` | YK unit name | AFSIN IRTIBAT |
| `eventId` | Event code | YK |
| `eventName` | Event description | Kargo Indirildi |
| `reasonId` | Reason code | OK |
| `reasonName` | Reason description | Sorun Yok |
| `eventDate` | Event date | 20110711 |
| `eventTime` | Event time | 082304 |
| `cityName` | City | Kahramanmaras |
| `townName` | District | Afsin |

### Cancel Shipment (cancelDispatch)

Cancellation is only possible before the shipment is dispatched (invoice not yet issued).

```php
$response = $carrier->cancelShipment([
    'cargoKeys' => 'ORDER-001',
])->send();

if ($response->isSuccessful() && $response->isCancelled()) {
    echo 'Shipment cancelled';
}
```

#### Cancel Operation Statuses

| operationCode | operationStatus | Description |
|---------------|-----------------|-------------|
| 0 | NOP | Shipment not processed |
| 1 | IND | Shipment in delivery |
| 2 | ISR | Shipment processed, invoice not yet issued |
| 3 | CNL | Shipment dispatch blocked (cancelled) |
| 4 | ISC | Shipment was already cancelled |
| 5 | DLV | Shipment delivered |

### Return Shipment Code (saveReturnShipmentCode)

Create RMA return codes that customers can use at YK branches.

```php
// Parameters: fieldName=16 (use 53 or 3 in test), returnCode, startDate, endDate, maxCount
```

| Parameter | Description | Example |
|-----------|-------------|---------|
| `fieldName` | Special field ID (16 for returns, use 53/3 in test) | 16 |
| `returnCode` | Your return code | 21312312 |
| `startDate` | Code validity start (YYYYMMDD) | 20231003 |
| `endDate` | Code validity end (YYYYMMDD) | 20231103 |
| `maxCount` | Maximum uses | 1 |

## API Reference

### WSDL Endpoints

| Environment | URL |
|-------------|-----|
| Test | `http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices?wsdl` |
| Production | `https://ws.yurticikargo.com/KOPSWebServices/ShippingOrderDispatcherServices?wsdl` |

> **IP Whitelisting Required:** You must provide your outgoing IP to Yurtiçi Kargo IT team for access authorization.

### Test Credentials

| Field | Value |
|-------|-------|
| Username | `YKTEST` |
| Password | `YK` |
| Payment Type | Sender Pays |
| Language | `TR` |

### SOAP Methods

| Method | SOAP Action | Description |
|--------|-------------|-------------|
| `createShipment` | `ship:createShipment` | Create shipment(s) via ShippingOrderVO array |
| `queryShipment` | `ship:queryShipment` | Query shipment status (ShippingDeliveryVO) |
| `cancelShipment` | `ship:cancelDispatch` | Cancel pending shipment(s) by cargoKey |
| `saveReturnShipmentCode` | `ship:saveReturnShipmentCode` | Create RMA return code |
| `cancelReturnShipmentCode` | `ship:cancelReturnShipmentCode` | Cancel RMA return code |

### createShipment Parameters (ShippingOrderVO)

#### Required Fields

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `cargoKey` | String(20) | Unique shipment key (barcode on package) | 222012345 |
| `invoiceKey` | String(20) | Unique invoice key | AB00113 |
| `receiverCustName` | String(200) | Receiver name (min 5 chars, min 4 letters) | MEHMET YILMAZ |
| `receiverAddress` | String(500) | Receiver address (exclude city/district, min 5, max 200 chars) | Eski Büyükdere Cad. No:3 |
| `receiverPhone1` | String(20) | Phone with area code (10 digits) | 02123652426 |

#### Optional Fields

| Parameter | Type | Description |
|-----------|------|-------------|
| `receiverPhone2/3` | String(20) | Additional phone numbers |
| `cityName` | String(40) | City name |
| `townName` | String(40) | District name |
| `desi` | Double(9,3) | Volumetric weight (if authorized) |
| `kg` | Double(9,3) | Weight in kg (if authorized) |
| `cargoCount` | Integer(4) | Number of pieces in shipment |
| `waybillNo` | String(20) | Waybill number (required for commercial) |
| `description` | String(255) | Description |
| `emailAddress` | String(200) | Receiver email |
| `taxNumber` | String(11) | Tax number (11 digits for individuals, 10 for companies) |
| `taxOfficeId` | Long(8) | Tax office code |
| `taxOfficeName` | String(60) | Tax office name |
| `orgReceiverCustId` | String(50) | Receiver customer code |
| `orgGeoCode` | String(20) | Customer address code |
| `privilegeOrder` | String(10) | Destination priority order |
| `custProdId` | String | Product code |
| `specialField1` | String(200) | Custom field 1 (see format below) |
| `specialField2` | String(100) | Custom field 2 |
| `specialField3` | String(100) | Custom field 3 |

#### COD Fields

| Parameter | Type | Description |
|-----------|------|-------------|
| `ttCollectionType` | String(1) | 0 = Cash, 1 = Credit Card |
| `ttInvoiceAmount` | Double(18,2) | Collection amount (separator: ".") |
| `ttDocumentId` | Long(12) | Invoice number |
| `ttDocumentSaveType` | String(1) | 0 = Same invoice, 1 = Separate invoice |
| `dcSelectedCredit` | Long(2) | Installment count (credit card only) |
| `dcCreditRule` | Long(2) | 0 = Customer choice required, 1 = Allow single payment |

### Special Fields (specialField1)

Multiple custom values can be sent in `specialField1` using this format:

```
{fieldId}${value}#{fieldId}${value}#
```

Example: `1$426031#2$397427#`

- `$` marks start of field value
- `#` marks end of field value

| fieldName | Description | fieldName | Description |
|-----------|-------------|-----------|-------------|
| 2 | Customer Serial No | 12 | Cost Code |
| 4 | Pouch No | 13 | Product |
| 5 | Package No | 14 | Customer Cargo Ref Code |
| 6 | Customer ID No | 16 | Return Approval Code |
| 7 | Customer Name | 51 | Desi Type |
| 8 | Region | 52 | Representative No |
| 9 | Department/Personnel | 54 | Waybill No |
| 10 | Mobile Phone | 55 | Receiver Tax No |
| 11 | Policy No | 56 | Team Leader Rep No |

### Tracking Status Codes (operationCode)

| Code | Status | Description (TR) | Description (EN) |
|------|--------|-------------------|-------------------|
| 0 | NOP | Kargo İşlem Görmemiş | Not processed |
| 1 | IND | Kargo Teslimattadır | In delivery |
| 2 | ISR | Faturası henüz düzenlenmemiştir | Processed, invoice pending |
| 3 | CNL | Kargo Çıkışı Engellendi | Dispatch blocked |
| 4 | ISC | Daha önceden iptal edilmiştir | Already cancelled |
| 5 | DLV | Kargo teslim edilmiştir | Delivered |
| 6 | BI | Fatura şube tarafından iptal edilmiştir | Invoice cancelled by branch |

### Return Status (returnStatus)

| Value | Description |
|-------|-------------|
| 0 | Not delivered, return invoice not issued |
| 1 | Delivered, return invoice not issued |
| 2 | Delivered, return invoice issued |
| 3 | Returned to sender |

### Reject Status (rejectStatus)

| Value | Description |
|-------|-------------|
| 0 | Return request made |
| 1 | Departure branch approved |
| 2 | Region approved |
| 3 | Customer approved |
| 4 | Awaiting return |
| 7 | Delivery cancelled |
| 8 | Billing cancelled |
| 9 | Return completed |
| 10 | Return finalized |
| 11 | Return not approved |

> Values 0, 1, 2, 3, 9, 10 = cargo is in return process. Values 4, 7, 8, 11 = return was initiated but cancelled, normal process resumed.

### Return Reason Descriptions (reasonDesc)

| Reason |
|--------|
| Adres Yanlış / Yetersiz |
| Alıcı Müşteriye Ulaşılamıyor |
| Çalışma Alanı Olmayan Adres |
| Hasarlı Kargo İadesi |
| Alıcı Tarafından Kabul Edilmedi |
| Ücretinden Dolayı Kabul Edilmedi |
| Müşteri Adresi Yok |
| Müşteri Taşınıyor/Ayrılmış/Tatilde |
| Müşteri İsteği |
| Not Bırakıldığı Halde Alınmadı |
| Varış Merkezi Hatası |

### Response Format

All responses include:
- `outFlag`: `0` = at least one success, `1` = all errors, `2` = unexpected error
- `outResult`: Human-readable result message
- Per-shipment detail VO with `errCode` and `errMessage` (errCode=0 for success)

### Error Codes

#### createShipment Errors

| Code | Constant | Description |
|------|----------|-------------|
| 0 | — | Success |
| 936 | — | Unexpected error (contact YK IT) |
| 80859 | ERR_INTG_CARGO_KEY_PARAM_NOT_FOUND | cargoKey missing |
| 82500 | ERR_INTG_CARGO_KEY_PARAM_LENGHT | cargoKey too long |
| 60020 | ERR_EXIST_CARGO_KEY_PARAM | cargoKey already exists |
| 80057 | MSG_JOB_ID_NOT_FOUND | jobId not found |
| 60017 | ERR_INTG_INVOICE_KEY_PARAM_NOT_FOUND | invoiceKey missing |
| 82501 | ERR_INTG_INVOICE_KEY_PARAM_LENGHT | invoiceKey too long |
| 60018 | ERR_INTG_RECEIVER_CUST_NAME_PARAM_NOT_FOUND | Receiver name missing |
| 82503 | ERR_INTG_RECEIVER_CUST_NAME_PARAM_LENGHT | Receiver name too long |
| 60019 | ERR_INTG_RECEIVER_ADDRESS_PARAM_NOT_FOUND | Receiver address missing |
| 82502 | ERR_INTG_RECEIVER_ADDRESS_PARAM_LENGHT | Receiver address too long |
| 82505 | ERR_INTG_TT_INVOICE_AMOUNT_PARAM_NOT_FOUND | COD amount missing |
| 82506 | ERR_INTG_TT_INVOICE_AMOUNT_PARAM_LENGHT | COD amount too long |
| 82507 | ERR_INTG_TT_DOCUMENT_ID_PARAM_NOT_FOUND | COD document ID missing |
| 82508 | ERR_INTG_TT_DOCUMENT_ID_PARAM_LENGHT | COD document ID too long |
| 82509 | ERR_INTG_DC_SELECTED_CREDIT_NOT_FOUND | Installment count missing |
| 82510 | ERR_INTG_DC_SELECTED_CREDIT_LENGHT | Installment count too long |
| 82511 | ERR_INTG_DC_CREDIT_RULE_NOT_FOUND | Credit rule missing |
| 82512 | ERR_INTG_DC_COLL_CC_WRONG_PARAMETER | Payment type mismatch with contract |
| 82513 | ERR_INTG_TT_COLL_TYPE | Invalid COD type (must be 0 or 1) |
| 82514 | ERR_INTG_TT_DOC_SAVE_TYPE | Invalid document save type (must be 0 or 1) |
| 82515 | ERR_INTG_EMAIL_ADDRESS_INVALID_PARAMETER | Invalid email address |
| 82516 | ERR_INTG_RECEIVER_PHONE_INVALID_PARAMETER | Invalid phone number |
| 82517 | ERR_INTG_INVALID_PARAMETER | Invalid format |
| 82518 | ERR_INTG_DC_CREDIT_RULE_WRONG_PARAMETER | Invalid credit rule value |

#### cancelShipment Errors

| Code | Constant | Description |
|------|----------|-------------|
| 82519 | ERR_INTG_CARGO_KEY_NOT_FOUND | cargoKey not found for this user |
| 82520 | ERR_INTG_CARGO_KEY_OPERATION_CANCELLED | cargoKey already cancelled |

#### queryShipment Errors

| Code | Constant | Description |
|------|----------|-------------|
| 82526 | ERR_INTG_KEYS_NOT_FOUND | keys parameter missing |
| 82527 | ERR_INTG_KEY_TYPE_NOT_FOUND | keyType parameter invalid |

### Parametric Tracking Links

You can build tracking URLs for customers using:

```
https://selfservis.yurticikargo.com/reports/SavReportsFromParamFields.aspx?ssfldvn={fieldId}&sskurkod={customerCode}&refnumber={value}&date={dd.mm.yyyy}
```

| Parameter | Description |
|-----------|-------------|
| `ssfldvn` | Field type ID (see Special Fields table, 99 for waybill) |
| `sskurkod` | YK customer code |
| `refnumber` | Reference value to search |
| `date` | Shipment date (dd.mm.yyyy, +/-5 day tolerance) |

## Integration Flow

1. Complete development and test in test environment
2. Send `createShipment` data to test, share `cargo_key` with YK IT for verification
3. YK dispatches test shipment, you query with `queryShipment` to verify tracking
4. Test `cancelShipment` to verify cancellation works
5. Request production credentials from your regional sales representative

## Notes

- **Two working modes**: Invoice-based (one cargoKey per shipment) and Package-based (one cargoKey per piece, shared invoiceKey)
- The `cargoKey` barcode must be physically present on the package for YK branch to scan
- `queryShipment` uses `wsLanguage` parameter (not `userLanguage` like other methods)
- Barcode/tracking number (`docId`) is only available after `queryShipment`, not at creation time
- Batch operations supported: pass multiple ShippingOrderVO items
- Rate limiting on `queryShipment`: 1-minute cooldown on repeated queries
- Cancellation only works before the shipment invoice is issued by YK branch

## Testing

```bash
vendor/bin/pest
```

## License

MIT
