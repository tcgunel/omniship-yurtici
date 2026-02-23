<?php

declare(strict_types=1);

namespace Omniship\Yurtici\Message;

use Omniship\Common\Label;
use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\ShipmentResponse;

class CreateShipmentResponse extends AbstractResponse implements ShipmentResponse
{
    public function isSuccessful(): bool
    {
        return $this->getOutFlag() === '0';
    }

    public function getMessage(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->errMessage) && $detail->errMessage !== '') {
            return (string) $detail->errMessage;
        }

        return $this->getOutResult();
    }

    public function getCode(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->operationCode)) {
            return (string) $detail->operationCode;
        }

        return $this->getOutFlag();
    }

    public function getShipmentId(): ?string
    {
        return $this->getJobId();
    }

    public function getTrackingNumber(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->cargoKey)) {
            return (string) $detail->cargoKey;
        }

        return null;
    }

    public function getBarcode(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->operationCode)) {
            return (string) $detail->operationCode;
        }

        return null;
    }

    public function getLabel(): ?Label
    {
        return null;
    }

    public function getTotalCharge(): ?float
    {
        return null;
    }

    public function getCurrency(): ?string
    {
        return null;
    }

    private function getOutFlag(): ?string
    {
        $data = $this->data;

        if (is_object($data) && isset($data->ShippingOrderResultVO)) {
            return (string) ($data->ShippingOrderResultVO->outFlag ?? null);
        }

        if (is_object($data) && isset($data->outFlag)) {
            return (string) $data->outFlag;
        }

        return null;
    }

    private function getOutResult(): ?string
    {
        $data = $this->data;

        if (is_object($data) && isset($data->ShippingOrderResultVO)) {
            return $data->ShippingOrderResultVO->outResult ?? null;
        }

        if (is_object($data) && isset($data->outResult)) {
            return $data->outResult;
        }

        return null;
    }

    private function getJobId(): ?string
    {
        $data = $this->data;

        if (is_object($data) && isset($data->ShippingOrderResultVO)) {
            $result = $data->ShippingOrderResultVO;
            return isset($result->jobId) ? (string) $result->jobId : null;
        }

        if (is_object($data) && isset($data->jobId)) {
            return (string) $data->jobId;
        }

        return null;
    }

    private function getDetail(): ?object
    {
        $data = $this->data;

        $result = $data;
        if (is_object($data) && isset($data->ShippingOrderResultVO)) {
            $result = $data->ShippingOrderResultVO;
        }

        if (!is_object($result)) {
            return null;
        }

        if (isset($result->shippingOrderDetailVO)) {
            $detail = $result->shippingOrderDetailVO;

            if (is_array($detail)) {
                return $detail[0] ?? null;
            }

            return $detail;
        }

        return null;
    }
}
