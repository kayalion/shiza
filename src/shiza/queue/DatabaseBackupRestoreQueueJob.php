<?php

namespace shiza\queue;

use shiza\manager\database\DatabaseManager;

use shiza\orm\entry\ProjectDatabaseEntry;

use \Exception;

/**
 * Queue job to push a project database to the server
 */
class DatabaseBackupRestoreQueueJob extends AbstractDatabaseQueueJob {

    const TITLE = 'Restore database backup';

    private $backupId;

    private $destinationId;

    public function setBackupId($backupId) {
        $this->backupId = $backupId;
    }

    public function setDestinationId($destinationId) {
        $this->destinationId = $destinationId;
    }

    protected function performTask(ProjectDatabaseEntry $database, DatabaseManager $manager) {
        $this->updateQueueJob('Restoring backup');

        if (!$this->backupId) {
            $this->logError($this->title, 'No backup set to the queue job');

            return;
        }

        $destination = null;
        if ($this->destinationId) {
            $destination = $this->orm->getProjectDatabaseModel()->getById($this->destinationId);
        }

        $server = $database->getServer();

        try {
            $manager->restoreBackup($database, $this->backupId, $destination);

            $this->logSuccess($this->title, 'Restored backup ' . $this->backupId . ' for ' . $database . ($destination && $destination->getId() != $database->getId() ? ' to ' . $destination : '') . ' on ' . $server->getHost());

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (\Exception $exception) {
            $this->logError($this->title, 'Could not restore backup ' . $this->backupId . ' for database ' . $database . ' on ' . $server->getHost(), $exception);

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
