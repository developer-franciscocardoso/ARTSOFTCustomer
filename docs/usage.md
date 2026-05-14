# Usage Guide

## Service Entry Point

Use the application service:

- `FranciscoCardoso\ARTSOFTCustomer\Application\Services\ArtsoftCustomerService`

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

- `limit` (int, optional; when omitted returns all available rows, when provided must be between `1` and `1000`)

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

## TerFch Catalog Access

`ArtsoftCustomerService` exposes helper methods to consult bundled TerFch fields:

- `getTerFchFields(?string $search = null): array`
- `hasTerFchField(string $alias): bool`
- `buildTerFchDefcol(array $aliases): string`
- `buildTerFchListQueryPayload(array $aliases, string $query = 'TerFch|Autoinc=1:999999999', ?string $where = null): string`

The catalog source file is:

- `resources/terfch_fields.txt`

Example:

```php
$service = new ArtsoftCustomerService();

$fields = $service->getTerFchFields('email');
$defcol = $service->buildTerFchDefcol(['ter_nome', 'ter_email', 'pais_abrv']);
$payload = $service->buildTerFchListQueryPayload(
	['ter_nome', 'ter_email', 'pais_abrv'],
	where: '$isequal(%TerFch.Ter.Filial,0)'
);
```
