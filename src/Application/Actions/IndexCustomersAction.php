<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Application\Actions;

use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerIndexResult;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Support\CustomerServiceSupport;

final readonly class IndexCustomersAction
{
    public function __construct(
        private CustomerServiceSupport $support,
    ) {}

    /**
     * @param array<string, mixed>|string $params
     */
    public function handle(array|string $params): CustomerIndexResult
    {
        $payload = is_array($params) ? $params : $this->support->decodePayload($params);
        $criteria = $payload['filtros'] ?? $payload;

        if (!is_array($criteria)) {
            throw new InvalidPayloadException('Estrutura inválida para indexação de clientes.');
        }

        $limit = 0;
        if (array_key_exists('limit', $criteria) && $criteria['limit'] !== null && $criteria['limit'] !== '') {
            $limit = (int) $criteria['limit'];

            if ($limit <= 0 || $limit > 1000) {
                throw new InvalidPayloadException('O campo limit deve estar entre 1 e 1000.');
            }
        }

        $customers = $this->support->indexCustomers($limit);

        return new CustomerIndexResult(
            success: true,
            customers: $customers,
            total: count($customers),
            message: 'Clientes listados com sucesso',
        );
    }
}
