<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Input;

final readonly class CustomerAddressData
{
    public function __construct(
        public int $id,
        public string $nome,
        public string $morada,
        public string $codpostal,
        public string $localidade,
    ) {}

    /**
     * @return array{id: int, nome: string, morada: string, codpostal: string, localidade: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'nome' => $this->nome,
            'morada' => $this->morada,
            'codpostal' => $this->codpostal,
            'localidade' => $this->localidade,
        ];
    }
}
