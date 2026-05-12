<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Application\Actions;

use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerOperationResult;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Support\CustomerServiceSupport;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Validation\CustomerPayloadValidator;

final readonly class CreateCustomerAction
{
    public function __construct(
        private CustomerServiceSupport $support,
    ) {}

    /**
     * @param array<string, mixed>|string $params
     */
    public function handle(array|string $params): CustomerOperationResult
    {
        $payload = $this->support->decodePayload($params);
        CustomerPayloadValidator::validate($payload);

        $customerData = $payload['cliente'] ?? $payload;

        if (!is_array($customerData)) {
            throw new InvalidPayloadException('Estrutura inválida do pedido.');
        }

        $customer = $this->support->mapCustomer($customerData);
        $existingCustomer = $this->support->getCustomer($customer);

        if ($existingCustomer !== null) {
            return new CustomerOperationResult(false, $existingCustomer, 'Cliente já existe.');
        }

        $customer = $customer->withClinumero($customer->clinumero ?? $this->support->generateClinumero());
        $savedCustomer = $this->support->updateOrInsertCustomer($customer);

        return new CustomerOperationResult(true, $savedCustomer, 'Cliente inserido com sucesso');
    }
}
