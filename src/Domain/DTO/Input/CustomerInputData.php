<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Input;

final readonly class CustomerInputData
{
    /**
     * @param CustomerAddressData[] $outrasMoradas
     */
    public function __construct(
        public string $nif,
        public string $pais,
        public string $morada,
        public string $codpostal,
        public string $localidade,
        public string $nome,
        public string $email,
        public int $filial,
        public ?int $fornumero,
        public ?int $clinumero,
        public array $outrasMoradas,
    ) {}

    public function withClinumero(int $clinumero): self
    {
        return new self(
            $this->nif,
            $this->pais,
            $this->morada,
            $this->codpostal,
            $this->localidade,
            $this->nome,
            $this->email,
            $this->filial,
            $this->fornumero,
            $clinumero,
            $this->outrasMoradas,
        );
    }
}
