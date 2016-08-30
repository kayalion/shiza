<?php

namespace shiza\manager\cron;

use shiza\orm\entry\ProjectEntry;

interface CronManager {

    public function updateCrontab(ProjectEntry $project);

}
