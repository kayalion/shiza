<?php

namespace shiza\queue;

use ride\library\queue\job\AbstractQueueJob as RideAbstractQueueJob;
use ride\library\queue\QueueManager;

use \Exception;

/**
 * Queue job to setup a crontab
 */
abstract class AbstractQueueJob extends RideAbstractQueueJob {

    private $queueManager;

    private $project;

    private $repository;

    protected $system;

    protected $dependencyInjector;

    protected $orm;

    protected $serverService;

    protected function initializeRun(QueueManager $queueManager) {
        $this->system = $queueManager->getSystem();
        $this->queueManager = $queueManager;
        $this->dependencyInjector = $this->system->getDependencyInjector();
        $this->orm = $this->dependencyInjector->get('ride\\library\\orm\\OrmManager');
        $this->serverService = $this->dependencyInjector->get('shiza\\service\\ServerService');
        $this->vcsService = $this->dependencyInjector->get('shiza\\service\\VcsService');
    }

    protected function updateQueueJob($description) {
        $this->queueManager->updateStatus($this->getJobId(), $description);
    }

    protected function getRepository($repositoryId) {
        $repositoryModel = $this->orm->getRepositoryModel();

        $this->repository = $repositoryModel->getById($repositoryId);

        return $this->repository;
    }

    protected function getProject($projectId) {
        $projectModel = $this->orm->getProjectModel();

        $this->project = $projectModel->getById($projectId);

        return $this->project;
    }

    protected function logSuccess($title, $description, $body = null) {
        $this->logMessage('success', $title, $description, $body);
    }

    protected function logError($title, $description, $body = null) {
        $this->logMessage('error', $title, $description, $body);
    }

    protected function logMessage($type, $title, $description, $body = null) {
        $messageModel = $this->orm->getMessageModel();

        $message = $messageModel->createEntry();
        $message->setType($type);
        $message->setTitle($title);
        $message->setDescription($description);
        if ($body instanceof Exception) {
            $message->setExceptionToBody($body);
        } else {
            $message->setBody($body);
        }

        if ($this->project) {
            $message->setProject($this->project);
        }

        if ($this->repository) {
            $message->setRepository($this->repository);
        }

        $messageModel->save($message);
    }

}
