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
     * @var array
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
            $this->add($id, $command['method'], $command['params'] ?? []);
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
        if (empty($this->commands)) {
            return [];
        }

        $batchCommands = [];
        foreach ($this->commands as $id => $command) {
            $batchCommands[$id] = $command['method'] . '?' . http_build_query($command['params']);
        }

        $result = $this->client->isWebhook()
            ? $this->executeBatchWebhook($batchCommands)
            : $this->executeBatchOAuth($batchCommands);

        $this->commands = [];

        return $result;
    }

    /**
     * Выполнить batch через webhook.
     *
     * @param array $commands Команды
     * @return array
     */
    protected function executeBatchWebhook(array $commands): array
    {
        return [];
    }

    /**
     * Выполнить batch через OAuth.
     *
     * @param array $commands Команды
     * @return array
     */
    protected function executeBatchOAuth(array $commands): array
    {
        return [];
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
        return empty($this->commands);
    }
}

