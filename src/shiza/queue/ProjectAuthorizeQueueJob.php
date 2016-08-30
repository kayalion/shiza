<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use shiza\orm\entry\ProjectDatabaseEntry;
use shiza\orm\entry\ProjectEnvironmentEntry;
use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use \Exception;

/**
 * Queue job to update the user credentials a project
 */
class ProjectAuthorizeQueueJob extends AbstractAuthQueueJob {

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

        $title = 'Update credentials';
        $this->servers = array();

        $this->updateQueueJob('Retrieving project');

        if (!$this->projectId) {
            $this->logError($title, 'No project set to the queue job');

            return;
        }

        $project = $this->getProject($this->projectId);
        if (!$project) {
            $this->logError($title, 'No project found with id ' . $this->projectId);

            return;
        }

        $this->updateQueueJob('Gathering server information');

        $environments = $project->getEnvironments();
        foreach ($environments as $environment) {
            if (!$environment->isDeleted() && $environment->getServer()) {
                $this->addServer($environment->getServer());
            }
        }

        $databases = $project->getDatabases();
        foreach ($databases as $database) {
            if (!$database->isDeleted() && $database->getServer()) {
                $this->addServer($database->getServer());
            }
        }

        if ($project->getCronServer()) {
            $this->addServer($project->getCronServer());
        }

        $this->updateQueueJob('Authorizing user');

        if ($this->servers) {
            foreach ($this->servers as $server) {
                $this->ensureUserExists($server, $project, $title);

                $this->serverService->getSshSystemForServer($server)->disconnect();
            }

            $this->logSuccess($title, 'Authorized user on the active servers');
        } else {
            $this->logSuccess($title, 'No servers in need of authorization');
        }
    }

    private function addServer(ServerEntry $server) {
        $this->servers[$server->getId()] = $server;
    }

}
