<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\RepositoryEntry as OrmRepositoryEntry;

use ride\library\system\file\File;

/**
 * Data container of a repository
 */
class RepositoryEntry extends OrmRepositoryEntry {

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

    /**
     * Gets the data directory for the provided branch
     * @param \ride\library\system\file\File $directory Base data directory
     * @param string $branch Name of the branch
     * @return ride\library\system\file\File
     */
    public function getBranchDirectory(File $directory, $branch = null) {
        if ($branch == null) {
            $branch = 'HEAD';
        }

        return $directory->getChild($this->getId() . '/' . $branch);
    }

    public function getBuildersForBranch($branch) {
        $builders = $this->getBuilders();

        foreach ($builders as $index => $builder) {
            if ($builder->getBranch() != $branch) {
                unset($builders[$index]);
            }
        }

        return $builders;
    }

    public function getDeployersForBranch($branch) {
        $deployers = $this->getDeployers();

        foreach ($deployers as $index => $deployer) {
            if ($deployer->getBranch() != $branch) {
                unset($deployers[$index]);
            }
        }

        return $deployers;
    }

    public function isUrlChanged() {
        if (!$this->getId()) {
            return true;
        }

        if ($this->isValueLoaded('url') && $this->getLoadedValues('url') != $this->getUrl()) {
            return true;
        }

        return false;
    }

}
