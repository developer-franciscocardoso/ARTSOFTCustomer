<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Infrastructure\Connectors;

use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\ConnectorException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidConfigurationException;

final class ArtsoftCustomerConnectorAdapter implements CustomerConnectorInterface
{
    private object $client;

    public function __construct(?object $client = null)
    {
        $this->client = $client ?? $this->resolveDefaultClient();
    }

    /**
     * @return array{success: bool, data?: string, message?: string}
     */
    public function request(string $endpoint, string $payload): array
    {
        if (!method_exists($this->client, 'doRequest')) {
            throw new InvalidConfigurationException('O conector configurado não suporta o método doRequest.');
        }

        $result = $this->client->doRequest($endpoint, $payload);

        if (!is_array($result)) {
            throw new ConnectorException('O conector devolveu uma resposta inválida.');
        }

        /** @var array{success: bool, data?: string, message?: string} */
        return $result;
    }

    private function resolveDefaultClient(): object
    {
        $serviceClass = '\\App\\Http\\Services\\ArtsoftService';

        if (!class_exists($serviceClass)) {
            throw new InvalidConfigurationException('Nenhum conector foi fornecido e ArtsoftService não está disponível.');
        }

        return new $serviceClass();
    }
}
