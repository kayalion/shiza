<?php

namespace shiza\queue;

use ride\library\system\file\File;
use ride\library\Timer;

use shiza\orm\entry\RepositoryEntry;

use shiza\manager\vcs\VcsManager;

use \Exception;

/**
 * Queue job to initialize a repository
 */
class RepositoryDeployQueueJob extends AbstractRepositoryQueueJob {

    const TITLE = 'Deploying repository';

    private $repositoryDeployerId;

    public function setRepositoryDeployerId($repositoryDeployerId) {
        $this->repositoryDeployerId = $repositoryDeployerId;
    }

    protected function performTask(RepositoryEntry $repository, VcsManager $vcsManager) {
        if (!$this->repositoryDeployerId) {
            $this->logError($this->title, 'No deployer set to the queue job');

            return;
        }

        $repositoryDeployerModel = $this->orm->getRepositoryDeployerModel();

        $repositoryDeployer = $repositoryDeployerModel->getById($this->repositoryDeployerId);
        if (!$repositoryDeployer) {
            $this->logError($this->title, 'No deployer found with id ' . $this->repositoryDeployerId);

            return;
        }

        $deployManagerId = $repositoryDeployer->getDeployManager();
        if (!$deployManagerId) {
            $this->logError($this->title, 'No deploy manager set for ' . $repositoryDeployer);

            return;
        }

        try {
            $deployManager = $this->dependencyInjector->get('shiza\\manager\\deploy\\DeployManager', $deployManagerId);
        } catch (Exception $exception) {
            $this->logError($this->title, 'Could not initialize deploy manager ' . $deployManagerId . ' for ' . $repositoryDeployer, $exception);

            throw $exception;
        }

        if (!$repository->isReady()) {
            $this->logError($this->title, 'Repository is not in ready state');

            throw new Exception('Repository is not ready');
        }

        $timer = new Timer();

        $repositoryDeployer->setWorking();
        $repositoryDeployerModel->save($repositoryDeployer);

        $files = array();
        $log = '';

        // check revision
        $revision = $vcsManager->getRevision($repository, $repositoryDeployer->getBranch());
        if ($revision) {
            $log .= "# Commit: " . $revision . "\n# Server: " . $repositoryDeployer->getDsn() . "\n";
        } else {
            $log .= "# No commits in the repository\n";
        }

        $workingDirectory = $vcsManager->getWorkingDirectory($repository, $repositoryDeployer->getBranch());

        // get changed files
        if ($revision && $repositoryDeployer->getRevision() != $revision) {
            $files = $vcsManager->getChangedFiles($repositoryDeployer);
        }

        // apply exclude filters
        $exclude = $repositoryDeployer->parseExclude();
        if ($exclude) {
            foreach ($files as $path => $action) {
                $file = $workingDirectory->getChild($path);
                $regex = $this->isPathExcluded($file, $path, $exclude);
                if ($regex === false) {
                    continue;
                }

                unset($files[$path]);

                $log .= '# [s] ' . $path . ' (' . $regex . ")\n";
            }
        }

        $exception = null;

        $output = $deployManager->deploy($repositoryDeployer, $workingDirectory, $files, $exception);
        if ($exception) {
            $revision = null;
        }

        // log deploy actions
        if ($output) {
            foreach ($output as $command => $commandOutput) {
                $log .= $command . "\n";

                if ($commandOutput === true || !$commandOutput) {
                    continue;
                }

                if (!is_array($commandOutput)) {
                    $commandOutput = array($commandOutput);
                }

                foreach ($commandOutput as $line) {
                    if (!substr($line, strlen($line) - 1, 1) != "\n") {
                        $line .= "\n";
                    }

                    $log .= "| " . $line;
                }
            }
        }

        $repositoryDeployer->finishDeploy($revision);

        if ($exception) {
            $repositoryDeployer->setError();
            $repositoryDeployerModel->save($repositoryDeployer);

            $log .= "# Deployment took " . $timer->getTime() . " seconds.";

            $this->logError($this->title, 'Deployment ' . $repositoryDeployer->getName() . ' for branch ' . $repositoryDeployer->getBranch() . ' ran into an error', $exception->getMessage() . "\n\n" . $log);
        } else {
            $repositoryDeployer->setReady();
            $repositoryDeployerModel->save($repositoryDeployer);

            $log .= "# Deployment took " . $timer->getTime() . " seconds.";

            // $flowModel = $orm->getDbudFlowModel();
            // $flowModel->onDeploy($this->server);

            $this->logSuccess($this->title, 'Deployment ' . $repositoryDeployer->getName() . ' finished for branch ' . $repositoryDeployer->getBranch(), $log);
        }
    }

    /**
     * Checks if the provided path matches a exclude regex
     * @param string $path
     * @param array $exceludes
     * @return boolean|string False when the path does not match, the regex if
     * it matches
     */
    protected function isPathExcluded(File $pathFile, $path, array $excludes) {
        foreach ($excludes as $exclude) {
            if (strpos($exclude, '**/') === 0) {
                $regex = substr($exclude, 3);
                $file = $pathFile->getName();
            } else {
                $regex = $exclude;
                $file = $path;
            }

            $regex = '/' . str_replace('/', '\\/', str_replace('*', '(.*)', $regex)) . '/';
            if (preg_match($regex, $file)) {
                return $exclude;
            }
        }

        return false;
    }

}
