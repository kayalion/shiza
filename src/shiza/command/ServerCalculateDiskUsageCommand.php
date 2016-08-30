<?php

namespace shiza\command;

use ride\cli\command\AbstractCommand;

use ride\library\orm\OrmManager;

/**
 * Command to calculate disk usage
 */
class ServerCalculateDiskUsageCommand extends AbstractCommand {

    /**
     * Constructs a new command
     * @return null
     */
    public function initialize() {
        $this->setDescription('Calculate disk usages for all environments and databases');
    }

    /**
     * Invokes the command
     * @param ride\library\orm\OrmManager $orm
     * @return null
     */
    public function invoke(OrmManager $orm) {
        $serverModel = $orm->getServerModel();
        $serverModel->calculateDiskUsages();
    }

}
