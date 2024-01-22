<?php

declare(strict_types=1);

namespace CMS\PhpBackup\Api;

use CMS\PhpBackup\Core\AppConfig;
use CMS\PhpBackup\Exceptions\FileNotFoundException;

if (!defined('ABS_PATH')) {
    return;
}

/**
 * Class ActionHandler.
 *
 * The ActionHandler class is responsible for managing and executing actions within the application.
 * Actions are registered with a name and associated callback function, allowing for dynamic execution
 * of predefined operations.
 */
class ActionHandler
{
    private const CONFIG_TYPE = 'actions';
    private static ?ActionHandler $instance = null;
    private array $actions = [];
    private AppConfig $config;

    /**
     * ActionHandler constructor.
     *
     * Initializes the ActionHandler instance with the provided AppConfig.
     *
     * @param AppConfig $appConfig the application configuration
     */
    private function __construct(AppConfig $appConfig)
    {
        $this->config = $appConfig;

        try {
            $this->actions = $this->config->readTempData(self::CONFIG_TYPE);
        } catch (FileNotFoundException $th) {
            $this->actions = [];
        }
    }

    /**
     * Retrieves or creates an instance of the ActionHandler.
     *
     * @param AppConfig $appConfig the application configuration
     *
     * @return ActionHandler the ActionHandler instance
     */
    public static function getInstance(AppConfig $appConfig): self
    {
        if (null === self::$instance) {
            self::$instance = new self($appConfig);
        }

        return self::$instance;
    }

    /**
     * Registers an action with a unique name and associated callback function.
     *
     * @param string $actionName the name of the action
     * @param callable $callback the callback function to be executed when the action is triggered
     */
    public function registerAction(string $actionName, callable $callback, bool $requiresConfig = false): void
    {
        $this->actions[$actionName] = ['callback' => $callback, 'config' => $requiresConfig];
        $this->config->saveTempData(self::CONFIG_TYPE, $this->actions);
    }

    /**
     * Unregister a previously registered action by its name.
     *
     * @param string $actionName the name of the action to be unregistered
     */
    public function unregisterAction(string $actionName): void
    {
        if ($this->isActionRegistered($actionName)) {
            unset($this->actions[$actionName]);
            $this->config->saveTempData(self::CONFIG_TYPE, $this->actions);
        }
    }

    /**
     * Executes a registered action with optional parameters.
     *
     * @param string $actionName the name of the action to be executed
     * @param string $nonce the nonce associated with the action
     * @param array $params optional parameters to be passed to the action callback
     *
     * @throws \InvalidArgumentException if the specified action is not registered
     */
    public function executeAction(string $actionName, string $nonce, array $params = []): void
    {
        if (!$this->isActionRegistered($actionName)) {
            throw new \InvalidArgumentException("Action '{$actionName}' is not registered.");
        }

        $this->validateNonce($actionName, $nonce);

        $callback = $this->actions[$actionName]['callback'];
        if ($this->actions[$actionName]['config']) {
            $params['config'] = $this->config;
        }
        call_user_func_array($callback, $params);
    }

    /**
     * Generates a unique nonce for the specified action.
     *
     * @param string $actionName the name of the action
     *
     * @return string the generated nonce
     */
    public static function generateNonce(string $actionName): string
    {
        return md5($actionName . self::class);
    }

    /**
     * Checks if a given action is registered.
     *
     * @param string $actionName the name of the action
     *
     * @return bool true if the action is registered; otherwise, false
     */
    private function isActionRegistered(string $actionName): bool
    {
        return isset($this->actions[$actionName]);
    }

    /**
     * Validates the provided nonce against the expected nonce for the specified action.
     *
     * @param string $actionName the name of the action
     * @param string $nonce the nonce to be validated
     *
     * @throws \RuntimeException if the provided nonce is invalid
     */
    private function validateNonce(string $actionName, string $nonce): void
    {
        $expectedNonce = $this->generateNonce($actionName);

        if (!hash_equals($expectedNonce, $nonce)) {
            throw new \RuntimeException("Invalid nonce for action '{$actionName}'.");
        }
    }
}
