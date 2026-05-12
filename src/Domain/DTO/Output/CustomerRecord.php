<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output;

use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Input\CustomerAddressData;

final readonly class CustomerRecord
{
    /**
     * @param CustomerAddressData[] $outrasMoradas
     */
    public function __construct(
        public int $id,
        public string $nif,
        public string $pais,
        public ?int $fornumero,
        public int $clinumero,
        public string $email,
        public string $nome,
        public string $morada,
        public string $codpostal,
        public string $localidade,
        public int $filial,
        public array $outrasMoradas,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nif' => $this->nif,
            'pais' => $this->pais,
            'fornumero' => $this->fornumero,
            'clinumero' => $this->clinumero,
            'email' => $this->email,
            'nome' => $this->nome,
            'morada' => $this->morada,
            'codpostal' => $this->codpostal,
            'localidade' => $this->localidade,
            'filial' => $this->filial,
            'outras_moradas' => array_map(
                static fn(CustomerAddressData $address): array => $address->toArray(),
                $this->outrasMoradas,
            ),
        ];
    }
}
