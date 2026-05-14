<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Application\Services;

use FranciscoCardoso\ARTSOFTCustomer\Application\Actions\CreateCustomerAction;
use FranciscoCardoso\ARTSOFTCustomer\Application\Actions\DeleteCustomerAction;
use FranciscoCardoso\ARTSOFTCustomer\Application\Actions\FindCustomerAction;
use FranciscoCardoso\ARTSOFTCustomer\Application\Actions\IndexCustomersAction;
use FranciscoCardoso\ARTSOFTCustomer\Application\Actions\UpdateCustomerAction;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerProcessorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerIndexResult;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Input\CustomerInputData;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerOperationResult;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerRecord;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Support\CustomerServiceSupport;

final class InsertCustomerService implements CustomerProcessorInterface
{
    private readonly CustomerServiceSupport $support;

    private readonly CreateCustomerAction $createAction;

    private readonly UpdateCustomerAction $updateAction;

    private readonly FindCustomerAction $findAction;

    private readonly DeleteCustomerAction $deleteAction;

    private readonly IndexCustomersAction $indexAction;

    public function __construct(
        private readonly ?CustomerConnectorInterface $connector = null,
    ) {
        $this->support = new CustomerServiceSupport($this->connector);
        $this->createAction = new CreateCustomerAction($this->support);
        $this->updateAction = new UpdateCustomerAction($this->support);
        $this->findAction = new FindCustomerAction($this->support);
        $this->deleteAction = new DeleteCustomerAction($this->support);
        $this->indexAction = new IndexCustomersAction($this->support);
    }

    /**
     * @param array<string, mixed>|string $params
     */
    public function create(array|string $params): CustomerOperationResult
    {
        return $this->createAction->handle($params);
    }

    /**
     * @param array<string, mixed>|string $params
     */
    public function update(array|string $params): CustomerOperationResult
    {
        return $this->updateAction->handle($params);
    }

    /**
     * @param array<string, mixed>|string $params
     */
    public function find(array|string $params): CustomerOperationResult
    {
        return $this->findAction->handle($params);
    }

    /**
     * @param array<string, mixed>|string $params
     */
    public function delete(array|string $params): CustomerOperationResult
    {
        return $this->deleteAction->handle($params);
    }

    /**
     * @param array<string, mixed>|string $params
     */
    public function index(array|string $params = []): CustomerIndexResult
    {
        return $this->indexAction->handle($params);
    }

    /**
     * @param array<string, mixed>|string $input
     * @return array<string, mixed>
     */
    public function decodePayload(array|string $input): array
    {
        return $this->support->decodePayload($input);
    }

    public function isCustomerConsumidorFinal(CustomerRecord $customer): bool
    {
        return $this->support->isCustomerConsumidorFinal($customer);
    }

    public function generateClinumero(): int
    {
        return $this->support->generateClinumero();
    }

    public function getAccountPrefix(CustomerInputData|CustomerRecord $customer): string
    {
        return $this->support->getAccountPrefix($customer);
    }

    public function updateOrInsertCustomer(CustomerInputData $customer): CustomerRecord
    {
        return $this->support->updateOrInsertCustomer($customer);
    }

    public function getCustomer(CustomerInputData $customer): ?CustomerRecord
    {
        return $this->support->getCustomer($customer);
    }

    public function getCustomerByNumbers(?int $fornumero, ?int $clinumero, int $filial = 0): ?CustomerRecord
    {
        return $this->support->getCustomerByNumbers($fornumero, $clinumero, $filial);
    }

    /**
     * @return array<int, mixed>
     */
    public function getAddresses(int $customerId): array
    {
        return $this->support->getAddresses($customerId);
    }

    public function updateAddresses(CustomerInputData $customer): bool
    {
        return $this->support->updateAddresses($customer);
    }
}
