<?php

namespace shiza\queue;

use shiza\manager\database\DatabaseManager;

use shiza\orm\entry\ProjectDatabaseEntry;

use \Exception;

/**
 * Queue job to push a project database to the server
 */
class DatabaseBackupCreateQueueJob extends AbstractDatabaseQueueJob {

    const TITLE = 'Create database backup';

    private $backupName;

    public function setBackupName($backupName) {
        $this->backupName = $backupName;
    }

    protected function performTask(ProjectDatabaseEntry $database, DatabaseManager $manager) {
        $this->updateQueueJob('Creating backup');

        $server = $database->getServer();

        try {
            $manager->createBackup($database, $this->backupName);

            $this->logSuccess($this->title, 'Created backup for database ' . $database . ' on ' . $server->getHost());

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (\Exception $exception) {
            $this->logError($this->title, 'Could not create backup for database ' . $database . ' on ' . $server->getHost(), $exception);

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
