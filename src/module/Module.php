<?php
namespace vm\background\module;

use yii\base\BootstrapInterface;

class Module extends \yii\base\Module implements BootstrapInterface
{
    public $controllerNamespace = 'vm\background\module\controllers';

    public $workers = [];

    public function bootstrap($app)
    {
        $app->set('background', [
            'class' => 'vm\daemon\BackgroundTasks',
            'workers' => $this->workers
        ]);
    }
}