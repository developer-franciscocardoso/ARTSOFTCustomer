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
                'data' => '<root>'
                    . '<row><id>10</id><nif>123456789</nif><pais>PT</pais><clinumero>55</clinumero><email>cliente1@example.com</email><nome>Cliente 1</nome><filial>0</filial></row>'
                    . '<row><id>11</id><nif>223456789</nif><pais>ES</pais><clinumero>56</clinumero><email>cliente2@example.com</email><nome>Cliente 2</nome><filial>0</filial></row>'
                    . '</root>',
            ];
        }

        return [
            'success' => false,
            'message' => 'Unexpected endpoint in example: ' . $endpoint,
        ];
    }
}

$service = new InsertCustomerService(new ExampleConnector());

$result = $service->index([
    'limit' => 2,
    'filial' => 0,
]);

echo "Index customers example:\n";
print_r($result->toArray());
