<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use shiza\exception\VarnishCompileException;

use shiza\manager\varnish\VarnishManager;

use shiza\orm\entry\ProjectEntry;

use \Exception;

/**
 * Abstract queue job to manage a varnish instance
 */
abstract class AbstractVarnishQueueJob extends AbstractQueueJob {

    const TITLE = 'Sync Varnish settings';

    protected $title;

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

        $this->title = static::TITLE;

        $this->updateQueueJob('Retrieving project');

        if (!$this->projectId) {
            $this->logError($this->title, 'No project set to the queue job');

            return;
        }

        $project = $this->getProject($this->projectId);
        if (!$project) {
            $this->setLogError($this->title, 'No project found with id ' . $this->projectId);

            return;
        }

        $this->updateQueueJob('Retrieving server information');

        $server = $project->getVarnishServer();
        if (!$server) {
            $this->logError($this->title, 'No varnish server set');

            return;
        }

        $this->updateQueueJob('Initializing varnish manager');

        $managerId = $server->getVarnishManager();
        if (!$managerId) {
            $this->logError($this->title, 'No varnish manager set for ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\varnish\\VarnishManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($this->title, 'Could not initialize varnish manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        try {
            $this->performTask($project, $manager);

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (Exception $exception) {
            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

    abstract protected function performTask(ProjectEntry $project, VarnishManager $manager);

}
