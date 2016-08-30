<?php

namespace shiza\manager\build;

use shiza\manager\vcs\VcsManager;

use shiza\orm\entry\RepositoryBuilderEntry;

use \Exception;

/**
 * Interface for the manager of a repository builder
 */
interface BuildManager {

    /**
     * Runs the script of the builder for the provided revision
     * @param \shiza\mangaer\vcs\VcsManager $vcsManager
     * @param \shiza\orm\entry\RepositoryBuilderEntry $builder
     * @param string $revision
     * @param \Exception $exception
     * @return string Log of the builder
     */
    public function runScript(VcsManager $vcsManager, RepositoryBuilderEntry $builder, $revision, Exception &$exception = null);

}
