<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\Support;

use FranciscoCardoso\ARTSOFTCustomer\Domain\Contracts\CustomerConnectorInterface;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Input\CustomerAddressData;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Input\CustomerInputData;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerRecord;
use FranciscoCardoso\ARTSOFTCustomer\Domain\DTO\Output\CustomerSummary;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\ConnectorException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\CustomerPersistenceException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidConfigurationException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Validation\CustomerPayloadValidator;
use FranciscoCardoso\ARTSOFTCustomer\Infrastructure\Connectors\ArtsoftCustomerConnectorAdapter;
use SimpleXMLElement;
use Throwable;

final class CustomerServiceSupport
{
    private readonly TerFchFieldCatalog $terFchFieldCatalog;

    private string $nationalCountry;

    /**
     * @var string[]
     */
    private array $internationalCountries;

    /**
     * @var array{nac: string, int: string, ext: string}
     */
    private array $accountPrefixes;

    public function __construct(
        private readonly ?CustomerConnectorInterface $connector = null,
        ?array $rules = null,
        ?TerFchFieldCatalog $terFchFieldCatalog = null,
    ) {
        $this->terFchFieldCatalog = $terFchFieldCatalog ?? new TerFchFieldCatalog();
        $configuredRules = $rules ?? $this->loadRulesFromConfig();
        $this->applyRules($configuredRules);
    }

    /**
     * @return array<int, array{alias: string, form: string}>
     */
    public function getTerFchFields(?string $search = null): array
    {
        if ($search === null) {
            return $this->terFchFieldCatalog->all();
        }

        return $this->terFchFieldCatalog->search($search);
    }

    public function hasTerFchField(string $alias): bool
    {
        return $this->terFchFieldCatalog->has($alias);
    }

    /**
     * @param array<int, string> $aliases
     */
    public function buildTerFchDefcol(array $aliases): string
    {
        return $this->terFchFieldCatalog->buildDefcol($aliases);
    }

    /**
     * @param array<int, string> $aliases
     */
    public function buildTerFchListQueryPayload(
        array $aliases,
        string $query = 'TerFch|Autoinc=1:999999999',
        ?string $where = null
    ): string {
        return $this->terFchFieldCatalog->buildListQueryPayload($aliases, $query, $where);
    }

    /**
     * @param array<string, mixed>|string $input
     * @return array<string, mixed>
     */
    public function decodePayload(array|string $input): array
    {
        if (is_array($input)) {
            return $input;
        }

        $decoded = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new InvalidPayloadException('JSON inválido');
        }

        return $decoded;
    }

    public function isCustomerConsumidorFinal(CustomerRecord $customer): bool
    {
        return $customer->nif === '999999999'
            && $customer->clinumero === 99999
            && $customer->pais === $this->nationalCountry;
    }

    public function generateClinumero(): int
    {
        $query = '<i type="list" query="TerFch|Cliente|NrCli=1:999999999">'
            . '<defcol><clinumero form="%TerFch.Cli.Numero"/></defcol>'
            . '</i>';

        $response = $this->connector()->request('Queries/Query', $query);
        $xml = $this->loadXml($response['data'] ?? '');

        if ($xml === null) {
            return 1;
        }

        $nodes = $xml->xpath('//clinumero');

        if (!is_array($nodes) || $nodes === []) {
            return 1;
        }

        $numbers = array_map(static fn(SimpleXMLElement $node): int => (int) $node, $nodes);
        $numbers = array_values(array_unique($numbers));
        sort($numbers);

        $expected = 1;

        foreach ($numbers as $number) {
            if ($number < $expected) {
                continue;
            }

            if ($number !== $expected) {
                return $expected;
            }

            $expected++;
        }

        return $expected;
    }

    public function getAccountPrefix(CustomerInputData|CustomerRecord $customer): string
    {
        if ($customer->pais === $this->nationalCountry) {
            return $this->accountPrefixes['nac'];
        }

        if (in_array($customer->pais, $this->internationalCountries, true)) {
            return $this->accountPrefixes['int'];
        }

        return $this->accountPrefixes['ext'];
    }

    public function updateOrInsertCustomer(CustomerInputData $customer): CustomerRecord
    {
        if ($customer->clinumero === null) {
            throw new CustomerPersistenceException('Não foi possível determinar o número do cliente.');
        }

        $query = '<Entity>'
            . '<BaseAddr>'
            . '<Cli.Numero>' . $this->escapeXml((string) $customer->clinumero) . '</Cli.Numero>'
            . '<Ter.Nridfisc>' . $this->escapeXml($customer->nif) . '</Ter.Nridfisc>'
            . '<Pais.Abrv>' . $this->escapeXml($customer->pais) . '</Pais.Abrv>'
            . '<Ter.Email>' . $this->escapeXml($customer->email) . '</Ter.Email>'
            . '<Ter.Nome>' . $this->escapeXml($customer->nome) . '</Ter.Nome>'
            . '<Ter.Filial>' . $this->escapeXml((string) $customer->filial) . '</Ter.Filial>'
            . '<Ter.Morada>' . $this->escapeXml($customer->morada) . '</Ter.Morada>'
            . '<Ter.CPPais>' . $this->escapeXml($customer->codpostal) . '</Ter.CPPais>'
            . '<Ter.Localid>' . $this->escapeXml($customer->localidade) . '</Ter.Localid>'
            . '<Cli.PrefS>' . $this->getAccountPrefix($customer) . '</Cli.PrefS>'
            . '</BaseAddr>'
            . '</Entity>';

        $result = $this->connector()->request('TerFch/Update', $query);
        $this->assertSuccessfulMutation($result, 'Ocorreu um erro ao inserir/atualizar o cliente');

        $this->updateAddresses($customer);

        $savedCustomer = $this->getCustomer($customer);

        if ($savedCustomer === null) {
            throw new CustomerPersistenceException('O cliente foi gravado mas não foi possível reler o registo.');
        }

        return $savedCustomer;
    }

    public function getCustomer(CustomerInputData $customer): ?CustomerRecord
    {
        if ($customer->fornumero !== null || $customer->clinumero !== null) {
            return $this->getCustomerByNumbers($customer->fornumero, $customer->clinumero, $customer->filial);
        }

        $query = '<i type="list" query="TerFch|Autoinc=1:999999999 |? '
            . '$isequal(%TerFch.Ter.NIF_UE,' . $this->escapeQueryValue($customer->pais . $customer->nif) . ') '
            . '^ $isequal(%TerFch.Ter.Filial,' . $customer->filial . ')">'
            . '<defcol>'
            . '<id form="%TerFch.Div.NrFicha"/>'
            . '<nif form="%TerFch.ter.Nridfisc"/>'
            . '<pais form="%TerFch.pais.abrv"/>'
            . '<fornumero form="%TerFch.for.numero"/>'
            . '<clinumero form="%TerFch.cli.numero"/>'
            . '<email form="%TerFch.ter.email"/>'
            . '<nome form="%TerFch.ter.nome"/>'
            . '<morada form="%TerFch.ter.morada"/>'
            . '<codpostal form="%TerFch.ter.cppais"/>'
            . '<localidade form="%TerFch.ter.localid"/>'
            . '<filial form="%TerFch.ter.filial"/>'
            . '</defcol>'
            . '</i>';

        $result = $this->connector()->request('Queries/Query', $query);

        if (!$result['success']) {
            throw new ConnectorException('Ocorreu um erro ao verificar o cliente: ' . ($result['message'] ?? 'resposta inválida'));
        }

        $xml = $this->loadXml($result['data'] ?? '');

        if ($xml === null) {
            return null;
        }

        $nodes = $xml->xpath('//row');

        if (!is_array($nodes) || !isset($nodes[0])) {
            return null;
        }

        $customerXml = $nodes[0];

        return new CustomerRecord(
            id: (int) $customerXml->id,
            nif: (string) $customerXml->nif,
            pais: (string) $customerXml->pais,
            fornumero: isset($customerXml->fornumero) && (string) $customerXml->fornumero !== '' ? (int) $customerXml->fornumero : null,
            clinumero: (int) $customerXml->clinumero,
            email: (string) $customerXml->email,
            nome: (string) $customerXml->nome,
            morada: (string) $customerXml->morada,
            codpostal: (string) $customerXml->codpostal,
            localidade: (string) $customerXml->localidade,
            filial: (int) $customerXml->filial,
            outrasMoradas: $this->getAddresses((int) $customerXml->id),
        );
    }

    public function getCustomerByNumbers(?int $fornumero, ?int $clinumero, int $filial = 0): ?CustomerRecord
    {
        $filters = ['$isequal(%TerFch.Ter.Filial,' . $filial . ')'];

        if ($fornumero !== null) {
            $filters[] = '$isequal(%TerFch.For.Numero,' . $fornumero . ')';
        }

        if ($clinumero !== null) {
            $filters[] = '$isequal(%TerFch.Cli.Numero,' . $clinumero . ')';
        }

        if (count($filters) === 1) {
            throw new InvalidPayloadException('Campo obrigatório em falta: fornumero ou clinumero');
        }

        $query = '<i type="list" query="TerFch|Autoinc=1:999999999 |? '
            . implode(' ^ ', $filters)
            . '">'
            . '<defcol>'
            . '<id form="%TerFch.Div.NrFicha"/>'
            . '<nif form="%TerFch.ter.Nridfisc"/>'
            . '<pais form="%TerFch.pais.abrv"/>'
            . '<fornumero form="%TerFch.for.numero"/>'
            . '<clinumero form="%TerFch.cli.numero"/>'
            . '<email form="%TerFch.ter.email"/>'
            . '<nome form="%TerFch.ter.nome"/>'
            . '<morada form="%TerFch.ter.morada"/>'
            . '<codpostal form="%TerFch.ter.cppais"/>'
            . '<localidade form="%TerFch.ter.localid"/>'
            . '<filial form="%TerFch.ter.filial"/>'
            . '</defcol>'
            . '</i>';

        $result = $this->connector()->request('Queries/Query', $query);

        if (!$result['success']) {
            throw new ConnectorException('Ocorreu um erro ao verificar o cliente: ' . ($result['message'] ?? 'resposta inválida'));
        }

        $xml = $this->loadXml($result['data'] ?? '');

        if ($xml === null) {
            return null;
        }

        $nodes = $xml->xpath('//row');

        if (!is_array($nodes) || !isset($nodes[0])) {
            return null;
        }

        $customerXml = $nodes[0];

        return new CustomerRecord(
            id: (int) $customerXml->id,
            nif: (string) $customerXml->nif,
            pais: (string) $customerXml->pais,
            fornumero: isset($customerXml->fornumero) && (string) $customerXml->fornumero !== '' ? (int) $customerXml->fornumero : null,
            clinumero: (int) $customerXml->clinumero,
            email: (string) $customerXml->email,
            nome: (string) $customerXml->nome,
            morada: (string) $customerXml->morada,
            codpostal: (string) $customerXml->codpostal,
            localidade: (string) $customerXml->localidade,
            filial: (int) $customerXml->filial,
            outrasMoradas: $this->getAddresses((int) $customerXml->id),
        );
    }

    /**
     * @return CustomerAddressData[]
     */
    public function getAddresses(int $customerId): array
    {
        $query = '<root params="' . $this->escapeXml((string) $customerId) . '"/>';
        $result = $this->connector()->request('TerFch/GetOtherAddress', $query);

        if (!$result['success']) {
            throw new ConnectorException('Ocorreu um erro ao obter as moradas do cliente: ' . ($result['message'] ?? 'resposta inválida'));
        }

        $xml = $this->loadXml($result['data'] ?? '');

        if ($xml === null) {
            return [];
        }

        $nodes = $xml->xpath('//addrss');

        if (!is_array($nodes) || $nodes === []) {
            return [];
        }

        $addresses = [];

        foreach ($nodes as $node) {
            $addresses[] = new CustomerAddressData(
                id: (int) ($node['id'] ?? 0),
                nome: (string) $node->nome,
                morada: (string) $node->morada,
                codpostal: (string) $node->cod_postal,
                localidade: (string) $node->localidade,
            );
        }

        return $addresses;
    }

    public function updateAddresses(CustomerInputData $customer): bool
    {
        if ($customer->outrasMoradas === []) {
            return true;
        }

        if ($customer->clinumero === null) {
            throw new CustomerPersistenceException('Não foi possível atualizar moradas sem número de cliente.');
        }

        $query = '<Entity>'
            . '<BaseAddr>'
            . '<Cli.Numero>' . $this->escapeXml((string) $customer->clinumero) . '</Cli.Numero>'
            . '<Ter.Filial>' . $this->escapeXml((string) $customer->filial) . '</Ter.Filial>'
            . '</BaseAddr>'
            . '<OtherAddr>';

        foreach ($customer->outrasMoradas as $address) {
            $query .= '<Rec ID="' . $this->escapeXml((string) $address->id) . '">'
                . '<Ter.Nome>' . $this->escapeXml($address->nome) . '</Ter.Nome>'
                . '<Ter.Filial>' . $this->escapeXml((string) $customer->filial) . '</Ter.Filial>'
                . '<Ter.Morada>' . $this->escapeXml($address->morada) . '</Ter.Morada>'
                . '<Ter.Localid>' . $this->escapeXml($address->localidade) . '</Ter.Localid>'
                . '<Ter.CPPais>' . $this->escapeXml($address->codpostal) . '</Ter.CPPais>'
                . '</Rec>';
        }

        $query .= '</OtherAddr></Entity>';

        $result = $this->connector()->request('TerFch/Update', $query);
        $this->assertSuccessfulMutation($result, 'Ocorreu um erro ao atualizar as moradas do cliente');

        return true;
    }

    /**
     * @return CustomerSummary[]
     */
    public function indexCustomers(int $limit = 0): array
    {
        $query = '<i type="list" query="TerFch|Autoinc=1:999999999 ">'
            . '<defcol>'
            . '<id form="%TerFch.Div.NrFicha"/>'
            . '<nif form="%TerFch.ter.Nridfisc"/>'
            . '<pais form="%TerFch.pais.abrv"/>'
            . '<clinumero form="%TerFch.cli.numero"/>'
            . '<email form="%TerFch.ter.email"/>'
            . '<nome form="%TerFch.ter.nome"/>'
            . '<filial form="%TerFch.ter.filial"/>'
            . '</defcol>'
            . '</i>';

        $result = $this->connector()->request('Queries/Query', $query);

        if (!$result['success']) {
            throw new ConnectorException('Ocorreu um erro ao indexar clientes: ' . ($result['message'] ?? 'resposta inválida'));
        }

        $xml = $this->loadXml($result['data'] ?? '');

        if ($xml === null) {
            return [];
        }

        $nodes = $xml->xpath('//row');

        if (!is_array($nodes) || $nodes === []) {
            return [];
        }
        if ($limit > 0) {
            $nodes = array_slice($nodes, 0, $limit);
        }
        $customers = [];

        foreach ($nodes as $customerXml) {
            $customers[] = new CustomerSummary(
                id: (int) $customerXml->id,
                nif: (string) $customerXml->nif,
                pais: (string) $customerXml->pais,
                clinumero: (int) $customerXml->clinumero,
                nome: (string) $customerXml->nome,
                email: (string) $customerXml->email,
                filial: (int) $customerXml->filial,
            );
        }

        return $customers;
    }

    /**
     * @param array<string, mixed> $customerData
     */
    public function mapCustomer(array $customerData): CustomerInputData
    {
        $otherAddresses = [];

        foreach (CustomerPayloadValidator::normalizeAddressPayload($customerData['outras_moradas'] ?? []) as $address) {
            if (!is_array($address)) {
                throw new InvalidPayloadException('O formato de outras moradas do cliente é inválido');
            }

            $otherAddresses[] = new CustomerAddressData(
                id: (int) ($address['id'] ?? 0),
                nome: (string) ($address['nome'] ?? ''),
                morada: (string) ($address['morada'] ?? ''),
                codpostal: (string) ($address['codpostal'] ?? ''),
                localidade: (string) ($address['localidade'] ?? ''),
            );
        }

        return new CustomerInputData(
            nif: (string) $customerData['nif'],
            pais: (string) $customerData['pais'],
            morada: (string) $customerData['morada'],
            codpostal: (string) $customerData['codpostal'],
            localidade: (string) $customerData['localidade'],
            nome: (string) $customerData['nome'],
            email: (string) ($customerData['email'] ?? ''),
            filial: (int) ($customerData['filial'] ?? 0),
            fornumero: isset($customerData['fornumero']) && $customerData['fornumero'] !== '' ? (int) $customerData['fornumero'] : null,
            clinumero: isset($customerData['clinumero']) && $customerData['clinumero'] !== '' ? (int) $customerData['clinumero'] : null,
            outrasMoradas: $otherAddresses,
        );
    }

    /**
     * @param array<string, mixed>|string $params
     */
    public function mapIdentifierPayload(array|string $params): CustomerInputData
    {
        $payload = $this->decodePayload($params);
        $clienteData = $payload['cliente'] ?? $payload;

        if (!is_array($clienteData)) {
            throw new InvalidPayloadException('Estrutura inválida do pedido.');
        }

        $fornumero = isset($clienteData['fornumero']) && $clienteData['fornumero'] !== '' ? (int) $clienteData['fornumero'] : null;
        $clinumero = isset($clienteData['clinumero']) && $clienteData['clinumero'] !== '' ? (int) $clienteData['clinumero'] : null;
        $nif = isset($clienteData['nif']) ? (string) $clienteData['nif'] : '';
        $pais = isset($clienteData['pais']) ? (string) $clienteData['pais'] : '';

        $hasNumericIdentifier = $fornumero !== null || $clinumero !== null;
        $hasNifCompositeIdentifier = $nif !== '' && $pais !== '';

        if (!$hasNumericIdentifier && !$hasNifCompositeIdentifier) {
            throw new InvalidPayloadException('Campo obrigatório em falta: fornumero, clinumero ou nif+pais');
        }

        return new CustomerInputData(
            nif: $nif,
            pais: $pais,
            morada: '',
            codpostal: '',
            localidade: '',
            nome: '',
            email: '',
            filial: (int) ($clienteData['filial'] ?? 0),
            fornumero: $fornumero,
            clinumero: $clinumero,
            outrasMoradas: [],
        );
    }

    public function deleteCustomer(int $customerId): void
    {
        $query = '<Entity>'
            . '<BaseAddr>'
            . '<Div.NrFicha>' . $this->escapeXml((string) $customerId) . '</Div.NrFicha>'
            . '</BaseAddr>'
            . '</Entity>';

        $result = $this->connector()->request('TerFch/Delete', $query);
        $this->assertSuccessfulResponse($result, 'Ocorreu um erro ao eliminar o cliente');
    }

    /**
     * @param array<string, mixed> $result
     */
    public function assertSuccessfulResponse(array $result, string $message): void
    {
        if (!(bool) ($result['success'] ?? false)) {
            throw new CustomerPersistenceException($message . ': ' . ($result['message'] ?? 'resposta inválida'));
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function assertSuccessfulMutation(array $result, string $message): void
    {
        $success = (bool) ($result['success'] ?? false);
        $data = (string) ($result['data'] ?? '');

        if (!$success || !str_contains($data, 'ID="')) {
            throw new CustomerPersistenceException($message . ': ' . ($result['message'] ?? 'resposta inválida'));
        }
    }

    private function connector(): CustomerConnectorInterface
    {
        return $this->connector ?? new ArtsoftCustomerConnectorAdapter();
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private function escapeQueryValue(string $value): string
    {
        return str_replace([',', '"'], ['\\,', '\\"'], $value);
    }

    private function loadXml(string $xml): ?SimpleXMLElement
    {
        if (trim($xml) === '') {
            return null;
        }

        try {
            $loadedXml = simplexml_load_string($xml);
        } catch (Throwable) {
            return null;
        }

        return $loadedXml instanceof SimpleXMLElement ? $loadedXml : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadRulesFromConfig(): array
    {
        $frameworkConfig = $this->loadRulesFromFrameworkConfig();
        if ($frameworkConfig !== null) {
            return $frameworkConfig;
        }

        $configFile = __DIR__ . '/../../../config/customer.php';

        if (!is_file($configFile)) {
            throw new InvalidConfigurationException('Ficheiro de configuração não encontrado: config/customer.php');
        }

        $loaded = require $configFile;

        if (!is_array($loaded)) {
            throw new InvalidConfigurationException('A configuração de cliente deve devolver um array.');
        }

        return $loaded;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadRulesFromFrameworkConfig(): ?array
    {
        if (!function_exists('config')) {
            return null;
        }

        $loaded = config('customer');

        return is_array($loaded) ? $loaded : null;
    }

    /**
     * @param array<string, mixed> $rules
     */
    private function applyRules(array $rules): void
    {
        $nationalCountry = $rules['national_country'] ?? null;
        $internationalCountries = $rules['international_countries'] ?? null;
        $accountPrefixes = $rules['account_prefixes'] ?? null;

        if (!is_string($nationalCountry) || $nationalCountry === '') {
            throw new InvalidConfigurationException('A chave national_country é obrigatória em config/customer.php.');
        }

        if (!is_array($internationalCountries)) {
            throw new InvalidConfigurationException('A chave international_countries deve ser um array em config/customer.php.');
        }

        $normalizedCountries = array_values(array_filter($internationalCountries, static fn(mixed $country): bool => is_string($country) && $country !== ''));

        if ($normalizedCountries === []) {
            throw new InvalidConfigurationException('A chave international_countries deve conter pelo menos um país válido.');
        }

        if (
            !is_array($accountPrefixes)
            || !isset($accountPrefixes['nac'], $accountPrefixes['int'], $accountPrefixes['ext'])
            || !is_string($accountPrefixes['nac'])
            || !is_string($accountPrefixes['int'])
            || !is_string($accountPrefixes['ext'])
        ) {
            throw new InvalidConfigurationException('A chave account_prefixes deve conter as chaves nac, int e ext em config/customer.php.');
        }

        $this->nationalCountry = $nationalCountry;
        $this->internationalCountries = $normalizedCountries;
        $this->accountPrefixes = [
            'nac' => $accountPrefixes['nac'],
            'int' => $accountPrefixes['int'],
            'ext' => $accountPrefixes['ext'],
        ];
    }
}
