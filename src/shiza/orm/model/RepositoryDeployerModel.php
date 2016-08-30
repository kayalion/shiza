<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\orm\entry\RepositoryDeployerEntry;

use shiza\queue\RepositoryDeployQueueJob;

class RepositoryDeployerModel extends GenericModel {

    public function deploy(RepositoryDeployerEntry $deployer) {
        $queueJob = new RepositoryDeployQueueJob();
        $queueJob->setRepositoryId($deployer->getRepository()->getId());
        $queueJob->setRepositoryDeployerId($deployer->getId());

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $deployer->setQueueJobStatus($queueJobStatus);

        $this->save($deployer);
    }

}
