<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use shiza\manager\vcs\VcsManager;

use shiza\orm\entry\RepositoryEntry;

use \Exception;

/**
 * Abstract queue job to manage a web environment
 */
abstract class AbstractRepositoryQueueJob extends AbstractAuthQueueJob {

    const TITLE = 'Handle repository';

    protected $title;

    private $repositoryId;

    public function setRepositoryId($repositoryId) {
        $this->repositoryId = $repositoryId;
    }

    /**
     * Invokes the implementation of the job
     * @param QueueManager $queueManager Instance of the queue manager
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run(QueueManager $queueManager) {
        $this->initializeRun($queueManager);

        $this->title = static::TITLE;

        $this->updateQueueJob('Retrieving repository');

        if (!$this->repositoryId) {
            $this->logError($this->title, 'No repository set to the queue job');

            return;
        }

        $repository = $this->getRepository($this->repositoryId);
        if (!$repository) {
            $this->logError($this->title, 'No repository found with id ' . $this->repositoryId);

            return;
        }

        $managerId = $repository->getVcsManager();
        if (!$managerId) {
            $this->logError($this->title, 'No VCS manager set for repository ' . $repository->getUrl());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\vcs\\VcsManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($this->title, 'Could not initialize VCS manager ' . $managerId . ' for ' . $repository->getUrl(), $exception);

            throw $exception;
        }

        $this->performTask($repository, $manager);
    }

    protected function saveRepository(RepositoryEntry $repository) {
        $model = $this->orm->getRepositoryModel();
        $model->save($repository);
    }

    abstract protected function performTask(RepositoryEntry $repository, VcsManager $manager);

}
