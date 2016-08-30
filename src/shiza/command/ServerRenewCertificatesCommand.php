<?php

namespace shiza\command;

use ride\cli\command\AbstractCommand;

use ride\library\orm\OrmManager;

/**
 * Command to renew the certificates
 */
class ServerRenewCertificatesCommand extends AbstractCommand {

    /**
     * Constructs a new command
     * @return null
     */
    public function initialize() {
        $this->setDescription('Renew SSL certificates for all environments');
    }

    /**
     * Invokes the command
     * @param ride\library\orm\OrmManager $orm
     * @return null
     */
    public function invoke(OrmManager $orm) {
        $environmentModel = $orm->getProjectEnvironmentModel();
        $environmentModel->renewCertificates();
    }

}
