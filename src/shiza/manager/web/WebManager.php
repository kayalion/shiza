<?php

namespace shiza\manager\web;

use shiza\orm\entry\ProjectEnvironmentEntry;
use shiza\orm\entry\ServerEntry;

interface WebManager {

    public function getPhpVersions(ServerEntry $server);

    public function getLog(ProjectEnvironmentEntry $environment);

    public function getWebRoot(ProjectEnvironmentEntry $environment);

    public function updateEnvironment(ProjectEnvironmentEntry $environment);

    /**
     * Creates a backup of the provided environment
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @param string $name Optional label for the backup, no automatic
     * generation when provided
     * @return null
     */
    public function createBackup(ProjectEnvironmentEntry $environment, $name = null);

    /**
     * Rotates the hourly backups into daily ones
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @return null
     */
    public function rotateBackup(ProjectEnvironmentEntry $environment);

    /**
     * Restores the provided backup to the environment
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @param string $backup Id of the backup
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $destination
     * @return null
     */
    public function restoreBackup(ProjectEnvironmentEntry $environment, $backup, ProjectEnvironmentEntry $destination = null);

    /**
     * Calculates the disk usage of the provided environments
     * @param \shiza\orm\entry\ServerEntry $server
     * @param array $environments Array of environments on the server
     * @return null
     */
    public function calculateDiskUsage(ServerEntry $server, array $environments);

}
