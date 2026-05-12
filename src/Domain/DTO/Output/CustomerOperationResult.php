<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output;

final readonly class CustomerOperationResult
{
    public function __construct(
        public bool $success,
        public ?CustomerRecord $customer,
        public string $message,
    ) {}

    /**
     * @return array{success: bool, customer: ?array<string, mixed>, message: string}
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'customer' => $this->customer?->toArray(),
            'message' => $this->message,
        ];
    }
}
