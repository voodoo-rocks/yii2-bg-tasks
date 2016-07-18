<?php
namespace vm\background\module;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface
{
    public $controllerNamespace = 'app';

    public $workers = [];

    public function init()
    {
        $this->controllerMap['watcher'] = [
            'class' => 'vm\background\module\commands\WatcherController',
        ];

        $this->controllerMap['worker'] = [
            'class' => 'vm\background\module\commands\WorkerController',
        ];
    }

    public function bootstrap($app)
    {
        $app->set('background', [
            'class' => 'vm\background\BackgroundTasks',
            'workers' => $this->workers
        ]);
    }
}