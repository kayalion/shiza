<?php

namespace shiza\queue;

use shiza\orm\entry\RepositoryEntry;

use shiza\manager\vcs\VcsManager;

use \Exception;

/**
 * Queue job to initialize a repository
 */
class RepositoryInitQueueJob extends AbstractRepositoryQueueJob {

    const TITLE = 'Initialize repository';

    protected function performTask(RepositoryEntry $repository, VcsManager $manager) {
        try {
            $manager->initRepository($repository);

            $repository->setReady();

            $this->saveRepository($repository);

            $this->logSuccess($this->title, 'Initialized repository ' . $repository->getUrl());
        } catch (Exception $exception) {
            $repository->setError();

            $this->saveRepository($repository);

            $this->logError($this->title, 'Could not initialize repository ' . $repository->getUrl(), $exception);

            throw $exception;
        }
    }

}
