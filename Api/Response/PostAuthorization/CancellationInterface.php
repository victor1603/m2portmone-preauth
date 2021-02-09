<?php

namespace CodeCustom\PortmonePreAuthorization\Api\Response\PostAuthorization;

interface CancellationInterface
{

    /**
     * @param string $orderIncrementId
     * @return mixed
     */
    public function cancel($orderIncrementId = '');
}
