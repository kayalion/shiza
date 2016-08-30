<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\orm\entry\RepositoryBuilderEntry;

use shiza\queue\RepositoryBuildQueueJob;

class RepositoryBuilderModel extends GenericModel {

    public function build(RepositoryBuilderEntry $builder) {
        $queueJob = new RepositoryBuildQueueJob();
        $queueJob->setRepositoryId($builder->getRepository()->getId());
        $queueJob->setRepositoryBuilderId($builder->getId());

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $builder->setQueueJobStatus($queueJobStatus);

        $this->save($builder);
    }

}
