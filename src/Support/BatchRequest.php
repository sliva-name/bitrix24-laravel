<?php

declare(strict_types=1);

namespace Leko\Bitrix24\Support;

use Leko\Bitrix24\Clients\BaseClient;

/**
 * Helper для пакетных запросов к Bitrix24 API
 */
class BatchRequest
{
    /**
     * Запросы для batch выполнения.
     *
     * @var array<string, array{method: string, params: array}>
     */
    protected array $commands = [];

    /**
     * Клиент для выполнения запросов.
     *
     * @var BaseClient
     */
    protected BaseClient $client;

    /**
     * Создать новый batch request.
     *
     * @param BaseClient $client Клиент API
     */
    public function __construct(BaseClient $client)
    {
        $this->client = $client;
    }

    /**
     * Добавить команду в batch.
     *
     * @param string $id Уникальный ID команды
     * @param string $method Метод API
     * @param array $params Параметры
     * @return self
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
     * Добавить несколько команд.
     *
     * @param array $commands Массив команд
     * @return self
     */
    public function addMany(array $commands): self
    {
        foreach ($commands as $id => $command) {
            $this->add((string) $id, $command['method'], $command['params'] ?? []);
        }

        return $this;
    }

    /**
     * Выполнить batch запрос.
     *
     * @return array
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

    /**
     * Получить количество команд.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * Очистить команды.
     *
     * @return self
     */
    public function clear(): self
    {
        $this->commands = [];
        return $this;
    }

    /**
     * Проверить наличие команд.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->commands === [];
    }
}
