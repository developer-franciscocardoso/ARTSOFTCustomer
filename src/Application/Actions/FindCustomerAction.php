<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Application\Actions;

use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerOperationResult;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Support\CustomerServiceSupport;

final readonly class FindCustomerAction
{
    public function __construct(
        private CustomerServiceSupport $support,
    ) {}

    /**
     * @param array<string, mixed>|string $params
     */
    public function handle(array|string $params): CustomerOperationResult
    {
        $customer = $this->support->mapIdentifierPayload($params);
        $foundCustomer = $this->support->getCustomer($customer);

        if ($foundCustomer === null) {
            return new CustomerOperationResult(false, null, 'Cliente não encontrado.');
        }

        return new CustomerOperationResult(true, $foundCustomer, 'Cliente encontrado com sucesso');
    }
}
