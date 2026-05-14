<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Tests\Unit;

use FranciscoCardoso\ArtsoftConnector\Contracts\ArtsoftServiceInterface;
use FranciscoCardoso\ArtsoftConnector\DTO\Output\RequestResultDTO;
use FranciscoCardoso\ARTSOFTCustomer\Infrastructure\Connectors\ArtsoftCustomerConnectorAdapter;
use PHPUnit\Framework\TestCase;

final class ArtsoftCustomerConnectorAdapterTest extends TestCase
{
    public function testRequestMapsSuccessfulConnectorResponse(): void
    {
        $service = new class implements ArtsoftServiceInterface {
            public function request(string $service, string $xml, bool $end = false): RequestResultDTO
            {
                return new RequestResultDTO(
                    success: true,
                    service: $service,
                    company: 'company-a',
                    timestamp: date('c'),
                    data: '<root><ok /></root>',
                );
            }

            public function requestWithRetry(string $service, string $xml, ?int $maxAttempts = null): RequestResultDTO
            {
                return $this->request($service, $xml);
            }

            public function switchCompany(string $company): void {}

            public function testConnection(): bool
            {
                return true;
            }
        };

        $adapter = new ArtsoftCustomerConnectorAdapter($service);
        $response = $adapter->request('Queries/Query', '<i />');

        self::assertTrue($response['success']);
        self::assertSame('<root><ok /></root>', $response['data']);
        self::assertNull($response['message']);
    }

    public function testRequestMapsConnectorErrorMessage(): void
    {
        $service = new class implements ArtsoftServiceInterface {
            public function request(string $service, string $xml, bool $end = false): RequestResultDTO
            {
                return new RequestResultDTO(
                    success: false,
                    service: $service,
                    company: 'company-a',
                    timestamp: date('c'),
                    error: 'ERP error'
                );
            }

            public function requestWithRetry(string $service, string $xml, ?int $maxAttempts = null): RequestResultDTO
            {
                return $this->request($service, $xml);
            }

            public function switchCompany(string $company): void {}

            public function testConnection(): bool
            {
                return false;
            }
        };

        $adapter = new ArtsoftCustomerConnectorAdapter($service);
        $response = $adapter->request('Queries/Query', '<i />');

        self::assertFalse($response['success']);
        self::assertSame('', $response['data']);
        self::assertSame('ERP error', $response['message']);
    }
}
