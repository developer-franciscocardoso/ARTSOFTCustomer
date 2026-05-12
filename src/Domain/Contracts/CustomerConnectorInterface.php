<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts;

interface CustomerConnectorInterface
{
    /**
     * @return array{success: bool, data?: string, message?: string}
     */
    public function request(string $endpoint, string $payload): array;
}
