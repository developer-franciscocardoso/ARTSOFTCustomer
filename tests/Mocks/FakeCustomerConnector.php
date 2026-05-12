<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Tests\Mocks;

use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;
use RuntimeException;

final class FakeCustomerConnector implements CustomerConnectorInterface
{
    /**
     * @param array<string, callable|string|array{success: bool, data?: string, message?: string}> $responses
     */
    public function __construct(
        private readonly array $responses,
    ) {}

    /**
     * @return array{success: bool, data?: string, message?: string}
     */
    public function request(string $endpoint, string $payload): array
    {
        $response = $this->responses[$endpoint] ?? null;

        if ($response === null) {
            throw new RuntimeException("Missing fake response for endpoint {$endpoint}");
        }

        if (is_callable($response)) {
            $resolved = $response($payload, $endpoint);

            if (!is_array($resolved)) {
                throw new RuntimeException("Fake response callback for {$endpoint} must return an array.");
            }

            /** @var array{success: bool, data?: string, message?: string} */
            return $resolved;
        }

        if (!is_array($response)) {
            throw new RuntimeException("Fake response for {$endpoint} must be an array or callable.");
        }

        /** @var array{success: bool, data?: string, message?: string} */
        return $response;
    }
}
