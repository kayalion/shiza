<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\orm\entry\ProjectDatabaseEntry;

use shiza\queue\DatabaseBackupCreateQueueJob;
use shiza\queue\DatabaseBackupRestoreQueueJob;
use shiza\queue\DatabaseSetupQueueJob;

class ProjectDatabaseModel extends GenericModel {

    protected function saveEntry($entry) {
        $isNew = $entry->getId() ? false : true;

        parent::saveEntry($entry);

        if (!$isNew && !$entry->isDeleted()) {
            return;
        }

        $queueJob = new DatabaseSetupQueueJob();
        $queueJob->setProjectId($entry->getProject->getId());
        $queueJob->setProjectDatabaseId($entry->getId());

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $entry->setQueueJobStatus($queueJobStatus);

        parent::saveEntry($entry);
    }

    public function createBackups() {
        $databases = $this->find();
        foreach ($databases as $database) {
            if ($database->isDeleted()) {
                continue;
            }

            $this->createBackup($database);
        }
    }

    public function createBackup(ProjectDatabaseEntry $database, $name = null) {
        $queueJob = new DatabaseBackupCreateQueueJob();
        $queueJob->setProjectId($database->getProject->getId());
        $queueJob->setProjectDatabaseId($database->getId());
        $queueJob->setBackupName($name);

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $database->setQueueJobStatus($queueJobStatus);

        $this->save($database);
    }

    public function restoreBackup(ProjectDatabaseEntry $database, $backup, ProjectDatabaseEntry $destination = null) {
        $queueJob = new DatabaseBackupRestoreQueueJob();
        $queueJob->setProjectId($database->getProject->getId());
        $queueJob->setProjectDatabaseId($database->getId());
        $queueJob->setBackupId($backup);
        if ($destination) {
            $queueJob->setDestinationId($destination->getId());
        }

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $database->setQueueJobStatus($queueJobStatus);

        $this->save($database);
    }

}
