<?php

namespace shiza\queue;

use shiza\orm\entry\RepositoryEntry;

use shiza\manager\vcs\VcsManager;

use \Exception;

/**
 * Queue job to update a repository
 */
class RepositoryUpdateQueueJob extends AbstractRepositoryQueueJob {

    const TITLE = 'Update repository';

    protected function performTask(RepositoryEntry $repository, VcsManager $manager) {
        $repository->setWorking();

        $this->saveRepository($repository);

        try {
            $revisions = $manager->updateRepository($repository);

            $repository->setReady();

            $this->saveRepository($repository);

            if ($revisions) {
                $body = '';
                foreach ($revisions as $branch => $revision) {
                    $body .= $branch . ': ' . $revision . "\n";
                }

                $this->logSuccess($this->title, 'Updated repository ' . $repository->getUrl(), $body);
            }

            $repositoryModel = $this->orm->getRepositoryModel();
            $repositoryModel->onRepositoryUpdate($repository, $revisions);
        } catch (Exception $exception) {
            $repository->setError();

            $this->saveRepository($repository);

            $this->logError($this->title, 'Could not update repository ' . $repository->getUrl(), $exception);

            throw $exception;
        }
    }

}
