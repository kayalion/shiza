<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\queue\ProjectAuthorizeQueueJob;

abstract class AbstractAuthorizeModel extends GenericModel {

    protected function saveEntry($entry) {
        $willSave = $this->willSave($entry);
        $isNew = $entry->getId() ? false : true;

        parent::saveEntry($entry);

        if ($isNew || !$willSave) {
            return;
        }

        $projects = $this->getUsedProjects($entry);

        $this->authorizeProjects($projects);
    }

    protected function deleteEntry($entry) {
        $projects = $this->getUsedProjects($entry);

        parent::deleteEntry($entry);

        $this->authorizeProjects($projects);
    }

    abstract protected function getUsedProjects($entry);

    protected function authorizeProjects(array $projects) {
        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');

        foreach ($projects as $project) {
            $queueJob = new ProjectAuthorizeQueueJob();
            $queueJob->setProjectId($project->getId());

            $queueJobStatus = $queueDispatcher->queue($queueJob);

            $project->setCredentialsQueueJobStatus($queueJobStatus);
        }

        $this->orm->getProjectModel()->save($projects);
    }

}
