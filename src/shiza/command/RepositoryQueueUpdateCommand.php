<?php

namespace shiza\command;

use ride\cli\command\AbstractCommand;

use ride\library\orm\OrmManager;

use \Exception;

/**
 * Command to update repositories
 */
class RepositoryQueueUpdateCommand extends AbstractCommand {

    /**
     * Constructs a new command
     * @return null
     */
    public function initialize() {
        $this->setDescription('Queues an update for repositories');

        $this->addArgument('repository', 'Id of the repository to update', false);
    }

    /**
     * Invokes the command
     * @param ride\library\orm\OrmManager $orm
     * @param string $repository
     * @return null
     */
    public function invoke(OrmManager $orm, $repository = null) {
        $repositoryModel = $orm->getRepositoryModel();

        if ($repository) {
            if (is_numeric($repository)) {
                $repository = $repositoryModel->getById($repository);
            } else {
                $repository = $repositoryModel->getBy(array('filter' => array('slug' => $repository)));
            }

            if (!$repository) {
                throw new Exception('Repository not found');
            }

            $repositories = array($repository);
        } else {
            $repositories = $repositoryModel->find();
        }

        $repositoryModel->updateRepositories($repositories);
    }

}
