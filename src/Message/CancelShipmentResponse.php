<?php

declare(strict_types=1);

namespace Omniship\Yurtici\Message;

use Omniship\Common\Message\AbstractResponse;
use Omniship\Common\Message\CancelResponse;

class CancelShipmentResponse extends AbstractResponse implements CancelResponse
{
    public function isSuccessful(): bool
    {
        return $this->getOutFlag() === '0';
    }

    public function isCancelled(): bool
    {
        if (!$this->isSuccessful()) {
            return false;
        }

        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->operationCode)) {
            return (string) $detail->operationCode === '0';
        }

        return true;
    }

    public function getMessage(): ?string
    {
        $detail = $this->getDetail();

        if ($detail !== null && isset($detail->operationMessage) && $detail->operationMessage !== '') {
            return (string) $detail->operationMessage;
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

        if (isset($result->shippingCancelDetailVO)) {
            $detail = $result->shippingCancelDetailVO;

            if (is_array($detail)) {
                return $detail[0] ?? null;
            }

            return $detail;
        }

        return null;
    }
}
