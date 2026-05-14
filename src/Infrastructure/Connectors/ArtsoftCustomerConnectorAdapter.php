<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Infrastructure\Connectors;

use FranciscoCardoso\ArtsoftConnector\Artsoft;
use FranciscoCardoso\ArtsoftConnector\Contracts\ArtsoftServiceInterface;
use FranciscoCardoso\ArtsoftConnector\ServiceProviders\ArtsoftConnectorServiceProvider;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\ConnectorException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidConfigurationException;
use Throwable;

final class ArtsoftCustomerConnectorAdapter implements CustomerConnectorInterface
{
    private ArtsoftServiceInterface $service;

    /**
     * @param array<string, mixed>|null $artsoftConfig
     */
    public function __construct(
        ?ArtsoftServiceInterface $service = null,
        ?string $company = null,
        ?array $artsoftConfig = null,
    )
    {
        $this->service = $service ?? $this->buildDefaultService($company, $artsoftConfig);
    }

    /**
     * @return array{success: bool, data?: string, message?: string}
     */
    public function request(string $endpoint, string $payload): array
    {
        try {
            $result = $this->service->request($endpoint, $payload);
        } catch (Throwable $exception) {
            throw new ConnectorException('Falha ao executar pedido ARTSOFT: ' . $exception->getMessage(), 0, $exception);
        }

        return [
            'success' => $result->success,
            'data' => $this->normalizeResponseData($result->data),
            'message' => $result->error,
        ];
    }

    /**
     * @param array<string, mixed>|null $artsoftConfig
     */
    private function buildDefaultService(?string $company, ?array $artsoftConfig): ArtsoftServiceInterface
    {
        $config = $artsoftConfig ?? $this->resolveArtsoftConfig();

        try {
            return Artsoft::create($config, $company);
        } catch (Throwable $exception) {
            throw new InvalidConfigurationException('Configuração inválida do ARTSOFT connector: ' . $exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveArtsoftConfig(): array
    {
        $frameworkConfig = $this->resolveFrameworkConfig();
        if ($frameworkConfig !== null) {
            return $frameworkConfig;
        }

        $provider = new ArtsoftConnectorServiceProvider();
        $loaded = require $provider->getSourceConfigPath();

        if (!is_array($loaded)) {
            throw new InvalidConfigurationException('A configuração do ARTSOFT connector deve devolver um array.');
        }

        return $loaded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveFrameworkConfig(): ?array
    {
        if (!function_exists('config')) {
            return null;
        }

        $config = config('artsoft');

        return is_array($config) ? $config : null;
    }

    private function normalizeResponseData(mixed $data): string
    {
        if (is_string($data)) {
            return $data;
        }

        if (!is_array($data)) {
            return '';
        }

        foreach (['data', 'xml', 'response'] as $key) {
            if (isset($data[$key]) && is_string($data[$key])) {
                return $data[$key];
            }
        }

        $encoded = json_encode($data);

        return is_string($encoded) ? $encoded : '';
    }
}

        return new $serviceClass();
    }
}
