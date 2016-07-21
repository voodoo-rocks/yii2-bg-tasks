<?php
namespace vm\background;

use yii\base\Component;

abstract class AbstractWorker extends Component
{
    /** seconds to task expire */
    public $deadTime = 600;

    public $name;

    abstract public function getAvailableTasks();

    abstract public function cleanDeadTasks();

    abstract public function start($task);

    public function getName()
    {
        return $this->name;
    }
}