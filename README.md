# francisco-cardoso/artsoft-customer

Customer operations package for ARTSOFT ERP integrations.

## Requirements

- PHP 8.2+
- ext-simplexml

## Install

```bash
composer require francisco-cardoso/artsoft-customer
```

For local workspace development with sibling packages:

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../ARTSOFTConnector",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "francisco-cardoso/artsoft-connector": "^1.0",
    "francisco-cardoso/artsoft-customer": "^1.0"
  }
}
```

## Quick Start

```php
<?php

declare(strict_types=1);

use FranciscoCardoso\ARTSOFTCustomer\Application\Services\ArtsoftCustomerService;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;

require __DIR__ . '/vendor/autoload.php';

final class MyConnector implements CustomerConnectorInterface
{
        public function request(string $endpoint, string $payload): array
        {
                // Replace with real integration call.
                return ['success' => true, 'data' => '<root />'];
        }
}

$service = new ArtsoftCustomerService(new MyConnector());
$result = $service->find(['cliente' => ['nif' => '123456789', 'pais' => 'PT']]);

print_r($result->toArray());
```

## Configuration

Customer country and account prefix rules are loaded from:

- `config/customer.php`

Current configuration shape:

```php
<?php

declare(strict_types=1);

return [
    'national_country' => 'PT',
    'international_countries' => [
        'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
        'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
        'PL', 'RO', 'SK', 'SI', 'ES', 'SE',
    ],
    'account_prefixes' => [
        'nac' => '2111',
        'int' => '2112',
        'ext' => '2113',
    ],
];
```

### Options

- `national_country`: Country code used to classify national customers.
- `international_countries`: Country code list used to classify international customers.
- `account_prefixes.nac`: Account prefix for national customers.
- `account_prefixes.int`: Account prefix for configured international customers.
- `account_prefixes.ext`: Account prefix for all other (external) customers.

### Validation

At runtime, configuration is validated by `CustomerServiceSupport` and the package throws `InvalidConfigurationException` when:

- `config/customer.php` is missing.
- The file does not return an array.
- `national_country` is missing or invalid.
- `international_countries` is missing, invalid, or empty.
- `account_prefixes` is missing required keys (`nac`, `int`, `ext`) or contains invalid values.

## Documentation

- Usage guide: `docs/usage.md`
- Layered architecture: `docs/architecture.md`

## Examples

- Existing-customer create flow: `examples/basic_create_existing.php`
- Find by `nif+pais`: `examples/find_by_nif.php`
- Index summarized customers: `examples/index_customers.php`

Run examples from package root:

```bash
php examples/basic_create_existing.php
php examples/find_by_nif.php
php examples/index_customers.php
```

Expected output shape for create/find examples:

- `success` (bool)
- `customer` (array|null)
- `message` (string)

Expected output shape for index example:

- `success` (bool)
- `customers` (array)
- `total` (int)
- `message` (string)

## TerFch Field Catalog

The package now bundles a TerFch field catalog at `resources/terfch_fields.txt`.

Use these helper methods from `ArtsoftCustomerService` to consult fields:

```php
$service = new ArtsoftCustomerService();

// Full list: [{ alias, form }, ...]
$all = $service->getTerFchFields();

// Search by alias or form expression
$matches = $service->getTerFchFields('nif');

// Validate one field
$exists = $service->hasTerFchField('ter_nif_ue'); // true/false

// Build <defcol> body safely from known aliases
$defcol = $service->buildTerFchDefcol([
  'ter_nome',
  'ter_email',
  'pais_abrv',
]);

// Build full Queries/Query payload (query + defcol)
$payload = $service->buildTerFchListQueryPayload(
  aliases: ['ter_nome', 'ter_email', 'pais_abrv'],
  where: '$isequal(%TerFch.Ter.Filial,0)'
);
```

This approach lets you keep field definitions centralized and avoid typos when creating custom query payloads.

## Tooling

```bash
vendor/bin/phpunit --configuration phpunit.xml
vendor/bin/phpstan analyse -c phpstan.neon
```
