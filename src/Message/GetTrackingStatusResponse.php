<?php

declare(strict_types=1);

namespace Omniship\Yurtici\Message;

use Omniship\Common\Enum\ShipmentStatus;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\TrackingResponse;
use Omniship\Common\TrackingEvent;
use Omniship\Common\TrackingInfo;

class GetTrackingStatusResponse extends AbstractResponse implements TrackingResponse
{
    /**
     * Yurtiçi operationCode → ShipmentStatus mapping.
     */
    private const STATUS_MAP = [
        0 => ShipmentStatus::PRE_TRANSIT,      // Kayıt alındı / Not processed
        1 => ShipmentStatus::PICKED_UP,         // Kabul edildi
        2 => ShipmentStatus::IN_TRANSIT,        // Aktarmada / Şubede
        3 => ShipmentStatus::CANCELLED,         // İptal edildi
        4 => ShipmentStatus::OUT_FOR_DELIVERY,  // Dağıtımda
        5 => ShipmentStatus::DELIVERED,          // Teslim edildi
        6 => ShipmentStatus::RETURNED,           // İade
    ];

    public function isSuccessful(): bool
    {
        return $this->getOutFlag() === '0';
    }

    public function getMessage(): ?string
    {
        return $this->getOutResult();
    }

    public function getCode(): ?string
    {
        return $this->getOutFlag();
    }

    public function getTrackingInfo(): TrackingInfo
    {
        $detail = $this->getDetail();
        $events = [];
        $status = ShipmentStatus::UNKNOWN;
        $trackingNumber = '';

        if ($detail !== null) {
            $trackingNumber = $detail->docId ?? $detail->cargoKey ?? '';

            if (isset($detail->operationCode)) {
                $code = (int) $detail->operationCode;
                $status = self::STATUS_MAP[$code] ?? ShipmentStatus::UNKNOWN;
            }

            $events = $this->parseEvents($detail);
        }

        return new TrackingInfo(
            trackingNumber: (string) $trackingNumber,
            status: $status,
            events: $events,
            carrier: 'Yurtiçi Kargo',
        );
    }

    /**
     * @return TrackingEvent[]
     */
    private function parseEvents(object $detail): array
    {
        $events = [];

        if (!isset($detail->invDocCargoVOArray)) {
            if (isset($detail->operationCode, $detail->operationMessage)) {
                $status = self::STATUS_MAP[(int) $detail->operationCode] ?? ShipmentStatus::UNKNOWN;
                $events[] = new TrackingEvent(
                    status: $status,
                    description: (string) $detail->operationMessage,
                    occurredAt: new \DateTimeImmutable(),
                );
            }

            return $events;
        }

        $history = $detail->invDocCargoVOArray;

        if (!is_array($history)) {
            $history = [$history];
        }

        foreach ($history as $item) {
            $operationCode = isset($item->operationCode) ? (int) $item->operationCode : null;
            $status = $operationCode !== null
                ? (self::STATUS_MAP[$operationCode] ?? ShipmentStatus::UNKNOWN)
                : ShipmentStatus::UNKNOWN;

            $dateTime = new \DateTimeImmutable();
            if (isset($item->operationDate) && $item->operationDate !== '') {
                try {
                    $dateTime = new \DateTimeImmutable((string) $item->operationDate);
                } catch (\Exception) {
                    // Keep default
                }
            }

            $location = null;
            if (isset($item->unitName) && $item->unitName !== '') {
                $location = (string) $item->unitName;
            }

            $events[] = new TrackingEvent(
                status: $status,
                description: (string) ($item->operationMessage ?? $item->cargoReasonExplanation ?? ''),
                occurredAt: $dateTime,
                location: $location,
            );
        }

        return $events;
    }

    private function getOutFlag(): ?string
    {
        $data = $this->data;

        if (is_object($data) && isset($data->ShippingDeliveryVO)) {
            return (string) ($data->ShippingDeliveryVO->outFlag ?? null);
        }

        if (is_object($data) && isset($data->outFlag)) {
            return (string) $data->outFlag;
        }

        return null;
    }

    private function getOutResult(): ?string
    {
        $data = $this->data;

        if (is_object($data) && isset($data->ShippingDeliveryVO)) {
            return $data->ShippingDeliveryVO->outResult ?? null;
        }

        if (is_object($data) && isset($data->outResult)) {
            return $data->outResult;
        }

        return null;
    }

    private function getDetail(): ?object
    {
        $data = $this->data;

        $result = $data;
        if (is_object($data) && isset($data->ShippingDeliveryVO)) {
            $result = $data->ShippingDeliveryVO;
        }

        if (!is_object($result)) {
            return null;
        }

        if (isset($result->shippingDeliveryDetailVO)) {
            $detail = $result->shippingDeliveryDetailVO;

            if (is_array($detail)) {
                return $detail[0] ?? null;
            }

            return $detail;
        }

        return null;
    }

    public static function mapStatus(int $operationCode): ShipmentStatus
    {
        return self::STATUS_MAP[$operationCode] ?? ShipmentStatus::UNKNOWN;
    }
}
