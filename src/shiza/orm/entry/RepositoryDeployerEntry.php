<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\RepositoryDeployerEntry as OrmRepositoryDeployerEntry;

/**
 * Data container of a repository
 */
class RepositoryDeployerEntry extends OrmRepositoryDeployerEntry {

    const STATUS_NEW = 'new';

    const STATUS_READY = 'ready';

    const STATUS_ERROR = 'error';

    const STATUS_WORKING = 'working';

    /**
     * Gets a string representation of this repository
     * @return string
     */
    public function __toString() {
        return $this->getName();
    }

    /**
     * Gets the DSN of the server connection
     * @return string
     */
    public function getDsn() {
        return $this->getDeployManager() . '://' . $this->getRemoteUsername() . ($this->getUseKey() ? '' : ':*****') . '@' . $this->getRemoteHost() . ':' . $this->getRemotePort() . $this->getRemotePath();
    }

    public function isNew() {
        return $this->getStatus() == self::STATUS_NEW;
    }

    public function isReady() {
        return $this->getStatus() == self::STATUS_READY;
    }

    public function isWorking() {
        return $this->getStatus() == self::STATUS_WORKING;
    }

    public function isError() {
        return $this->getStatus() == self::STATUS_ERROR;
    }

    public function setReady() {
        $this->setStatus(self::STATUS_READY);
    }

    public function setWorking() {
        $this->setStatus(self::STATUS_WORKING);
    }

    public function setError() {
        $this->setStatus(self::STATUS_ERROR);
    }

    /**
     * Gets the exclude
     * @return array
     */
    public function parseExclude() {
        $exclude = $this->getExclude();

        if (!$exclude) {
            return array();
        }

        return explode("\n", $exclude);
    }

    /**
     * Gets the commands
     * @return array
     */
    public function parseCommands() {
        $commands = $this->getScript();

        if (!$commands) {
            return array();
        }

        $commands = str_replace("\r", "", $commands);

        return explode("\n", $commands);
    }

    public function finishDeploy($revision) {
        if ($revision) {
            $this->setRevision($revision);
        }

        $this->setDateDeployed(time());
    }

    /**
     * Gets the revision in a friendly format
     * @return string
     */
    public function getFriendlyRevision() {
        return substr($this->revision, 0, 7);
    }

}
