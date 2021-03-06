<?php
namespace vm\background;

use yii\base\Component;
use yii\helpers\ArrayHelper;

class BackgroundTasks extends Component
{
    public $workers = [];

    public $workerObjects = [];

    public function getAvailableTasks()
    {
        $tasks = [];
        /** @var AbstractWorker $worker */
        foreach ($this->workers as $name => $class) {

            if (!$worker = $this->getWorker($name)) {
                \Yii::info('Worker !'.  $name . ' not found');
                continue;
            }

            $worker->cleanDeadTasks();
            foreach ($worker->getAvailableTasks() as $task) {
                $tasks[] = [
                    'worker' => $worker->getName(),
                    'task'   => $task
                ];
            }
        }

        return $tasks;
    }

    public function start($taskData)
    {

        $worker = $this->getWorker($taskData['worker']);
        if (!$worker) {
            \Yii::error('Worker '. $taskData['worker'] . ' not found');
        }
        $worker->start($taskData['task']);
    }

    public function __get($name)
    {
        return $this->canGetProperty($name) ? parent::__get($name) : $this->getWorker($name);
    }

    /**
     * @param $name
     * @return AbstractWorker
     */
    protected function getWorker($name)
    {
        $class = ArrayHelper::getValue($this->workers, $name);
        $worker = \Yii::createObject($class);
        $worker->name = $name;
        return $worker;
    }
}