<?php

declare(strict_types=1);

use FranciscoCardoso\ARTSOFTCustomer\Application\Services\InsertCustomerService;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;

require __DIR__ . '/../vendor/autoload.php';

final class ExampleConnector implements CustomerConnectorInterface
{
    public function request(string $endpoint, string $payload): array
    {
        if ($endpoint === 'Queries/Query') {
            return [
                'success' => true,
                'data' => '<root><row><id>10</id><nif>123456789</nif><pais>PT</pais><fornumero>77</fornumero><clinumero>55</clinumero><email>cliente@example.com</email><nome>Cliente Teste</nome><morada>Rua Central</morada><codpostal>1000-100</codpostal><localidade>Lisboa</localidade><filial>0</filial></row></root>',
            ];
        }

        if ($endpoint === 'TerFch/GetOtherAddress') {
            return [
                'success' => true,
                'data' => '<root />',
            ];
        }

        return [
            'success' => false,
            'message' => 'Unexpected endpoint in example: ' . $endpoint,
        ];
    }
}

$service = new InsertCustomerService(new ExampleConnector());

$result = $service->find([
    'cliente' => [
        'nif' => '123456789',
        'pais' => 'PT',
    ],
]);

echo "Find customer by nif+pais example:\n";
print_r($result->toArray());
