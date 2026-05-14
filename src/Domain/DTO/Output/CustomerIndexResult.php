<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output;

final readonly class CustomerIndexResult
{
    /**
     * @param CustomerSummary[] $customers
     */
    public function __construct(
        public bool $success,
        public array $customers,
        public int $total,
        public string $message,
    ) {}

    /**
     * @return array{success: bool, customers: array<int, array{id: int, nif: string, pais: string, clinumero: int, nome: string, email: string, filial: int}>, total: int, message: string}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'customers' => array_map(
                static fn(CustomerSummary $customer): array => $customer->toArray(),
                $this->customers,
            ),
            'total' => $this->total,
            'message' => $this->message,
        ];
    }
}
