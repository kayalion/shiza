<?php

namespace shiza\orm\model;

use ride\library\orm\entry\EntryProxy;
use ride\library\orm\model\GenericModel;
use ride\library\validation\exception\ValidationException;
use ride\library\validation\ValidationError;

use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use shiza\queue\CronQueueJob;
use shiza\queue\ProjectAuthorizeQueueJob;
use shiza\queue\ProjectDeleteQueueJob;
use shiza\queue\VarnishDeleteQueueJob;
use shiza\queue\VarnishRestartQueueJob;
use shiza\queue\VarnishReloadQueueJob;
use shiza\queue\VarnishSetupQueueJob;

class ProjectModel extends GenericModel {

    public function getUsedVarnishMemory(ServerEntry $server, ProjectEntry $project = null) {
        $query = $this->createQuery();
        $query->setFields('SUM({varnishMemory}) AS usedMemory');
        $query->addCondition('{varnishServer} = %1%', $server->getId());
        if ($project && $project->getId()) {
            $query->addCondition('{id} <> %1%', $project->getId());
        }

        $result = $query->queryFirst();

        return $result->usedMemory;
    }

    /**
     * Validates an entry of this model
     * @param mixed $entry Entry instance or entry properties of this model
     * @return null
     * @throws \ride\library\orm\exception\OrmException when the validation
     * factory is not set
     * @throws \ride\library\validation\exception\ValidationException when one
     * of the fields is not valid
     */
    public function validate($entry) {
        try {
            parent::validate($entry);
            $exception = new ValidationException('Validation errors occured in ' . $this->getName());
        } catch (ValidationException $e) {
            $exception = $e;
        }

        $isProxy = $entry instanceof EntryProxy;
        if (($isProxy && $entry->isFieldSet('varnishMemory')) || (!$isProxy && $entry->getVarnishMemory())) {
            $server = $entry->getVarnishServer();
            if ($server) {
                $projectMemory = $entry->getVarnishMemory();
                $serverMemory = $server->getVarnishMemory();
                $usedMemory = $this->getUsedVarnishMemory($server, $entry);

                if ($serverMemory < ($usedMemory + $projectMemory)) {
                    $error = new ValidationError('error.validation.varnish.memory', 'Cannot assign %requested% Mb of memory: server uses %used% Mb out of %total% Mb', array(
                        'requested' => $projectMemory,
                        'used' => $usedMemory,
                        'total' => $serverMemory,
                        'free' => $serverMemory - $usedMemory,
                    ));

                    $exception->addError('varnishMemory', $error);
                }
            }
        }

        if ($exception->hasErrors()) {
            throw $exception;
        }
    }

    protected function saveEntry($entry) {
        $serverService = $this->orm->getDependencyInjector()->get('shiza\\service\\ServerService');

        if (!$entry->getPassword()) {
            $entry->generatePassword($serverService);
        }
        if (!$entry->getVarnishSecret()) {
            $entry->generateVarnishSecret($serverService);
        }

        $isNew = $entry->getId() ? false : true;
        $isCronChanged = $entry->isCronChanged();
        $isVarnishChanged = $entry->isVarnishChanged();
        $isCredentialsChanged = !$isNew && $entry->isCredentialsChanged();

        parent::saveEntry($entry);

        if ($entry->willSkipQueue()) {
            return;
        }

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');

        if ($entry->isDeleted()) {
            $queueJob = new ProjectDeleteQueueJob();
            $queueJob->setProjectId($entry->getId());

            $queueDispatcher->queue($queueJob);
        } else {
            if ($isCredentialsChanged) {
                $queueJob = new ProjectAuthorizeQueueJob();
                $queueJob->setProjectId($entry->getId());

                $queueJobStatus = $queueDispatcher->queue($queueJob);

                $entry->setCredentialsQueueJobStatus($queueJobStatus);
            }

            if ($isCronChanged) {
                $queueJob = new CronQueueJob();
                $queueJob->setProjectId($entry->getId());

                $queueJobStatus = $queueDispatcher->queue($queueJob);

                $entry->setCronQueueJobStatus($queueJobStatus);
            }

            if ($isVarnishChanged) {
                switch ($isVarnishChanged) {
                    case ProjectEntry::VARNISH_SETUP:
                        $queueJob = new VarnishSetupQueueJob();

                        break;
                    case ProjectEntry::VARNISH_RESTART:
                        $queueJob = new VarnishRestartQueueJob();

                        break;
                    case ProjectEntry::VARNISH_RELOAD:
                        $queueJob = new VarnishReloadQueueJob();

                        break;
                    case ProjectEntry::VARNISH_DELETE:
                        $queueJob = new VarnishDeleteQueueJob();

                        break;
                }

                $queueJob->setProjectId($entry->getId());

                $queueJobStatus = $queueDispatcher->queue($queueJob);

                $entry->setVarnishQueueJobStatus($queueJobStatus);
            }

            if ($isCredentialsChanged || $isCronChanged || $isVarnishChanged) {
                parent::saveEntry($entry);
            }
        }
    }

    public function restartVarnish(ProjectEntry $project) {
        $queueJob = new VarnishRestartQueueJob();
        $queueJob->setProjectId($project->getId());

        $queueDispatcher = $this->orm->getDependencyInjector()->get('ride\\library\\queue\\dispatcher\\QueueDispatcher');
        $queueJobStatus = $queueDispatcher->queue($queueJob);

        $project->setVarnishQueueJobStatus($queueJobStatus);
        $project->setSkipQueue();

        $this->save($project);
    }

    /**
     * Gets the global crontab of all projects
     * @param \shiza\orm\entry\ServerEntry $server Set to filter on server
     * @return string
     */
    public function getCronTab(ServerEntry $server = null) {
        $crontab = '';

        $query = $this->createQuery();
        $query->setFields('{id}, {code}, {name}, {cronTab}');
        $query->addOrderBy('{code} ASC');

        if ($server) {
            $query->addCondition('{cronServer} = %1%', $server->getId());
        }

        $projects = $query->query();
        foreach ($projects as $project) {
            if (!$project->getCronTab()) {
                continue;
            }

            $crontab .= '# ' . $project->getCode() . ': ' . $project->getName() . PHP_EOL;
            $crontab .= $project->getCronTab() . PHP_EOL . PHP_EOL;
        }

        return $crontab;
    }

}
