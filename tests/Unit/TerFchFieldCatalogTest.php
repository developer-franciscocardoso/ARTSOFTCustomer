<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Tests\Unit;

use FranciscoCardoso\ARTSOFTCustomer\Application\Services\ArtsoftCustomerService;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;
use FranciscoCardoso\ARTSOFTCustomer\Tests\Mocks\FakeCustomerConnector;
use PHPUnit\Framework\TestCase;

final class TerFchFieldCatalogTest extends TestCase
{
    public function testCatalogExposesBundledFields(): void
    {
        $service = new ArtsoftCustomerService(new FakeCustomerConnector([
            'Queries/Query' => ['success' => true, 'data' => '<root />'],
        ]));

        $fields = $service->getTerFchFields();

        self::assertNotEmpty($fields);
        self::assertTrue($service->hasTerFchField('ter_nif_ue'));
    }

    public function testCatalogSearchReturnsFilteredFields(): void
    {
        $service = new ArtsoftCustomerService(new FakeCustomerConnector([
            'Queries/Query' => ['success' => true, 'data' => '<root />'],
        ]));

        $fields = $service->getTerFchFields('nif_ue');

        self::assertNotEmpty($fields);
        self::assertSame('ter_nif_ue', $fields[0]['alias']);
    }

    public function testBuildDefcolFromAliases(): void
    {
        $service = new ArtsoftCustomerService(new FakeCustomerConnector([
            'Queries/Query' => ['success' => true, 'data' => '<root />'],
        ]));

        $defcol = $service->buildTerFchDefcol(['ter_nome', 'ter_email', 'pais_abrv']);

        self::assertStringContainsString('<ter_nome form="%TerFch.ter.nome"/>', $defcol);
        self::assertStringContainsString('<ter_email form="%TerFch.ter.email"/>', $defcol);
        self::assertStringContainsString('<pais_abrv form="%TerFch.pais.abrv"/>', $defcol);
    }

    public function testBuildDefcolThrowsOnUnknownField(): void
    {
        $service = new ArtsoftCustomerService(new FakeCustomerConnector([
            'Queries/Query' => ['success' => true, 'data' => '<root />'],
        ]));

        $this->expectException(InvalidPayloadException::class);

        $service->buildTerFchDefcol(['ter_nome', 'unknown_field']);
    }

    public function testBuildListQueryPayloadFromAliases(): void
    {
        $service = new ArtsoftCustomerService(new FakeCustomerConnector([
            'Queries/Query' => ['success' => true, 'data' => '<root />'],
        ]));

        $payload = $service->buildTerFchListQueryPayload(
            aliases: ['ter_nome', 'ter_email', 'pais_abrv'],
            where: '$isequal(%TerFch.Ter.Filial,0)'
        );

        self::assertStringContainsString('<i type="list" query="TerFch|Autoinc=1:999999999 |? $isequal(%TerFch.Ter.Filial,0)">', $payload);
        self::assertStringContainsString('<defcol>', $payload);
        self::assertStringContainsString('<ter_nome form="%TerFch.ter.nome"/>', $payload);
        self::assertStringContainsString('<ter_email form="%TerFch.ter.email"/>', $payload);
        self::assertStringContainsString('<pais_abrv form="%TerFch.pais.abrv"/>', $payload);
        self::assertStringContainsString('</defcol></i>', $payload);
    }

    public function testBuildListQueryPayloadThrowsOnEmptyQueryExpression(): void
    {
        $service = new ArtsoftCustomerService(new FakeCustomerConnector([
            'Queries/Query' => ['success' => true, 'data' => '<root />'],
        ]));

        $this->expectException(InvalidPayloadException::class);

        $service->buildTerFchListQueryPayload(['ter_nome'], '');
    }
}
