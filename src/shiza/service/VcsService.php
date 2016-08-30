<?php

namespace shiza\service;

use ride\library\system\file\File;
use ride\library\system\System;

class VcsService {

    public function __construct(System $system) {
        $this->system = $system;
    }

    public function getSystem() {
        return $this->system;
    }

    public function getWorkingDirectory() {
        $directory = $this->system->getConfig()->get('system.directory.repository');
        $directory = $this->system->getFileSystem()->getFile($directory);

        return $directory;
    }

    public function getVcsRepository($vcs, $url, File $directory) {
        $repository = $this->system->getDependencyInjector()->get('ride\\library\\vcs\\Repository', $vcs, array());
        $repository->setUrl($url);
        $repository->setWorkingCopy($directory);

        return $repository;
    }

    public function getBuildManager($id) {
        if (!$id) {
            throw new Exception('Could not get build manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\build\\BuildManager', $id);
    }

    public function getVcsManager($id) {
        if (!$id) {
            throw new Exception('Could not get VCS manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\vcs\\VcsManager', $id);
    }

}
