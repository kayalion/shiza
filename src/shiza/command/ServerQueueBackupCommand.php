<?php

namespace shiza\command;

use ride\cli\command\AbstractCommand;

use ride\library\orm\OrmManager;

/**
 * Command to queue backups
 */
class ServerQueueBackupCommand extends AbstractCommand {

    /**
     * Constructs a new command
     * @return null
     */
    public function initialize() {
        $this->setDescription('Queue backups for all environments and databases');
    }

    /**
     * Invokes the command
     * @param ride\library\orm\OrmManager $orm
     * @return null
     */
    public function invoke(OrmManager $orm) {
        $databaseModel = $orm->getProjectDatabaseModel();
        $databaseModel->createBackups();

        $environmentModel = $orm->getProjectEnvironmentModel();
        $environmentModel->createBackups();
    }

}
