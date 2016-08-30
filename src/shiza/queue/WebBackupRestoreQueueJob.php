<?php

namespace shiza\queue;

use shiza\manager\web\WebManager;

use shiza\orm\entry\ProjectEnvironmentEntry;

use \Exception;

/**
 * Queue job to restore a web environment backup
 */
class WebBackupRestoreQueueJob extends AbstractWebQueueJob {

    const TITLE = 'Restore web environment backup';

    private $backupId;

    private $destinationId;

    public function setBackupId($backupId) {
        $this->backupId = $backupId;
    }

    public function setDestinationId($destinationId) {
        $this->destinationId = $destinationId;
    }

    protected function performTask(ProjectEnvironmentEntry $environment, WebManager $manager) {
        $this->updateQueueJob('Restoring backup');

        if (!$this->backupId) {
            $this->logError($this->title, 'No backup set to the queue job');

            return;
        }

        $destination = null;
        if ($this->destinationId) {
            $destination = $this->orm->getProjectEnvironmentModel()->getById($this->destinationId);
        }

        $server = $environment->getServer();

        try {
            $manager->restoreBackup($environment, $this->backupId, $destination);

            $this->logSuccess($this->title, 'Restored backup ' . $this->backupId . ' for ' . $environment . ($destination && $destination->getId() != $environment->getId() ? ' to ' . $destination : '') . ' on ' . $server->getHost());

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (\Exception $exception) {
            $this->logError($this->title, 'Could not restore backup ' . $this->backupId . ' for environment ' . $environment . ' on ' . $server->getHost(), $exception);

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
