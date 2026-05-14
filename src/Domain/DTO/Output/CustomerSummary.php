<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output;

final readonly class CustomerSummary
{
    public function __construct(
        public int $id,
        public string $nif,
        public string $pais,
        public int $clinumero,
        public string $nome,
        public string $email,
        public int $filial,
    ) {}

    /**
     * @return array{id: int, nif: string, pais: string, clinumero: int, nome: string, email: string, filial: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nif' => $this->nif,
            'pais' => $this->pais,
            'clinumero' => $this->clinumero,
            'nome' => $this->nome,
            'email' => $this->email,
            'filial' => $this->filial,
        ];
    }
}
