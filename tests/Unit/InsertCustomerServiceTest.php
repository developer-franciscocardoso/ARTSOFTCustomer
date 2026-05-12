<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Tests\Unit;

use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;
use FranciscoCardoso\ARTSOFTCustomer\Application\Services\InsertCustomerService;
use FranciscoCardoso\ARTSOFTCustomer\Tests\Mocks\FakeCustomerConnector;
use PHPUnit\Framework\TestCase;

final class InsertCustomerServiceTest extends TestCase
{
    public function testCreateThrowsWhenRequiredFieldIsMissing(): void
    {
        $service = new InsertCustomerService(new FakeCustomerConnector([]));

        $this->expectException(InvalidPayloadException::class);

        $service->create([
            'cliente' => [
                'nif' => '123456789',
                'pais' => 'PT',
                'nome' => 'Cliente Teste',
                'morada' => 'Rua Central',
                'codpostal' => '1000-100',
                'localidade' => 'Lisboa',
            ],
        ]);
    }

    public function testCreateReturnsFailureWhenCustomerAlreadyExists(): void
    {
        $service = new InsertCustomerService(new FakeCustomerConnector([
            'Queries/Query' => [
                'success' => true,
                'data' => '<root><row><id>10</id><nif>123456789</nif><pais>PT</pais><clinumero>55</clinumero><email>cliente@example.com</email><nome>Cliente Teste</nome><morada>Rua Central</morada><codpostal>1000-100</codpostal><localidade>Lisboa</localidade><filial>0</filial></row></root>',
            ],
            'TerFch/GetOtherAddress' => [
                'success' => true,
                'data' => '<root />',
            ],
        ]));

        $result = $service->create([
            'cliente' => [
                'nif' => '123456789',
                'pais' => 'PT',
                'nome' => 'Cliente Teste',
                'email' => 'cliente@example.com',
                'morada' => 'Rua Central',
                'codpostal' => '1000-100',
                'localidade' => 'Lisboa',
            ],
        ]);

        self::assertFalse($result->success);
        self::assertSame('Cliente já existe.', $result->message);
        self::assertNotNull($result->customer);
        self::assertSame(55, $result->customer->clinumero);
        self::assertSame('cliente@example.com', $result->customer->email);
    }

    public function testCreateAcceptsFlatPayloadWithoutClienteWrapper(): void
    {
        $service = new InsertCustomerService(new FakeCustomerConnector([
            'Queries/Query' => [
                'success' => true,
                'data' => '<root><row><id>10</id><nif>123456789</nif><pais>PT</pais><clinumero>55</clinumero><email>cliente@example.com</email><nome>Cliente Teste</nome><morada>Rua Central</morada><codpostal>1000-100</codpostal><localidade>Lisboa</localidade><filial>0</filial></row></root>',
            ],
            'TerFch/GetOtherAddress' => [
                'success' => true,
                'data' => '<root />',
            ],
        ]));

        $result = $service->create([
            'nif' => '123456789',
            'pais' => 'PT',
            'nome' => 'Cliente Teste',
            'email' => 'cliente@example.com',
            'morada' => 'Rua Central',
            'codpostal' => '1000-100',
            'localidade' => 'Lisboa',
        ]);

        self::assertFalse($result->success);
        self::assertSame('Cliente já existe.', $result->message);
        self::assertNotNull($result->customer);
    }

    public function testFindReturnsCustomerWhenItExists(): void
    {
        $service = new InsertCustomerService(new FakeCustomerConnector([
            'Queries/Query' => [
                'success' => true,
                'data' => '<root><row><id>10</id><nif>123456789</nif><pais>PT</pais><clinumero>55</clinumero><email>cliente@example.com</email><nome>Cliente Teste</nome><morada>Rua Central</morada><codpostal>1000-100</codpostal><localidade>Lisboa</localidade><filial>0</filial></row></root>',
            ],
            'TerFch/GetOtherAddress' => [
                'success' => true,
                'data' => '<root />',
            ],
        ]));

        $result = $service->find([
            'cliente' => [
                'fornumero' => 77,
                'clinumero' => 55,
                'filial' => 0,
            ],
        ]);

        self::assertTrue($result->success);
        self::assertSame('Cliente encontrado com sucesso', $result->message);
        self::assertNotNull($result->customer);
        self::assertSame(55, $result->customer->clinumero);
    }

    public function testFindAllowsNifPaisCompositeKeyWithDefaultFilial(): void
    {
        $service = new InsertCustomerService(new FakeCustomerConnector([
            'Queries/Query' => static function (string $payload): array {
                self::assertStringContainsString('$isequal(%TerFch.Ter.NIF_UE,PT123456789)', $payload);
                self::assertStringContainsString('$isequal(%TerFch.Ter.Filial,0)', $payload);

                return [
                    'success' => true,
                    'data' => '<root><row><id>10</id><nif>123456789</nif><pais>PT</pais><fornumero>77</fornumero><clinumero>55</clinumero><email>cliente@example.com</email><nome>Cliente Teste</nome><morada>Rua Central</morada><codpostal>1000-100</codpostal><localidade>Lisboa</localidade><filial>0</filial></row></root>',
                ];
            },
            'TerFch/GetOtherAddress' => [
                'success' => true,
                'data' => '<root />',
            ],
        ]));

        $result = $service->find([
            'cliente' => [
                'nif' => '123456789',
                'pais' => 'PT',
            ],
        ]);

        self::assertTrue($result->success);
        self::assertSame('Cliente encontrado com sucesso', $result->message);
        self::assertNotNull($result->customer);
        self::assertSame(55, $result->customer->clinumero);
    }

    public function testDeleteRemovesCustomerWhenItExists(): void
    {
        $queryCalls = 0;

        $service = new InsertCustomerService(new FakeCustomerConnector([
            'Queries/Query' => static function () use (&$queryCalls): array {
                $queryCalls++;

                return [
                    'success' => true,
                    'data' => '<root><row><id>10</id><nif>123456789</nif><pais>PT</pais><clinumero>55</clinumero><email>cliente@example.com</email><nome>Cliente Teste</nome><morada>Rua Central</morada><codpostal>1000-100</codpostal><localidade>Lisboa</localidade><filial>0</filial></row></root>',
                ];
            },
            'TerFch/GetOtherAddress' => [
                'success' => true,
                'data' => '<root />',
            ],
            'TerFch/Delete' => [
                'success' => true,
                'data' => '<root />',
            ],
        ]));

        $result = $service->delete([
            'cliente' => [
                'fornumero' => 77,
                'clinumero' => 55,
                'filial' => 0,
            ],
        ]);

        self::assertTrue($result->success);
        self::assertSame('Cliente eliminado com sucesso', $result->message);
        self::assertNull($result->customer);
        self::assertSame(1, $queryCalls);
    }
}
