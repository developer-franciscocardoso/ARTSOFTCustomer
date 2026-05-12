# francisco-cardoso/artsoft-customer

Customer operations package for ARTSOFT ERP integrations.

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
