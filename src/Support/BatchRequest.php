<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support;

use Leko\Bitrix24\Clients\BaseClient;

/**
 * Пакетные запросы к Bitrix24 REST API.
 */
class BatchRequest
{
    /**
     * @var array<string, array{method: string, params: array<string, mixed>}>
     */
    private array $commands = [];

    public function __construct(
        private readonly BaseClient $client
    ) {
    }

    /**
     * @param array<string, mixed> $params
     */
    public function add(string $id, string $method, array $params = []): self
    {
        $this->commands[$id] = [
            'method' => $method,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * @param array<string|int, array{method: string, params?: array<string, mixed>}> $commands
     */
    public function addMany(array $commands): self
    {
        foreach ($commands as $id => $command) {
            $this->add((string) $id, $command['method'], $command['params'] ?? []);
        }

        return $this;
    }

    /**
     * @return array<mixed>
     */
    public function execute(): array
    {
        if ($this->commands === []) {
            return [];
        }

        $batchCommands = [];

        foreach ($this->commands as $id => $command) {
            $query = http_build_query($command['params']);
            $batchCommands[$id] = $query === ''
                ? $command['method']
                : $command['method'] . '?' . $query;
        }

        $result = $this->client->getServiceBuilder()->core
            ->call('batch', [
                'halt' => 0,
                'cmd' => $batchCommands,
            ])
            ->getResponseData()
            ->getResult();

        $this->commands = [];

        return is_array($result) ? $result : [];
    }

    public function count(): int
    {
        return count($this->commands);
    }

    public function clear(): self
    {
        $this->commands = [];

        return $this;
    }

    public function isEmpty(): bool
    {
        return $this->commands === [];
    }
}
