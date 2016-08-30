<?php

namespace shiza\queue;

use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use \Exception;

/**
 * Auth manager shortcuts for a queue job
 */
abstract class AbstractAuthQueueJob extends AbstractQueueJob {

    protected function ensureUserExists(ServerEntry $server, ProjectEntry $project, $title = 'Manage user') {
        $managerId = $server->getAuthManager();
        if (!$managerId) {
            return;
        }

        $this->updateQueueJob('Initializing auth manager');

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\auth\\AuthManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize auth manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->updateQueueJob('Initializing user account for ' . $project->getUsername());

        try {
            $manager->addUser($server, $project);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not add user ' . $project->getUsername() . ' on ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->updateQueueJob('Authorizing SSH keys for user ' . $project->getUsername());

        try {
            $manager->authorizeSshKeys($server, $project);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not setup the authorized keys for ' . $project->getUsername() . ' on ' . $server->getHost(), $exception);

            throw $exception;
        }
    }

    protected function deleteUser(ServerEntry $server, ProjectEntry $project, $title = 'Manage user') {
        $managerId = $server->getAuthManager();
        if (!$managerId) {
            return;
        }

        $this->updateQueueJob('Initializing auth manager');

        try {
            $manager = $this->dependencyInjector->get('shiza\\manager\\auth\\AuthManager', $managerId);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not initialize auth manager ' . $managerId . ' for ' . $server->getHost(), $exception);

            throw $exception;
        }

        $this->updateQueueJob('Deleting user account ' . $project->getUsername());

        try {
            $manager->deleteUser($server, $project);
        } catch (Exception $exception) {
            $this->logError($title, 'Could not delete user ' . $project->getUsername() . ' on ' . $server->getHost(), $exception);

            throw $exception;
        }
    }

}
