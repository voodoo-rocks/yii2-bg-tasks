<?php
namespace vm\background\module\commands;

class WatcherController extends \vyants\daemon\controllers\WatcherDaemonController
{
    public $sleep = 10;

    protected function getDaemonsList()
    {
        return [
            ['className' => 'WorkerController', 'enabled' => true]
        ];
    }

    /**
     * Job processing body
     *
     * @param $job array
     * @return boolean
     */
    protected function doJob($job)
    {
        $pidfile = \Yii::getAlias($this->pidDir) . DIRECTORY_SEPARATOR . $job['className'];

        \Yii::trace('Check daemon '.$job['className']);
        if (file_exists($pidfile)) {
            $pid = file_get_contents($pidfile);
            if ($this->isProcessRunning($pid)) {
                if ($job['enabled']) {
                    \Yii::trace('Daemon ' . $job['className']. ' running and working fine');
                    return true;
                } else {
                    \Yii::warning('Daemon ' . $job['className']. ' running, but disabled in config. Send SIGTERM signal.');
                    if(isset($job['hardKill']) && $job['hardKill']){
                        posix_kill($pid, SIGKILL);
                    } else {
                        posix_kill($pid, SIGTERM);
                    }
                    return true;
                }
            }
        }
        \Yii::trace('Daemon pid not found.');
        if($job['enabled']) {
            \Yii::trace('Try to run daemon ' . $job['className']. '.');
            $command_name = $this->getCommandNameBy($job['className']);
            //flush log before fork
            \Yii::$app->getLog()->getLogger()->flush(true);
            //run daemon
            $pid = pcntl_fork();
            if ($pid == -1) {
                $this->halt(self::EXIT_CODE_ERROR, 'pcntl_fork() returned error');
            } elseif (!$pid) {
                $this->initLogger();
                \Yii::trace('Daemon '.$job['className'] .' is running.');
            } else {
                $this->halt(
                    (0 === \Yii::$app->runAction("$command_name", ['demonize' => 1])
                        ? self::EXIT_CODE_NORMAL
                        : self::EXIT_CODE_ERROR
                    )
                );
            }

        }
        \Yii::trace('Daemon '.$job['className'] .' is checked.');

        return true;
    }

    protected function getCommandNameBy($className) {
        $command = strtolower(
            preg_replace_callback('/(?<!^)(?<![A-Z])[A-Z]{1}/',
                function ($matches) {
                    return '-' . $matches[0];
                },
                str_replace('Controller', '', $className)
            )
        );

        if(!empty($this->daemonFolder)) {
            $command = $this->daemonFolder . DIRECTORY_SEPARATOR. $command;
        }

        return 'daemon' . DIRECTORY_SEPARATOR  . $command . DIRECTORY_SEPARATOR . 'index';
    }

    protected function initLogger()
    {

        $targets = \Yii::$app->getLog()->targets;
        foreach ($targets as $name => $target) {
            $target->enabled = false;
        }
        $config = [
            'levels' => ['error', 'warning', 'trace', 'info'],
            'logFile' => \Yii::getAlias($this->logDir) . DIRECTORY_SEPARATOR . $this->shortClassName() . '.log',
            'logVars'=>[], // Don't log all variables
            'exportInterval'=>1, // Write each message to disk
            'except' => [
                'yii\db\*', // Don't include messages from db
            ],
        ];
        $targets['daemon'] = new \yii\log\FileTarget($config);
        \Yii::$app->getLog()->targets = $targets;
        \Yii::$app->getLog()->init();
        // Flush each message
        \Yii::$app->getLog()->flushInterval = 1;
    }
}