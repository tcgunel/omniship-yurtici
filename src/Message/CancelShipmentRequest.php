<?php

declare(strict_types=1);

namespace Omniship\Yurtici\Message;

use Omniship\Common\Message\AbstractSoapRequest;
use Omniship\Common\Message\ResponseInterface;

class CancelShipmentRequest extends AbstractSoapRequest
{
    protected function getSoapMethod(): string
    {
        return 'cancelShipment';
    }

    public function getCargoKeys(): ?string
    {
        return $this->getParameter('cargoKeys');
    }

    public function setCargoKeys(string $cargoKeys): static
    {
        return $this->setParameter('cargoKeys', $cargoKeys);
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        $cargoKeys = $this->getCargoKeys() ?? $this->getTrackingNumber();

        if ($cargoKeys === null || $cargoKeys === '') {
            $this->validate('cargoKeys');
        }

        $this->validate('username', 'password');

        return [
            'wsUserName' => $this->getParameter('username'),
            'wsPassword' => $this->getParameter('password'),
            'userLanguage' => $this->getParameter('userLanguage') ?? 'TR',
            'cargoKeys' => $cargoKeys,
        ];
    }

    protected function createResponse(mixed $data): ResponseInterface
    {
        return $this->response = new CancelShipmentResponse($this, $data);
    }
}
