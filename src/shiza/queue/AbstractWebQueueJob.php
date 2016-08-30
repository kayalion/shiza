<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use shiza\manager\web\WebManager;

use shiza\orm\entry\ProjectEnvironmentEntry;

use \Exception;

/**
 * Abstract queue job to manage a web environment
 */
abstract class AbstractWebQueueJob extends AbstractAuthQueueJob {

    const TITLE = 'Sync web environment settings';

    protected $title;

    private $projectId;

    private $projectEnvironmentId;

    public function setProjectId($projectId) {
        $this->projectId = $projectId;
    }

    public function setProjectEnvironmentId($projectEnvironmentId) {
        $this->projectEnvironmentId = $projectEnvironmentId;
    }

    /**
     * Invokes the implementation of the job
     * @param QueueManager $queueManager Instance of the queue manager
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run(QueueManager $queueManager) {
        $this->initializeRun($queueManager);

        $this->title = static::TITLE;

        $this->updateQueueJob('Retrieving project');

        if ($this->projectId) {
            $project = $this->getProject($this->projectId);
        }

        if (!$this->projectEnvironmentId) {
            $this->logError($this->title, 'No project environment set to the queue job');

            return;
        }

        $this->updateQueueJob('Retrieving environment');

        $environmentModel = $this->orm->getProjectEnvironmentModel();
        $environment = $environmentModel->getById($this->projectEnvironmentId);
        if (!$environment) {
            $this->logError($this->title, 'No project environment found with id ' . $this->projectEnvironmentId);

            return;
        }

        $this->updateQueueJob('Retrieving server information');

        $server = $environment->getServer();
        if (!$server) {
            $this->logError($this->title, 'No server set for environment ' . $environment);

            return;
        }

        $this->updateQueueJob('Initializing web manager');

        $managerId = $server->getWebManager();
        if (!$managerId) {
            $this->logError($this->title, 'No web manager set for server ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\web\\WebManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($this->title, 'Could not initialize web manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->ensureUserExists($server, $project, $this->title);

        try {
            $this->performTask($environment, $manager);

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (Exception $exception) {
            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

    abstract protected function performTask(ProjectEnvironmentEntry $project, WebManager $manager);

}
