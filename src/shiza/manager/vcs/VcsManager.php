<?php

namespace shiza\manager\vcs;

use shiza\orm\entry\RepositoryDeployerEntry;
use shiza\orm\entry\RepositoryEntry;

/**
 * Interface for the VCS manager of a repository
 */
interface VcsManager {

    /**
     * Gets the commands to checkout a repository
     * @return array
     */
    public function getCheckoutCommands();

    /**
     * Initializes the repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @return null
     */
    public function initRepository(RepositoryEntry $repository);

    /**
     * Updates the repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @return array|boolean Array with the name of the branch as key and the
     * current revisions as value, false when nothing was changed
     */
    public function updateRepository(RepositoryEntry $repository);

    /**
     * Gets the working directory of a repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @param string $branch
     * @return \ride\library\system\file\File
     */
    public function getWorkingDirectory(RepositoryEntry $repository, $branch);

    /**
     * Gets the revision of a repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @param string $branch
     * @return string Current revision
     */
    public function getRevision(RepositoryEntry $repository, $branch);

    public function getChangedFiles(RepositoryDeployerEntry $repositoryDeployer);

}
