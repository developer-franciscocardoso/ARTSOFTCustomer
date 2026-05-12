<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\Validation;

use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;

final class CustomerPayloadValidator
{
    /**
     * @param array<string, mixed> $payload
     */
    public static function validate(array $payload): void
    {
        $customerData = $payload['cliente'] ?? $payload;

        if (!is_array($customerData)) {
            throw new InvalidPayloadException('Estrutura inválida do pedido.');
        }

        foreach (['nif', 'pais', 'nome', 'email', 'morada', 'codpostal', 'localidade'] as $field) {
            if (!array_key_exists($field, $customerData)) {
                throw new InvalidPayloadException("Campo obrigatório em falta: {$field}");
            }
        }

        if (!array_key_exists('outras_moradas', $customerData)) {
            return;
        }

        if (!is_array($customerData['outras_moradas'])) {
            throw new InvalidPayloadException('O formato de outras moradas do cliente é inválido');
        }

        $otherAddresses = self::normalizeAddressPayload($customerData['outras_moradas']);

        foreach ($otherAddresses as $index => $address) {
            if (!is_array($address)) {
                throw new InvalidPayloadException("O formato de outras moradas do cliente é inválido na posição {$index}");
            }

            foreach (['nome', 'morada', 'codpostal', 'localidade', 'id'] as $field) {
                if (!array_key_exists($field, $address)) {
                    throw new InvalidPayloadException("Campo obrigatório em falta: {$field} em outras_moradas nr {$index}");
                }
            }
        }
    }

    /**
     * @param array<int|string, mixed> $addresses
     * @return array<int, mixed>
     */
    public static function normalizeAddressPayload(array $addresses): array
    {
        if ($addresses === []) {
            return [];
        }

        return array_is_list($addresses) ? $addresses : [$addresses];
    }
}
