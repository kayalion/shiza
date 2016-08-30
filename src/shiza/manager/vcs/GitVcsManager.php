<?php

namespace shiza\manager\vcs;

use shiza\orm\entry\RepositoryDeployerEntry;
use shiza\orm\entry\RepositoryEntry;

use shiza\service\VcsService;

/**
 * Interface for the VCS manager of a repository
 */
class GitVcsManager implements VcsManager {

    public function __construct(VcsService $vcsService) {
        $this->vcsService = $vcsService;
    }

    /**
     * Gets the commands to checkout a repository
     * @return array
     */
    public function getCheckoutCommands() {
        return array(
            '# Cloning repository',
            'git clone --depth=50 --branch=[[branch]] [[repository]] .',
            'git checkout -qf [[revision]]',
        );
    }

    /**
     * Initializes the repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @return null
     */
    public function initRepository(RepositoryEntry $repository) {
        $directory = $this->vcsService->getWorkingDirectory();

        $directoryHead = $repository->getBranchDirectory($directory);
        if ($directoryHead->exists()) {
            $directoryHead->delete();
        }
        $directoryHead->create();

        $gitHead = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directoryHead);
        $gitHead->checkout();

        $branches = $gitHead->getBranches();
        foreach ($branches as $branch) {
            $directoryBranch = $repository->getBranchDirectory($directory, $branch);
            if ($directoryBranch->exists()) {
                $directoryBranch->delete();
            }

            $directoryHead->copy($directoryBranch);

            $gitBranch = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directoryBranch);

            if ($gitBranch->getBranch() != $branch) {
                $gitBranch->checkout(array(
                    'branch' => $branch,
                ));
            }
        }
    }

    /**
     * Updates the repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @return array|boolean Array with the name of the branch as key and the
     * current revisions as value, false when nothing was changed
     */
    public function updateRepository(RepositoryEntry $repository) {
        $oldRevisions = array();
        $revisions = array();

        $directory = $this->vcsService->getWorkingDirectory();

        $directoryHead = $repository->getBranchDirectory($directory);

        $gitHead = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directoryHead);

        $branches = $gitHead->getBranches();
        foreach ($branches as $branch) {
            $directoryBranch = $repository->getBranchDirectory($directory, $branch);
            if (!$directoryBranch->exists()) {
                continue;
            }

            $gitBranch = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directoryBranch);

            $oldRevisions[$branch] = $gitBranch->getRevision();
        }

        $gitHead->update();

        $branches = $gitHead->getBranches();
        foreach ($branches as $branch) {
            $directoryBranch = $repository->getBranchDirectory($directory, $branch);
            if (!$directoryBranch->exists()) {
                $directoryHead->copy($directoryBranch);

                $gitBranch = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directoryBranch);
                $gitBranch->checkout(array(
                    'branch' => $branch,
                ));
            } else {
                $gitBranch = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directoryBranch);
                $gitBranch->update();
            }

            $revisions[$branch] = $gitBranch->getRevision();
        }

        if ($oldRevisions == $revisions) {
            return false;
        }

        return $revisions;
    }

    /**
     * Gets the working directory of a repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @param string $branch
     * @return \ride\library\system\file\File
     */
    public function getWorkingDirectory(RepositoryEntry $repository, $branch) {
        $directory = $this->vcsService->getWorkingDirectory();

        $directory = $repository->getBranchDirectory($directory, $branch);
        if (!$directory->exists()) {
            throw new Exception('Could not get revision of ' . $repository . ': no checkout done');
        }

        return $directory;
    }


    /**
     * Gets the revision of a repository
     * @param \shiza\orm\entry\RepositoryEntry $repository
     * @param string $branch
     * @return string Current revision
     */
    public function getRevision(RepositoryEntry $repository, $branch) {
        $directory = $this->getWorkingDirectory($repository, $branch);

        $gitBranch = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directory);

        return $gitBranch->getRevision();
    }

    public function getChangedFiles(RepositoryDeployerEntry $repositoryDeployer) {
        $files = array();

        $repository = $repositoryDeployer->getRepository();
        $repositoryPath = $repositoryDeployer->getRepositoryPath();
        $branch = $repositoryDeployer->getBranch();
        $revision = $repositoryDeployer->getRevision();

        $directory = $this->getWorkingDirectory($repository, $branch);
        $gitBranch = $this->vcsService->getVcsRepository('git', $repository->getUrl(), $directory);

        if ($revision) {
            $output = $gitBranch->git('diff --name-status ' . $revision);
            foreach ($output as $file) {
                list($action, $path) = explode("\t", $file, 2);

                if (isset($files[$file]) || strpos('/' . $path, $repositoryPath) !== 0) {
                    continue;
                }

                $files[$path] = $action;
            }
        } else {
            $output = $gitBranch->getTree($repositoryDeployer->getBranch(), null, true);
            foreach ($output as $path => $null) {
                if (strpos('/' . $path, $repositoryPath) !== 0) {
                    continue;
                }

                $files[$path] = 'A';
            }
        }

        return $files;
    }

}
