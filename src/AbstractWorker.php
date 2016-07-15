<?php
namespace vm\daemon;

use yii\base\Component;

abstract class AbstractWorker extends Component
{
    /** seconds to task expire */
    public $deadTime = 60 * 10;

    public $name;

    abstract public function getAvailableTasks();

    abstract public function cleanDeadTasks();

    abstract public function start($task);

    public function getName()
    {
        return $this->name;
    }
}