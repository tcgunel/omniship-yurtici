<?php

declare(strict_types=1);

namespace Omniship\Yurtici;

use Omniship\Common\AbstractSoapCarrier;
use Omniship\Common\Auth\UsernamePasswordTrait;
use Omniship\Common\Message\RequestInterface;
use Omniship\Yurtici\Message\CancelShipmentRequest;
use Omniship\Yurtici\Message\CreateShipmentRequest;
use Omniship\Yurtici\Message\GetTrackingStatusRequest;

class Carrier extends AbstractSoapCarrier
{
    use UsernamePasswordTrait;

    private const WSDL_PRODUCTION = 'https://ws.yurticikargo.com/KOPSWebServices/ShippingOrderDispatcherServices?wsdl';
    private const WSDL_TEST = 'http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices?wsdl';

    public function getName(): string
    {
        return 'Yurtiçi Kargo';
    }

    public function getShortName(): string
    {
        return 'Yurtici';
    }

    protected function getWsdlUrl(): string
    {
        return $this->getTestMode() ? self::WSDL_TEST : self::WSDL_PRODUCTION;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDefaultParameters(): array
    {
        return [
            'username' => '',
            'password' => '',
            'userLanguage' => 'TR',
            'testMode' => false,
        ];
    }

    public function getUserLanguage(): string
    {
        return $this->getParameter('userLanguage') ?? 'TR';
    }

    public function setUserLanguage(string $language): static
    {
        return $this->setParameter('userLanguage', $language);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CreateShipmentRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function getTrackingStatus(array $options = []): RequestInterface
    {
        return $this->createRequest(GetTrackingStatusRequest::class, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function cancelShipment(array $options = []): RequestInterface
    {
        return $this->createRequest(CancelShipmentRequest::class, $options);
    }
}
