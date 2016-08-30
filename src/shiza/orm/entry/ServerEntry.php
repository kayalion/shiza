<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\ServerEntry as OrmServerEntry;

use shiza\service\ServerService;

class ServerEntry extends OrmServerEntry {

    private $isDatabasePasswordChanged;

    private $isVarnishSecretChanged;

    public function __toString() {
        return $this->getLabel();
    }

    public function getLabel() {
        return $this->getUsername() . '@' . $this->getHost() . ':' . $this->getPort();
    }

    public function setHost($host) {
        if ($this->getHost() != $host) {
            $this->setFingerprint(null);
        }

        parent::setHost($host);
    }

    public function setPort($port = 22) {
        if ($this->getPort() != $port) {
            $this->setFingerprint(null);
        }

        parent::setPort($port);
    }

    public function setUsername($username = 'root') {
        if ($this->getUsername() != $username) {
            $this->setFingerprint(null);
        }

        parent::setUsername($username);
    }

    public function setDatabasePassword($databasePassword) {
        if (!$databasePassword) {
            return;
        }

        if ($this->getDatabasePassword() != $databasePassword) {
            $this->setIsDatabasePasswordChanged(true);
        }

        parent::setDatabasePassword($databasePassword);
    }

    public function setIsDatabasePasswordChanged($isDatabasePasswordChanged) {
        $this->isDatabasePasswordChanged = $isDatabasePasswordChanged;
    }

    public function isDatabasePasswordChanged() {
        return $this->isDatabasePasswordChanged;
    }

    public function encryptDatabasePassword(ServerService $serverService) {
        $encryptedDatabasePassword = $serverService->encrypt($this->getDatabasePassword());

        $this->setDatabasePassword($encryptedDatabasePassword);
        $this->setIsDatabasePasswordChanged(false);
    }

    public function setVarnishSecret($varnishSecret) {
        if (!$varnishSecret) {
            return;
        }

        if ($this->getVarnishSecret() != $varnishSecret) {
            $this->setIsVarnishSecretChanged(true);
        }

        parent::setVarnishSecret($varnishSecret);
    }

    public function setIsVarnishSecretChanged($isVarnishSecretChanged) {
        $this->isVarnishSecretChanged = $isVarnishSecretChanged;
    }

    public function isVarnishSecretChanged() {
        return $this->isVarnishSecretChanged;
    }

    public function encryptVarnishSecret(ServerService $serverService) {
        $encryptedVarnishSecret = $serverService->encrypt($this->getVarnishSecret());

        $this->setVarnishSecret($encryptedVarnishSecret);
        $this->setIsVarnishSecretChanged(false);
    }

}
