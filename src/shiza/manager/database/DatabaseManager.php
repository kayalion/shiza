<?php

namespace shiza\manager\database;

use ride\library\system\file\File;

use shiza\orm\entry\ProjectDatabaseEntry;
use shiza\orm\entry\ServerEntry;

/**
 * Manager for a database server
 */
interface DatabaseManager {

    /**
     * Adds a database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @return null
     */
    public function addDatabase(ProjectDatabaseEntry $database);

    /**
     * Deletes a database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @return null
     */
    public function deleteDatabase(ProjectDatabaseEntry $database);

    /**
     * Gets a list of available backups for the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @return array Array with the id of the backup as key and a label as value
     */
    public function getBackups(ProjectDatabaseEntry $database);

    /**
     * Creates a backup of the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @param string $name Optional label for the backup, no timestamp
     * generation when provided
     * @return null
     */
    public function createBackup(ProjectDatabaseEntry $database, $name = null);

    /**
     * Rotates the hourly backups into daily ones
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @return null
     */
    public function rotateBackup(ProjectDatabaseEntry $database);

    /**
     * Gets a list of available backups for the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @param string $backup Id of the backup
     * @param \shiza\orm\entry\ProjectDatabaseEntry $destination
     * @return null
     */
    public function restoreBackup(ProjectDatabaseEntry $database, $backup, ProjectDatabaseEntry $destination = null);

    /**
     * Gets a list of available backups for the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @param string $backup Id of the backup
     * @param \ride\library\system\file\File $destination File to store the
     * backup in
     * @return null
     */
    public function downloadBackup(ProjectDatabaseEntry $database, $backup, File $destination);

    /**
     * Calculates the disk usage of the provided databases
     * @param \shiza\orm\entry\ServerEntry $server
     * @param array $projectDatabases Array of databases on the server
     * @return null
     */
    public function calculateDiskUsage(ServerEntry $server, array $projectDatabases);

}
