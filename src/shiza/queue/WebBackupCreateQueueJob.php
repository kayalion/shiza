<?php

namespace shiza\queue;

use shiza\manager\web\WebManager;

use shiza\orm\entry\ProjectEnvironmentEntry;

use \Exception;

/**
 * Queue job to create a backup of a web environment
 */
class WebBackupCreateQueueJob extends AbstractWebQueueJob {

    const TITLE = 'Create web environment backup';

    private $backupName;

    public function setBackupName($backupName) {
        $this->backupName = $backupName;
    }

    protected function performTask(ProjectEnvironmentEntry $environment, WebManager $manager) {
        $this->updateQueueJob('Creating backup');

        $server = $environment->getServer();

        try {
            $manager->createBackup($environment, $this->backupName);

            $this->logSuccess($this->title, 'Created backup for environment ' . $environment . ' on ' . $server->getHost());

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (\Exception $exception) {
            $this->logError($this->title, 'Could not create backup for environment ' . $environment . ' on ' . $server->getHost(), $exception);

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
