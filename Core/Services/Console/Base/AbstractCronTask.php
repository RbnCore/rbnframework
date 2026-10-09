<?php

declare(strict_types=1);

namespace Rbn\Framework\Core\Services\Console\Base;

use Rbn\Framework\Core\Base\BaseComponent;
use Rbn\Framework\Core\Support\Exceptions\CronTaskException;

/**
 * AbstractCronTask - Base class for all otonom tasks in the system. 🛰️🏛️🎻
 * 
 * RBN Framework: Now a BaseComponent actor.
 * Every task can now autonomously discover services and models.
 */
abstract class AbstractCronTask extends BaseComponent
{
    /**
     * Cron e-posta bildirim politikası: 'always' (success+failed), 'failure' (yalnız failed), 'never'.
     * Sık koşan görevler alt sınıfta 'failure' yapar; cron_jobs.params `notify_on` bunu ezer.
     */
    public const NOTIFY_ON = 'always';

    /**
     * @var string Output status of the task execution ('success', 'failed', 'skipped').
     */
    protected string $status = 'success';

    /**
     * @var string Output message of the task execution.
     */
    protected string $message = '';

    /**
     * @var array Detailed trace or dump data for the task.
     */
    protected array $traceData = [];

    /**
     * Main execution logic for the task: Dynamically routes to matching method by task_key. 🎯
     * 
     * @param array $params Optional parameters passed from the DB.
     * @return bool True if successful, false otherwise.
     */
    public function run(array $params = []): bool
    {
        if (empty($params['project_key'])) {
            $params['project_key'] = function_exists('project_key') ? project_key() : '';
        }

        $taskKey = (string) ($params['task_key'] ?? '');
        if (!empty($taskKey)) {
            $methodName = 'run' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $taskKey))) . 'Task';
            if (method_exists($this, $methodName)) {
                return $this->$methodName($params);
            }
        }

        throw CronTaskException::undefinedTaskKey($taskKey);
    }

    /**
     * Standardized Helper: Merges parameters, resolves Builder via TaskResolver, executes autopilot. 🎯
     */
    protected function runBuilder(string $builderName, array $defaultParams = [], array $params = []): bool
    {
        $mergedParams = array_merge($defaultParams, $params);
        $projectKey = (string) ($mergedParams['project_key'] ?? '');

        // 🎯 Çözümleme TaskResolver (TaskResolver::resolveProjectBuilder) üzerinden yapılır!
        $builder = $this->resolver('task')->resolveProjectBuilder($builderName, $projectKey);

        if (!$builder) {
            $this->setMessage("Hata: Görev Builder'ı [{$builderName}] bulunamadı.");
            return false;
        }

        $mergedParams['preset'] = $builderName;
        $result = $builder->autopilot($mergedParams);

        if (isset($result['message'])) {
            $this->setMessage((string) $result['message']);
        }

        if (isset($result['status'])) {
            $this->setStatus((string) $result['status']);
        }

        return (bool) ($result['success'] ?? false);
    }

    /**
     * Get the execution status ('success', 'failed', 'skipped').
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set the execution status.
     */
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    /**
     * Helper to mark task as skipped with a message.
     */
    public function skipped(string $message): void
    {
        $this->status = 'skipped';
        $this->message = $message;
    }

    /**
     * Get the result message.
     * 
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Helper to set output message.
     * 
     * @param string $message
     * @return void
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Add detailed trace or dump data (will be saved as JSON).
     * 
     * @param array $data
     * @return void
     */
    protected function setTrace(array $data): void
    {
        $this->traceData = $data;
    }

    /**
     * Get the trace data.
     * 
     * @return array
     */
    public function getTrace(): array
    {
        return $this->traceData;
    }
}

