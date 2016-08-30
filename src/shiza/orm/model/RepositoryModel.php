<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\orm\entry\RepositoryEntry;

use shiza\queue\RepositoryInitQueueJob;
use shiza\queue\RepositoryUpdateQueueJob;

class RepositoryModel extends GenericModel {

    protected function saveEntry($entry) {
        $isUrlChanged = $entry->isUrlChanged();

        parent::saveEntry($entry);

        if (!$isUrlChanged) {
            return;
        }

        $queueJob = new RepositoryInitQueueJob();
        $queueJob->setRepositoryId($entry->getId());

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueDispatcher->queue($queueJob);
    }

    public function updateRepositories(array $repositories) {
        foreach ($repositories as $repository) {
            $this->updateRepository($repository);
        }
    }

    public function updateRepository(RepositoryEntry $repository) {
        $queueJob = new RepositoryUpdateQueueJob();
        $queueJob->setRepositoryId($repository->getId());

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueDispatcher->queue($queueJob);
    }

    public function onRepositoryUpdate(RepositoryEntry $repository, $revisions) {
        if (!$revisions) {
            return;
        }

        $repositoryBuilderModel = $this->orm->getRepositoryBuilderModel();
        $repositoryDeployerModel = $this->orm->getRepositoryDeployerModel();

        foreach ($revisions as $branch => $revision) {
            $builders = $repository->getBuildersForBranch($branch);
            foreach ($builders as $builder) {
                if (!$builder->isAutomatic() || $builder->getRevision() == $revision) {
                    continue;
                }

                $repositoryBuilderModel->build($builder);
            }

            $deployers = $repository->getDeployersForBranch($branch);
            foreach ($deployers as $deployer) {
                if (!$deployer->isAutomatic() || $deployer->getRevision() == $revision) {
                    continue;
                }

                $repositoryDeployerModel->deploy($deployer);
            }
        }
    }

}
