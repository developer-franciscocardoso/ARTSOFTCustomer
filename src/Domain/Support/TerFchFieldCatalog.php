<?php

declare(strict_types=1);

namespace FranciscoCardoso\ARTSOFTCustomer\Domain\Support;

use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidConfigurationException;
use FranciscoCardoso\ARTSOFTCustomer\Domain\Exceptions\InvalidPayloadException;

final class TerFchFieldCatalog
{
    private const FIELD_PATTERN = '/^<([a-z0-9_]+)\s+form="(%TerFch\.[^"]+)"\/>$/i';

    /**
     * @var array<int, array{alias: string, form: string}>|null
     */
    private ?array $cache = null;

    public function __construct(
        private readonly string $catalogPath = __DIR__ . '/../../../resources/terfch_fields.txt'
    ) {}

    /**
     * @return array<int, array{alias: string, form: string}>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!is_file($this->catalogPath)) {
            throw new InvalidConfigurationException('Catálogo TerFch não encontrado em resources/terfch_fields.txt');
        }

        $content = file_get_contents($this->catalogPath);
        if (!is_string($content)) {
            throw new InvalidConfigurationException('Não foi possível ler o catálogo TerFch.');
        }

        $fields = [];

        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (!preg_match(self::FIELD_PATTERN, $trimmed, $matches)) {
                continue;
            }

            $fields[] = [
                'alias' => strtolower($matches[1]),
                'form' => $matches[2],
            ];
        }

        if ($fields === []) {
            throw new InvalidConfigurationException('O catálogo TerFch está vazio ou com formato inválido.');
        }

        $this->cache = $fields;

        return $fields;
    }

    /**
     * @return array<int, string>
     */
    public function aliases(): array
    {
        return array_values(array_map(static fn(array $field): string => $field['alias'], $this->all()));
    }

    /**
     * @return array<int, array{alias: string, form: string}>
     */
    public function search(string $term): array
    {
        $needle = trim(strtolower($term));

        if ($needle === '') {
            return $this->all();
        }

        return array_values(array_filter(
            $this->all(),
            static fn(array $field): bool => str_contains(strtolower($field['alias']), $needle)
                || str_contains(strtolower($field['form']), $needle)
        ));
    }

    public function has(string $alias): bool
    {
        return in_array(strtolower($alias), $this->aliases(), true);
    }

    /**
     * @param array<int, string> $aliases
     */
    public function buildDefcol(array $aliases): string
    {
        if ($aliases === []) {
            throw new InvalidPayloadException('A lista de campos TerFch não pode estar vazia.');
        }

        $map = [];
        foreach ($this->all() as $field) {
            $map[$field['alias']] = $field['form'];
        }

        $parts = [];

        foreach ($aliases as $alias) {
            $normalized = strtolower(trim($alias));
            if ($normalized === '' || !isset($map[$normalized])) {
                throw new InvalidPayloadException('Campo TerFch inválido: ' . $alias);
            }

            $parts[] = '<' . $normalized . ' form="' . $map[$normalized] . '"/>';
        }

        return implode('', $parts);
    }

    /**
     * Builds a full ARTSOFT list query payload from known TerFch aliases.
     *
     * @param array<int, string> $aliases
     */
    public function buildListQueryPayload(
        array $aliases,
        string $query = 'TerFch|Autoinc=1:999999999',
        ?string $where = null
    ): string {
        $query = trim($query);

        if ($query === '') {
            throw new InvalidPayloadException('A expressão query não pode estar vazia.');
        }

        $fullQuery = $query;
        if ($where !== null && trim($where) !== '') {
            $fullQuery .= ' |? ' . trim($where);
        }

        $escapedQuery = htmlspecialchars($fullQuery, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $defcol = $this->buildDefcol($aliases);

        return '<i type="list" query="' . $escapedQuery . '"><defcol>' . $defcol . '</defcol></i>';
    }
}
