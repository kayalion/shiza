<?php

namespace shiza\queue;

use ride\library\queue\QueueManager;

use shiza\orm\entry\ProjectDatabaseEntry;
use shiza\orm\entry\ProjectEnvironmentEntry;
use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use \Exception;

/**
 * Queue job to delete a project
 */
class ProjectDeleteQueueJob extends AbstractAuthQueueJob {

    private $projectId;

    public function setProjectId($projectId) {
        $this->projectId = $projectId;
    }

    /**
     * Invokes the implementation of the job
     * @param QueueManager $queueManager Instance of the queue manager
     * @return integer|null A timestamp from which time this job should be
     * invoked again or null when the job is done
     */
    public function run(QueueManager $queueManager) {
        $this->initializeRun($queueManager);

        $title = 'Delete project';
        $this->servers = array();

        $this->updateQueueJob('Retrieving project');

        if (!$this->projectId) {
            $this->logError($title, 'No project set to the queue job');

            return;
        }

        $project = $this->getProject($this->projectId);
        if (!$project) {
            $this->logError($title, 'No project found with id ' . $this->projectId);

            return;
        }

        $title .= ' ' . $project->getCode();

        $environments = $project->getEnvironments();
        foreach ($environments as $environment) {
            $this->updateQueueJob('Deleting environment ' . $environment);

            $this->deleteEnvironment($environment, $title);
        }

        $databases = $project->getDatabases();
        foreach ($databases as $database) {
            $this->updateQueueJob('Deleting database ' . $database);

            $this->deleteDatabase($database, $title);
        }

        $this->updateQueueJob('Deleting crontab');

        $this->deleteCron($project, $title);

        $this->updateQueueJob('Deleting project');

        foreach ($this->servers as $server) {
            $this->deleteUser($server, $project, $title);

            $this->serverService->getSshSystemForServer($server)->disconnect();
        }

        $this->orm->getProjectModel()->delete($project);

        $this->logSuccess($title, $project->getName() . ' has been deleted');
    }

    private function deleteEnvironment(ProjectEnvironmentEntry $environment, $title) {
        $server = $environment->getServer();
        if (!$server) {
            $this->logError($title, 'No server set for environment ' . $environment);

            return;
        }

        $managerId = $server->getWebManager();
        if (!$managerId) {
            $this->logError($title, 'No web manager set for server ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\web\\WebManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize web manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->ensureUserExists($server, $environment->getProject(), $title);

        $environment->setIsDeleted(true);

        $manager->updateEnvironment($environment);

        $environmentModel = $this->orm->getProjectEnvironmentModel();
        $environmentModel->delete($environment);

        $this->addServer($server);
    }

    private function deleteDatabase(ProjectDatabaseEntry $database, $title) {
        $server = $database->getServer();
        if (!$server) {
            $this->logError($title, 'No server set for database ' . $database);

            return;
        }

        $managerId = $server->getDatabaseManager();
        if (!$managerId) {
            $this->logError($title, 'No database manager set for server ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\database\\DatabaseManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize database manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->ensureUserExists($server, $database->getProject(), $title);

        $database->setIsDeleted(true);

        $manager->deleteDatabase($database);

        $databaseModel = $this->orm->getProjectDatabaseModel();
        $databaseModel->delete($database);

        $this->addServer($server);
    }

    private function deleteCron(ProjectEntry $project, $title) {
        $server = $project->getCronServer();
        if (!$server) {
            $this->logError($title, 'No cron server set for project ' . $project);

            return;
        }

        $managerId = $server->getCronManager();
        if (!$managerId) {
            $this->logError($title, 'No cron manager set for ' . $server->getHost());

            return;
        }

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\cron\\CronManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize cron manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->ensureUserExists($server, $project, $title);

        $project->setCronTab('');

        $manager->updateCrontab($project);

        $this->addServer($server);
    }

    private function addServer(ServerEntry $server) {
        $this->servers[$server->getId()] = $server;
    }

}
