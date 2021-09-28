<?php

namespace CodeCustom\PortmonePreAuthorization\Api\Response\PostAuthorization;

interface ConfirmationInterface
{

    /**
     * @param string $orderIncrementId
     * @return mixed
     */
    public function confirm($orderIncrementId = '');
}
