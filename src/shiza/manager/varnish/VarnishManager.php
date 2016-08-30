<?php

namespace shiza\manager\varnish;

use shiza\orm\entry\ProjectEntry;

interface VarnishManager {

    public function setupVarnish(ProjectEntry $project, array $projects);

    public function restartVarnish(ProjectEntry $project);

    public function reloadVarnish(ProjectEntry $project);

    public function deleteVarnish(ProjectEntry $project, array $projects);

}
