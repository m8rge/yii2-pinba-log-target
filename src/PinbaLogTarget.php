<?php

namespace m8rge\pinba;

use yii\log\Logger;

class PinbaLogTarget extends \yii\log\Target
{
    /**
     * Pinba engine host name. Required
     * @var string
     */
    public $pinbaHost;

    /**
     * Application server name
     * @var string
     */
    public $serverName;

    /**
     * @var int
     */
    public $tagMaxLength = 200;

    /**
     * @var string[]
     */
    public $excludeScriptNames = [];

    /**
     * @var array
     */
    private $systemTags = [];

    public function init()
    {
        parent::init();

        $requestIdentifier = $this->getRequestIdentifier();

        $this->enabled = $this->enabled &&
            extension_loaded('pinba') &&
            function_exists('pinba_get_info') &&
            function_exists('pinba_timer_add') &&
            function_exists('pinba_server_name_set') &&
            function_exists('pinba_script_name_set') &&
            !$this->match($requestIdentifier, $this->excludeScriptNames);

        ini_set('pinba.enabled', $this->enabled);

        if (!$this->enabled) {
            return;
        }

        ini_set('pinba.server', $this->pinbaHost);
        if ($this->serverName) {
            pinba_server_name_set($this->serverName);
        }
        pinba_script_name_set($requestIdentifier);

        $this->setLevels(['profile']);

        $pinbaData = pinba_get_info();
        $this->systemTags = [
            '__hostname' => $pinbaData['hostname'],
            '__server_name' => $pinbaData['server_name']
        ];
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        $timers = [];
        foreach ($this->messages as $message) {
            [$token, $level, $category, $timestamp] = $message;
            if ($level === Logger::LEVEL_PROFILE_BEGIN) {
                $timers[md5($category . $token)] = $timestamp;
            } elseif ($level === Logger::LEVEL_PROFILE_END) {
                $deltaTime = $timestamp - $timers[md5($category . $token)];

                $this->pinbaTimerAdd($category, $token, $deltaTime);
            }
        }
    }

    protected function pinbaTimerAdd(string $category, string $token, float $time): void
    {
        $pinbaCategory = 'app';
        if (false !== $delimiterPos = strpos($category, '::')) {
            $pinbaCategory = substr($category, 0, $delimiterPos);
        }

        pinba_timer_add([
                'group' => $category,
                'category' => $pinbaCategory,
                'token' => mb_substr($token, 0, $this->tagMaxLength),
            ] + $this->systemTags, $time);
    }

    protected function getRequestIdentifier(): string
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uriArray = explode('?', $_SERVER['REQUEST_URI'], 2);
            $identifier = reset($uriArray);
        } else {
            $identifier = implode(' ', $_SERVER['argv']);
        }

        return $identifier;
    }

    protected function match(string $string, array $excludeMasks): bool
    {
        foreach ($excludeMasks as $excludeMask) {
            $haveAsterisk = substr_compare($excludeMask, '*', -1, 1);
            $match = $haveAsterisk && strpos($string[2], rtrim($excludeMask, '*')) === 0 ||
                !$haveAsterisk && $excludeMask === $string;

            if ($match){
                return true;
            }
        }

        return false;
    }
}