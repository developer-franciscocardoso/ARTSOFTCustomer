<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts;

use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerIndexResult;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerOperationResult;

interface CustomerProcessorInterface
{
    /**
     * @param array<string, mixed>|string $params
     */
    public function create(array|string $params): CustomerOperationResult;

    /**
     * @param array<string, mixed>|string $params
     */
    public function update(array|string $params): CustomerOperationResult;

    /**
     * @param array<string, mixed>|string $params
     */
    public function find(array|string $params): CustomerOperationResult;

    /**
     * @param array<string, mixed>|string $params
     */
    public function delete(array|string $params): CustomerOperationResult;

    /**
     * @param array<string, mixed>|string $params
     */
    public function index(array|string $params = []): CustomerIndexResult;
}
