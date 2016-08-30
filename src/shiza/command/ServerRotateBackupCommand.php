<?php

namespace shiza\command;

use ride\cli\command\AbstractCommand;

use ride\library\orm\OrmManager;

/**
 * Command to rotate backups
 */
class ServerRotateBackupCommand extends AbstractCommand {

    /**
     * Constructs a new command
     * @return null
     */
    public function initialize() {
        $this->setDescription('Rotates the daily backups on all servers');
    }

    /**
     * Invokes the command
     * @param ride\library\orm\OrmManager $orm
     * @return null
     */
    public function invoke(OrmManager $orm) {
        $serverModel = $orm->getServerModel();
        $serverModel->rotateBackups();
    }

}
