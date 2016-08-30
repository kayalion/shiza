<?php

namespace shiza\manager\database;

use ride\library\database\driver\PdoDriver;
use ride\library\database\driver\Driver;
use ride\library\database\Dsn;
use ride\library\system\file\File;
use ride\library\StringHelper;

use shiza\orm\entry\ProjectDatabaseEntry;
use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use shiza\service\ServerService;

use \Exception;

/**
 * MySQL manager through a direct connection
 */
class DriverMysqlDatabaseManager implements DatabaseManager {

    /**
     * Constructs a new databsae manager
     * @param \shiza\service\ServerService $serverService
     * @return null
     */
    public function __construct(ServerService $serverService) {
        $this->serverService = $serverService;
        $this->backupHourlyMax = 6;
        $this->backupHourlyPrefix = 'hourly-';
        $this->backupDailyMax = 14;
        $this->backupDailyPrefix = 'daily-';
    }

    /**
     * Setups a database
     * @param string $database
     * @param string $charset
     * @param string $collation
     * @return null
     */
    public function addDatabase(ProjectDatabaseEntry $database) {
        $driver = $this->getDriver($database->getServer());

        $project = $database->getProject();
        $userName = $project->getUsername();
        $databaseName = $database->getDatabaseName();

        if (!$this->userExists($driver, $userName)) {
            $password = $this->serverService->decrypt($project->getPassword());

            $this->createUser($driver, $userName, $password);
        }

        if (!$this->databaseExists($driver, $databaseName)) {
            $this->createDatabase($driver, $databaseName);
            $this->grantDatabase($driver, $databaseName, $userName);
        }
    }

    /**
     * Deletes a database
     * @param string $database
     * @return null
     */
    public function deleteDatabase(ProjectDatabaseEntry $database) {
        $driver = $this->getDriver($database->getServer());

        $project = $database->getProject();
        $userName = $project->getUsername();
        $databaseName = $database->getDatabaseName();

        if ($this->databaseExists($driver, $databaseName)) {
            $this->revokeDatabase($driver, $databaseName, $userName);
            $this->dropDatabase($driver, $databaseName);
        }

        $databases = $project->getActiveDatabases();
        if (!$databases && $this->userExists($driver, $userName)) {
            $this->dropUser($driver, $userName);
        }
    }

    /**
     * Gets a list of available backups for the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @return array Array with the id of the backup as key and a label as value
     */
    public function getBackups(ProjectDatabaseEntry $database) {
        $server = $database->getServer();
        $system = $this->serverService->getSshSystemForServer($server);

        $username = $database->getProject()->getUsername();
        $databaseName = $database->getDatabaseName();
        $backups = array();

        $backupDirectory = $system->getFileSystem()->getFile('/var/backups/' . $username . '/databases');
        $backupDirectory = $backupDirectory->getChild($databaseName);

        if ($backupDirectory->exists()) {
            $files = $backupDirectory->read();
            foreach ($files as $file) {
                $id = $file->getName();
                $time = date('Ymd-His', $file->getModificationTime());

                $backups[$id] = $id . ' - ' . $time;
            }
        }

        $system->disconnect();

        return $backups;
    }

    /**
     * Creates a backup of the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @param string $name Optional label for the backup, no timestamp
     * generation when provided
     * @return null
     */
    public function createBackup(ProjectDatabaseEntry $database, $name = null) {
        $server = $database->getServer();

        $system = $this->serverService->getSshSystemForServer($server);

        $username = $database->getProject()->getUsername();
        $databaseName = $database->getDatabaseName();

        $backupDirectory = $system->getFileSystem()->getFile('/var/backups/' . $username . '/databases');
        $backupDirectory = $backupDirectory->getChild($databaseName);
        if (!$backupDirectory->exists()) {
            $backupDirectory->create();
        }

        if ($name) {
            $name = StringHelper::safeString($name);

            $backup = $backupDirectory->getChild($name . '.sql.gz');
        } else {
            $backup = $this->serverService->getBackupHelper()->rotateBackup($system, $backupDirectory, $this->backupHourlyMax, $this->backupHourlyPrefix, '.sql.gz');
        }

        $sqlFile = $backup->getParent()->getChild(substr($backup->getName(), 0, -3));

        $password = $database->getProject()->getPassword();
        $password = $this->serverService->decrypt($password);

        $exportCommand = 'mysqldump --host=localhost --user=' . $username . ' --password=' . $password . ' ' . $databaseName . ' > ' . $sqlFile->getLocalPath();

        $code = null;
        $output = $system->execute($exportCommand, $code);

        if ($code != '0') {
            throw new Exception('Could not export the database: ' . implode("\n", $output));
        }

        $compressCommand = 'gzip -f ' . $sqlFile->getLocalPath();

        $code = null;
        $output = $system->execute($compressCommand, $code);

        if ($code != '0') {
            throw new Exception('Could not compress database backup: ' . implode("\n", $output));
        }
    }


    /**
     * Rotates the hourly backups into daily ones
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @return null
     */
    public function rotateBackup(ProjectDatabaseEntry $database) {
        $server = $database->getServer();

        $system = $this->serverService->getSshSystemForServer($server);

        $username = $database->getProject()->getUsername();
        $databaseName = $database->getDatabaseName();

        $backupDirectory = $system->getFileSystem()->getFile('/var/backups/' . $username . '/databases');
        $backupDirectory = $backupDirectory->getChild($databaseName);
        if (!$backupDirectory->exists()) {
            return;
        }

        $suffix = '.sql.gz';
        $slots = 24 / $this->backupHourlyMax;
        $slot = floor(date('H') / $slots) - 1;

        $backupName = $this->backupHourlyPrefix . $slot . $suffix;
        $backup = $backupDirectory->getChild($backupName);

        $this->serverService->getBackupHelper()->rotateBackupDaily($system, $backupDirectory, $backup, $this->backupDailyMax, $this->backupDailyPrefix, $suffix);
    }

    /**
     * Gets a list of available backups for the provided database
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @param string $backup Id of the backup
     * @param \shiza\orm\entry\ProjectDatabaseEntry $destination
     * @return null
     */
    public function restoreBackup(ProjectDatabaseEntry $database, $backup, ProjectDatabaseEntry $destination = null) {
        $server = $database->getServer();
        $system = $this->serverService->getSshSystemForServer($server);

        $username = $database->getProject()->getUsername();
        $databaseName = $database->getDatabaseName();

        $backupDirectory = $system->getFileSystem()->getFile('/var/backups/' . $username . '/databases');
        $backupDirectory = $backupDirectory->getChild($databaseName);

        $backupFile = $backupDirectory->getChild($backup);

        if (!$backupFile->exists()) {
            throw new Exception('Backup ' . $backup . ' does not exist');
        }

        $tempFile = $system->getFileSystem()->getFile('/tmp/restore-' . $username . '.sql');
        $tempFile = $tempFile->getCopyFile();

        $sqlFile = $tempFile;

        $tempFile = $tempFile->getParent()->getChild($tempFile->getName() . '.' . $backupFile->getExtension());

        $backupFile->copy($tempFile);

        $decompressCommand = 'gzip -d ' . $tempFile->getLocalPath();

        $code = null;
        $output = $system->execute($decompressCommand, $code);

        if ($code != '0') {
            if ($tempFile->exists()) {
                $tempFile->delete();
            }

            throw new Exception('Could not decompress database backup: ' . implode("\n", $output));
        }

        if ($destination) {
            $destination = $destination->getDatabaseName();
        } else {
            $destination = $databaseName;
        }

        $password = $database->getProject()->getPassword();
        $password = $this->serverService->decrypt($password);

        $importCommand = 'mysql --max_allowed_packet=100M --host=localhost --user=' . $username . ' --password=' . $password . ' ' . $destination . ' < ' . $sqlFile->getLocalPath();

        $code = null;
        $output = $system->execute($importCommand, $code);

        if ($tempFile->exists()) {
            $tempFile->delete();
        }

        if ($sqlFile->exists()) {
            $sqlFile->delete();
        }

        if ($code != '0') {
            throw new Exception('Could not import the database backup: ' . implode("\n", $output));
        }
    }

    /**
     * Downloads a backup to the provided file
     * @param \shiza\orm\entry\ProjectDatabaseEntry $database
     * @param string $backup Id of the backup
     * @param \ride\library\system\file\File $destination File to store the
     * backup in
     * @return null
     */
    public function downloadBackup(ProjectDatabaseEntry $database, $backup, File $destination) {
        $server = $database->getServer();
        $system = $this->serverService->getSshSystemForServer($server);

        $username = $database->getProject()->getUsername();
        $databaseName = $database->getDatabaseName();

        $backupDirectory = $system->getFileSystem()->getFile('/var/backups/' . $username . '/databases');
        $backupDirectory->getChild($databaseName);

        $backupFile = $backupDirectory->getChild($backup);

        if ($backupFile->exists()) {
            $backupFile->copy($destination);
        }

        $system->disconnect();
    }

    /**
     * Calculates the disk usage of the provided databases
     * @param \shiza\orm\entry\ServerEntry $server
     * @param array $projectDatabases Array of databases on the server
     * @return null
     */
    public function calculateDiskUsage(ServerEntry $server, array $projectDatabases) {
        $driver = $this->getDriver($server);

        $query = 'SELECT table_schema "database", SUM(data_length + index_length) "size" ';
        $query .= 'FROM information_schema.TABLES ';
        $query .= 'GROUP BY table_schema';

        $result = $driver->execute($query);
        foreach ($result as $row) {
            foreach ($projectDatabases as $projectDatabase) {
                if ($projectDatabase->getDatabaseName() != $row['database']) {
                    continue;
                }

                $projectDatabase->setDiskUsage($row['size']);
            }
        }
    }

    /**
     * Checks if a user exists
     * @param string $username
     * @param string $host
     * @return boolean
     */
    private function userExists(Driver $driver, $username, $host = null) {
        $query = "SELECT COUNT(*) AS numUsers FROM mysql.user WHERE User = '$username'";
        if ($host) {
            $query .= " AND Host = '$host'";
        }

        $result = $driver->execute($query);
        $row = $result->getFirst();

        return $row['numUsers'] ? true : false;
    }

    /**
     * Creates a user
     * @param string $username Username of the new user
     * @param string $password Password of the new user, one will be generated
     * if not provided
     * @param string $host Host for the user. If not provided, a user for % and
     * localhost will be created
     * @return boolean|string A password string if a new user has been made and
     * no password was provided, true if a user was created, false if the user
     * exists
     */
    private function createUser(Driver $driver, $username, $password, $host = null) {
        if ($host) {
            $driver->execute("CREATE USER '$username'@'$host' IDENTIFIED BY '$password'");
        } else {
            $driver->execute("CREATE USER '$username'@'localhost' IDENTIFIED BY '$password'");
            $driver->execute("CREATE USER '$username'@'%' IDENTIFIED BY '$password'");
        }

        return true;
    }

    /**
     * Deletes a user
     * @param \ride\library\database\driver\Driver $driver
     * @param string $username
     * @param string $host
     * @return boolean
     */
    private function dropUser(Driver $driver, $username, $host = null) {
        if ($host) {
            $driver->execute("DROP USER '$username'@'$host'");
        } else {
            $driver->execute("DROP USER '$username'@'%'");
            $driver->execute("DROP USER '$username'@'localhost'");
        }

        return true;
    }

    /**
     * Checks if a database exists
     * @param \ride\library\database\driver\Driver $driver
     * @param string $database
     * @return boolean
     */
    private function databaseExists(Driver $driver, $database) {
        $result = $driver->execute("SHOW DATABASES LIKE '$database'");

        return $result->getRowCount() ? true : false;
    }

    /**
     * Creates a database
     * @param \ride\library\database\driver\Driver $driver
     * @param string $database
     * @param string $charset
     * @param string $collation
     * @return null
     */
    private function createDatabase(Driver $driver, $database, $charset = 'utf8', $collation = 'utf8_general_ci') {
        $driver->execute("CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET = '$charset' COLLATE '$collation'");
    }

    /**
     * Grants all permissions
     * @param \ride\library\database\driver\Driver $driver
     * @param string $database
     * @param string $username
     * @param string $host
     * @return null
     * @throws Exception when the user or database do not exist
     */
    private function grantDatabase(Driver $driver, $database, $username, $host = null) {
        if (!$host) {
            $driver->execute("GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'localhost' WITH GRANT OPTION");
            $driver->execute("GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'%' WITH GRANT OPTION");
        } else {
            $driver->execute("GRANT ALL PRIVILEGES ON `$database`.* TO '$username'@'$host' WITH GRANT OPTION");
        }

        $driver->execute("FLUSH PRIVILEGES");
    }

    /**
     * Revokes all permissions
     * @param \ride\library\database\driver\Driver $driver
     * @param string $database
     * @param string $username
     * @param string $host
     * @return null
     * @throws Exception when the user or database do not exist
     */
    private function revokeDatabase(Driver $driver, $database, $username, $host = null) {
        if (!$host) {
            $driver->execute("REVOKE ALL PRIVILEGES ON `$database`.* FROM '$username'@'localhost'");
            $driver->execute("REVOKE GRANT OPTION ON `$database`.* FROM '$username'@'localhost'");
            $driver->execute("REVOKE ALL PRIVILEGES ON `$database`.* FROM '$username'@'%'");
            $driver->execute("REVOKE GRANT OPTION ON `$database`.* FROM '$username'@'%'");
        } else {
            $driver->execute("REVOKE ALL PRIVILEGES ON `$database`.* FROM '$username'@'$host'");
            $driver->execute("REVOKE GRANT OPTION ON `$database`.* FROM '$username'@'$host'");
        }

        $driver->execute("FLUSH PRIVILEGES");
    }

    /**
     * Drops a database
     * @param \ride\library\database\driver\Driver $driver
     * @param string $database
     * @return null
     */
    private function dropDatabase(Driver $driver, $database) {
        $driver->execute("DROP DATABASE IF EXISTS `$database`");
    }

    /**
     * Gets the driver for the administrator of the provided database
     * @param \shiza\orm\entry\ServerEntry $server
     * @return \ride\library\database\driver\Driver
     */
    private function getDriver(ServerEntry $server) {
        $host = $server->getHost();
        $username = $server->getDatabaseUsername();
        $password = $server->getDatabasePassword();

        $password = $this->serverService->decrypt($password);

        $dsn = 'mysql://' . $username . ':' . $password . '@' . $host . '/information_schema';

        $driver = new PdoDriver(new Dsn($dsn));
        $driver->setLog($this->serverService->getLog());
        $driver->connect();

        return $driver;
    }

}
