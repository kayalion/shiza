<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\RepositoryBuilderEntry as OrmRepositoryBuilderEntry;

/**
 * Data container of a repository
 */
class RepositoryBuilderEntry extends OrmRepositoryBuilderEntry {

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

    public function finishBuild($revision) {
        $this->setRevision($revision);
        $this->setDateBuilded(time());
    }

    /**
     * Gets the revision in a friendly format
     * @return string
     */
    public function getFriendlyRevision() {
        return substr($this->revision, 0, 7);
    }

}
