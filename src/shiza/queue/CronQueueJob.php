<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use \Exception;

/**
 * Queue job to setup a crontab
 */
class CronQueueJob extends AbstractAuthQueueJob {

    private $projectId;

    public function setProjectId($projectId) {
        $this->projectId = $projectId;
    }

    /**
     * Invokes the implementation of the job
     * @param QueueManager $queueManager Instance of the queue manager
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run(QueueManager $queueManager) {
        $this->initializeRun($queueManager);

        $title = 'Update crontab';

        $this->updateQueueJob('Retrieving project');

        if (!$this->projectId) {
            $this->logError($title, 'No project set to the queue job');

            return;
        }

        $project = $this->getProject($this->projectId);
        if (!$project) {
            $this->setLogError($title, 'No project found with id ' . $this->projectId);

            return;
        }

        $this->updateQueueJob('Retrieving server information');

        $server = $project->getCronServer();
        if (!$server) {
            $this->logError($title, 'No cron server set');

            return;
        }

        $this->updateQueueJob('Initializing cron manager');

        $managerId = $server->getCronManager();
        if (!$managerId) {
            $this->logError($title, 'No cron manager set for ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\cron\\CronManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize cron manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->ensureUserExists($server, $project, $title);

        $this->updateQueueJob('Updating crontab');

        try {
            $manager->updateCrontab($project);

            if ($project->getCronTab()) {
                $this->logSuccess($title, 'Updated crontab on ' . $server->getHost());
            } else {
                $this->logSuccess($title, 'Deleted crontab on ' . $server->getHost());
            }

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (Exception $exception) {
            $this->logError($title, 'Could not update the crontab on ' . $server->getHost(), $exception);

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
