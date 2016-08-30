<?php

namespace shiza\queue;

use ride\library\Timer;

use shiza\orm\entry\RepositoryEntry;

use shiza\manager\vcs\VcsManager;

use \Exception;

/**
 * Queue job to initialize a repository
 */
class RepositoryBuildQueueJob extends AbstractRepositoryQueueJob {

    const TITLE = 'Building repository';

    private $repositoryBuilderId;

    public function setRepositoryBuilderId($repositoryBuilderId) {
        $this->repositoryBuilderId = $repositoryBuilderId;
    }

    protected function performTask(RepositoryEntry $repository, VcsManager $vcsManager) {
        if (!$this->repositoryBuilderId) {
            $this->logError($this->title, 'No builder set to the queue job');

            return;
        }

        $repositoryBuilderModel = $this->orm->getRepositoryBuilderModel();

        $repositoryBuilder = $repositoryBuilderModel->getById($this->repositoryBuilderId);
        if (!$repositoryBuilder) {
            $this->logError($this->title, 'No builder found with id ' . $this->repositoryBuilderId);

            return;
        }

        $buildManagerId = $repositoryBuilder->getBuildManager();
        if (!$buildManagerId) {
            $this->logError($this->title, 'No build manager set for ' . $repositoryBuilder);

            return;
        }

        try {
            $buildManager = $this->dependencyInjector->get('shiza\\manager\\build\\BuildManager', $buildManagerId);
        } catch (Exception $exception) {
            $this->logError($this->title, 'Could not initialize build manager ' . $buildManagerId . ' for ' . $repositoryBuilder, $exception);

            throw $exception;
        }

        $timer = new Timer();

        $repositoryBuilder->setWorking();
        $repositoryBuilderModel->save($repositoryBuilder);

        $exception = null;
        $revision = $vcsManager->getRevision($repository, $repositoryBuilder->getBranch());

        $log = "# Commit: " . $revision . "\n";
        $log .= $buildManager->runScript($vcsManager, $repositoryBuilder, $revision, $exception);

        $repositoryBuilder->finishBuild($revision);

        if ($exception) {
            $repositoryBuilder->setError();
            $repositoryBuilderModel->save($repositoryBuilder);

            $log .= "# Builder took " . $timer->getTime() . " seconds.";

            $this->logError($this->title, 'Builder ' . $repositoryBuilder->getName() . ' for branch ' . $repositoryBuilder->getBranch() . ' ran into an error', $exception->getMessage() . "\n\n" . $log);
        } else {
            $repositoryBuilder->setReady();
            $repositoryBuilderModel->save($repositoryBuilder);

            // $flowModel = $orm->getDbudFlowModel();
            // $flowModel->onBuild($this->builder);

            $log .= "# Builder took " . $timer->getTime() . " seconds.";

            $this->logSuccess($this->title, 'Builder ' . $repositoryBuilder->getName() . ' finished for branch ' . $repositoryBuilder->getBranch(), $log);
        }
    }

}
