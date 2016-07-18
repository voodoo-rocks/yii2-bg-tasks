<?php
namespace vm\background\module\commands;

use vyants\daemon\DaemonController;

class WorkerController extends DaemonController
{
    /**
     * @var int Delay between task list checking
     * @default 5sec
     */
    public $sleep = 5;

    /**
     * @return array
     */
    protected function defineJobs()
    {
        sleep($this->sleep);
        return \Yii::$app->background->getAvailableTasks();
    }

    protected function doJob($task)
    {
        \Yii::$app->background->start($task);
        return true;
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