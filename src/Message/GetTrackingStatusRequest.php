<?php

declare(strict_types=1);

namespace Omniship\Yurtici\Message;

use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;

class GetTrackingStatusRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'queryShipment';
    }

    public function getKeyType(): int
    {
        return $this->getParameter('keyType') ?? 0;
    }

    public function setKeyType(int $keyType): static
    {
        return $this->setParameter('keyType', $keyType);
    }

    public function getAddHistoricalData(): bool
    {
        return (bool) ($this->getParameter('addHistoricalData') ?? true);
    }

    public function setAddHistoricalData(bool $value): static
    {
        return $this->setParameter('addHistoricalData', $value);
    }

    public function getOnlyTracking(): bool
    {
        return (bool) ($this->getParameter('onlyTracking') ?? false);
    }

    public function setOnlyTracking(bool $value): static
    {
        return $this->setParameter('onlyTracking', $value);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $this->validate('username', 'password', 'trackingNumber');

        return [
            'wsUserName' => $this->getParameter('username'),
            'wsPassword' => $this->getParameter('password'),
            'wsLanguage' => $this->getParameter('userLanguage') ?? 'TR',
            'keys' => $this->getTrackingNumber(),
            'keyType' => $this->getKeyType(),
            'addHistoricalData' => $this->getAddHistoricalData(),
            'onlyTracking' => $this->getOnlyTracking(),
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new GetTrackingStatusResponse($this, $data);
    }
}
