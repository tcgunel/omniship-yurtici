<?php

declare(strict_types=1);

namespace Omniship\Yurtici\Message;

use Omniship\Common\Address;
use Omniship\Common\Enum\PaymentType;
use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;
use Omniship\Common\Package;

class CreateShipmentRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'createShipment';
    }

    public function getCargoKey(): ?string
    {
        return $this->getParameter('cargoKey');
    }

    public function setCargoKey(string $cargoKey): static
    {
        return $this->setParameter('cargoKey', $cargoKey);
    }

    public function getInvoiceKey(): ?string
    {
        return $this->getParameter('invoiceKey');
    }

    public function setInvoiceKey(string $invoiceKey): static
    {
        return $this->setParameter('invoiceKey', $invoiceKey);
    }

    public function getDescription(): ?string
    {
        return $this->getParameter('description');
    }

    public function setDescription(string $description): static
    {
        return $this->setParameter('description', $description);
    }

    public function getPaymentType(): ?PaymentType
    {
        return $this->getParameter('paymentType');
    }

    public function setPaymentType(PaymentType $paymentType): static
    {
        return $this->setParameter('paymentType', $paymentType);
    }

    public function getCashOnDelivery(): bool
    {
        return (bool) $this->getParameter('cashOnDelivery');
    }

    public function setCashOnDelivery(bool $value): static
    {
        return $this->setParameter('cashOnDelivery', $value);
    }

    public function getCodAmount(): ?float
    {
        return $this->getParameter('codAmount');
    }

    public function setCodAmount(float $amount): static
    {
        return $this->setParameter('codAmount', $amount);
    }

    public function getCodCollectionType(): int
    {
        return $this->getParameter('codCollectionType') ?? 0;
    }

    public function setCodCollectionType(int $type): static
    {
        return $this->setParameter('codCollectionType', $type);
    }

    public function getSpecialField1(): ?string
    {
        return $this->getParameter('specialField1');
    }

    public function setSpecialField1(string $value): static
    {
        return $this->setParameter('specialField1', $value);
    }

    public function getSpecialField2(): ?string
    {
        return $this->getParameter('specialField2');
    }

    public function setSpecialField2(string $value): static
    {
        return $this->setParameter('specialField2', $value);
    }

    public function getSpecialField3(): ?string
    {
        return $this->getParameter('specialField3');
    }

    public function setSpecialField3(string $value): static
    {
        return $this->setParameter('specialField3', $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('username', 'password', 'shipTo', 'cargoKey', 'invoiceKey');

        $shipTo = $this->getShipTo();
        assert($shipTo instanceof Address);

        $packages = $this->getPackages() ?? [];
        $firstPackage = $packages[0] ?? null;

        $totalDesi = 0;
        $totalKg = 0.0;
        $totalCount = 0;

        foreach ($packages as $package) {
            $totalDesi += $package->getDesi() ?? 0;
            $totalKg += $package->weight;
            $totalCount += $package->quantity;
        }

        if ($totalCount === 0) {
            $totalCount = 1;
        }

        $shippingOrder = [
            'cargoKey' => $this->getCargoKey(),
            'invoiceKey' => $this->getInvoiceKey(),
            'receiverCustName' => $shipTo->name,
            'receiverAddress' => $this->buildAddress($shipTo),
            'receiverPhone1' => $shipTo->phone,
            'cityName' => $shipTo->city,
            'townName' => $shipTo->district,
            'cargoCount' => $totalCount,
        ];

        if ($totalDesi > 0) {
            $shippingOrder['desi'] = $totalDesi;
        }

        if ($totalKg > 0) {
            $shippingOrder['kg'] = $totalKg;
        }

        if ($shipTo->email !== null) {
            $shippingOrder['emailAddress'] = $shipTo->email;
        }

        if ($shipTo->taxId !== null) {
            $shippingOrder['taxNumber'] = $shipTo->taxId;
        }

        if ($this->getDescription() !== null) {
            $shippingOrder['description'] = $this->getDescription();
        }

        if ($this->getSpecialField1() !== null) {
            $shippingOrder['specialField1'] = $this->getSpecialField1();
        }

        if ($this->getSpecialField2() !== null) {
            $shippingOrder['specialField2'] = $this->getSpecialField2();
        }

        if ($this->getSpecialField3() !== null) {
            $shippingOrder['specialField3'] = $this->getSpecialField3();
        }

        if ($this->getCashOnDelivery() && $this->getCodAmount() !== null) {
            $shippingOrder['ttInvoiceAmount'] = $this->getCodAmount();
            $shippingOrder['ttCollectionType'] = $this->getCodCollectionType();
        }

        if ($firstPackage?->description !== null) {
            $shippingOrder['custProdId'] = $firstPackage->description;
        }

        return [
            'wsUserName' => $this->getParameter('username'),
            'wsPassword' => $this->getParameter('password'),
            'userLanguage' => $this->getParameter('userLanguage') ?? 'TR',
            'ShippingOrderVO' => $shippingOrder,
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new CreateShipmentResponse($this, $data);
    }

    private function buildAddress(Address $address): string
    {
        $parts = array_filter([
            $address->street1,
            $address->street2,
        ]);

        return implode(' ', $parts);
    }
}
