# Usage Guide

## Service Entry Point

Use the application service:

- `FranciscoCardoso\ARTSOFTCustomer\Application\Services\InsertCustomerService`

It exposes four actions:

- `create(array|string $params)`
- `update(array|string $params)`
- `find(array|string $params)`
- `delete(array|string $params)`
- `index(array|string $params = [])`

All actions return `CustomerOperationResult`.

## Payload Formats

Supported payload formats:

- Wrapped payload: `{ "cliente": { ... } }`
- Flat payload: `{ ... }`

Identifier-based operations (`find` and `delete`) accept:

- `fornumero` or `clinumero`
- or `nif + pais`

`index` accepts optional filters:

- `limit` (int, default `50`, min `1`, max `1000`)
- `filial` (int, default `0`)

## Response Contract

`CustomerOperationResult::toArray()` returns:

- `success` (bool)
- `customer` (array|null)
- `message` (string)

`index` returns `CustomerIndexResult::toArray()` with:

- `success` (bool)
- `customers` (array of summarized customers)
- `total` (int)
- `message` (string)

## Connector Contract

Custom connectors must implement:

- `FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface`

And return:

- `array{success: bool, data?: string, message?: string}`

## Configuration

Country and account-prefix rules are loaded from:

- `config/customer.php`

If configuration is invalid, the package throws `InvalidConfigurationException`.
