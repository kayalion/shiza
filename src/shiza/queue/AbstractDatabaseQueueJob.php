<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use shiza\manager\database\DatabaseManager;

use shiza\orm\entry\ProjectDatabaseEntry;

use \Exception;

/**
 * Abstract queue job to manage a database
 */
abstract class AbstractDatabaseQueueJob extends AbstractAuthQueueJob {

    const TITLE = 'Sync database settings';

    protected $title;

    private $projectId;

    private $projectDatabaseId;

    public function setProjectId($projectId) {
        $this->projectId = $projectId;
    }

    public function setProjectDatabaseId($projectDatabaseId) {
        $this->projectDatabaseId = $projectDatabaseId;
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

        if (!$this->projectDatabaseId) {
            $this->logError($this->title, 'No project database set to the queue job');

            return;
        }

        $this->updateQueueJob('Retrieving database');

        $databaseModel = $this->orm->getProjectDatabaseModel();
        $database = $databaseModel->getById($this->projectDatabaseId);
        if (!$database) {
            $this->logError($this->title, 'No project database found with id ' . $this->projectDatabaseId);

            return;
        }

        $this->updateQueueJob('Retrieving server information');

        $server = $database->getServer();
        if (!$server) {
            $this->logError($this->title, 'No server set for database ' . $database);

            return;
        }

        $this->updateQueueJob('Initializing database manager');

        $managerId = $server->getDatabaseManager();
        if (!$managerId) {
            $this->logError($this->title, 'No database manager set for server ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\database\\DatabaseManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize database manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->ensureUserExists($server, $project, $this->title);

        try {
            $this->performTask($database, $manager);

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (Exception $exception) {
            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

    abstract protected function performTask(ProjectDatabaseEntry $database, DatabaseManager $manager);

}
