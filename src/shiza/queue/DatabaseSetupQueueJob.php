<?php

namespace shiza\queue;

use shiza\manager\database\DatabaseManager;

use shiza\orm\entry\ProjectDatabaseEntry;

use \Exception;

/**
 * Queue job to push a project database to the server
 */
class DatabaseSetupQueueJob extends AbstractDatabaseQueueJob {

    const TITLE = 'Sync database settings';

    protected function performTask(ProjectDatabaseEntry $database, DatabaseManager $manager) {
        $server = $database->getServer();

        try {
            if (!$database->isDeleted()) {
                $this->updateQueueJob('Initializing database');

                $manager->addDatabase($database);

                $this->logSuccess($this->title, 'Initialiazed ' . $database . ' on ' . $server->getHost());
            } else {
                $this->updateQueueJob('Deleting database');

                $manager->deleteDatabase($database);

                $this->orm->getProjectDatabaseModel()->delete($database);

                $this->logSuccess($this->title, 'Deleted ' . $database . ' from ' . $server->getHost());
            }

            $this->serverService->getSshSystemForServer($server)->disconnect();
        } catch (\Exception $exception) {
            if (!$database->isDeleted()) {
                $this->logError($this->title, 'Could not initialize database ' . $database . ' on ' . $server->getHost(), $exception);
            } else {
                $this->logError($this->title, 'Could not delete database ' . $database . ' on ' . $server->getHost(), $exception);
            }

            $this->serverService->getSshSystemForServer($server)->disconnect();

            throw $exception;
        }
    }

}
