<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\orm\entry\ProjectEnvironmentEntry;

use shiza\queue\WebBackupCreateQueueJob;
use shiza\queue\WebBackupRestoreQueueJob;
use shiza\queue\WebSetupQueueJob;
use shiza\queue\VarnishSetupQueueJob;

class ProjectEnvironmentModel extends GenericModel {

    protected function saveEntry($entry) {
        $isAliasChanged = $entry->isAliasChanged();
        $isSslChanged = $entry->isSslChanged();

        if ($isSslChanged) {
            if ($entry->getSslManager()) {
                $sslManager = $this->orm->getDependencyInjector()->get('shiza\\manager\\ssl\\SslManager', $entry->getSslManager());
                $sslManager->generateCertificate($entry);
            } else {
                $entry->setSslCertificate(null);
                $entry->setSslCertificateKey(null);
                $entry->setSslCertificateExpires(null);
                $entry->setIsSslActive(false);
            }
        }

        parent::saveEntry($entry);

        if ($entry->willSkipQueue()) {
            return;
        }

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');

        $project = $entry->getProject();

        $queueJob = new WebSetupQueueJob();
        $queueJob->setProjectId($project->getId());
        $queueJob->setProjectEnvironmentId($entry->getId());

        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $entry->setQueueJobStatus($queueJobStatus);

        if ($project->getVarnishServer() && $project->getVarnishMemory() && ($isAliasChanged || $isSslChanged)) {
            $queueJob = new VarnishSetupQueueJob();
            $queueJob->setProjectId($project->getId());

            $queueJobStatus = $queueDispatcher->queue($queueJob);

            $project->setVarnishQueueJobStatus($queueJobStatus);
        }

        parent::saveEntry($entry);
    }

    public function renewSslCertificates() {
        $query = $this->createQuery();
        $query->addcondition('{isActive} = %1% AND {sslManager} <> %2%', 1, '');

        $environments = $query->query();
        foreach ($environments as $environment) {
            $sslManager = $this->orm->getDependencyInjector()->get('shiza\\manager\\ssl\\SslManager', $entry->getSslManager());

            if ($sslManager->generateCertificate($entry)) {
                $this->save($entry);
            }
        }
    }

    public function createBackups() {
        $environments = $this->find();
        foreach ($environments as $environment) {
            if ($environment->isDeleted()) {
                continue;
            }

            $this->createBackup($environment);
        }
    }

    public function createBackup(ProjectEnvironmentEntry $environment, $name = null) {
        $queueJob = new WebBackupCreateQueueJob();
        $queueJob->setProjectId($environment->getProject()->getId());
        $queueJob->setProjectEnvironmentId($environment->getId());
        $queueJob->setBackupName($name);

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $environment->setQueueJobStatus($queueJobStatus);
        $environment->setSkipQueue();

        $this->save($environment);
    }

    public function restoreBackup(ProjectEnvironmentEntry $environment, $backup, ProjectEnvironmentEntry $destination = null) {
        $queueJob = new WebBackupRestoreQueueJob();
        $queueJob->setProjectId($environment->getProject()->getId());
        $queueJob->setProjectEnvironmentId($environment->getId());
        $queueJob->setBackupId($backup);
        if ($destination) {
            $queueJob->setDestinationId($destination->getId());
        }

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $environment->setQueueJobStatus($queueJobStatus);
        $environment->setSkipQueue();

        $this->save($environment);
    }

}
