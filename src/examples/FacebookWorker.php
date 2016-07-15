<?php
namespace vm\background\examples;

use vm\background\AbstractWorker;

/**
 * For example like a queue we will use our SQL DB and Task table.
 * You can use any other ways to develop your queue.
 *
 * In our DB we have `task` table, that contain next fields:
 *
 * id : int  // pk
 * status : enum(`waiting`, `in-progress`, `finished`, `aborted`) default `waiting`
 * created_at: datetime
 * started_at: datetime
 * dead_time: datetime //Time to check task timeout
 * worker_name: string
 * description: string //Additional string, not required
 *
 */
class FacebookWorker extends AbstractWorker
{
    /* This is required system section for daemon console application */

    /**
     * This method will return to daemon available task for current worker.
     *
     * @return array
     */
    public function getAvailableTasks()
    {
        return [
            Task::find()
                ->andWhere(['status' => 'waiting'])
                ->andWhere(['worker_name' => $this->getName()])
                ->orderBy('created_at ASC')
                ->one()
        ];
    }

    /**
     * Check dead tasks for current worker, and break it if dead.
     */
    public function cleanDeadTasks()
    {
        $deadTasks = Task::find()
            ->andWhere(['status' => 'in-progress'])
            ->andWhere('dead_time > NOW()')
            ->all();

        foreach ($deadTasks as $deadTask) {
            $deadTask->status = 'aborted';
        }

        return true;
    }

    /**
     * This method will do main work.
     */
    public function start($task)
    {
        /* Start task and set timeout */
        $task->status    = 'in-progress';
        $task->dead_time = (new \DateTime('now +' . $this->deadTime . ' seconds'));
        $task->save();
        $task->refresh();

        /* Do work */
        $places = Places::find()->approved()->all();
        foreach ($places as $place){
            $place->importFromFacebook();
        }

        /* Finish task */
        $task->status    = 'finished';
        return true;
    }

    /* End system section */

    /**
     * Will be called from web app to create background task
     */
    public function startImport()
    {
        $task = new Task([
            'status' => 'waiting',
            'created_at' => (new \DateTime('now'))->format('Y-m-d H:i:s'),
        ]);

        return $task->save();
    }
}