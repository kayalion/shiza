<?php

namespace shiza\manager\web;

use ride\library\template\TemplateFacade;
use ride\library\system\file\File;
use ride\library\system\SshSystem;
use ride\library\StringHelper;

use shiza\orm\entry\ProjectEnvironmentEntry;
use shiza\orm\entry\ServerEntry;

use shiza\service\ServerService;

use \Exception;

class ApacheSshWebManager implements WebManager {

    const TEMPLATE_VIRTUAL_HOST = 'config/apache.virtual.host';

    const TEMPLATE_PHP_WRAPPER = 'config/apache.php.wrapper';

    public function __construct(ServerService $serverService, TemplateFacade $templateFacade, File $skeletonDirectory) {
        $this->serverService = $serverService;
        $this->templateFacade = $templateFacade;
        $this->skeletonDirectory = $skeletonDirectory;

        $this->phpFarmDirectory = '/opt/phpfarm';

        $this->logScript = __DIR__ . '/../../../logresolvemerge.pl';

        $this->backupHourlyMax = 6;
        $this->backupHourlyPrefix = 'hourly-';
        $this->backupDailyMax = 14;
        $this->backupDailyPrefix = 'daily-';
    }

    public function getPhpVersions(ServerEntry $server) {
        $system = $this->serverService->getSshSystemForServer($server);

        return $this->readPhpVersions($system);
    }

    public function getWebRoot(ProjectEnvironmentEntry $environment) {
        $server = $environment->getServer();

        $authManager = $this->serverService->getAuthManager($server->getAuthManager());

        $username = $environment->getProject()->getUsername();
        $reversedDomain = $environment->getReversedDomain();

        $homeDirectory = $authManager->getHomeDirectory($username);

        return rtrim($homeDirectory, '/') . '/www/' . $reversedDomain . '/public';
    }

    /**
     * Gets the combined log from the provided environment
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @return string Log for the provided environment
     */
    public function getLog(ProjectEnvironmentEntry $environment) {
        $server = $environment->getServer();
        if (!$server) {
            return;
        }

        $files = array();

        $localSystem = $this->serverService->getLocalSystem();
        $remoteSystem = $this->serverService->getSshSystemForServer($server);
        $username = $environment->getProject()->getUsername();

        $authManager = $this->serverService->getAuthManager($server->getAuthManager());
        $homeDirectory = $authManager->getHomeDirectory($username);
        $homeDirectory = $remoteSystem->getFileSystem()->getFile($homeDirectory);
        $reversedDomain = $environment->getReversedDomain();

        $logDirectory = $this->getLogDirectory($homeDirectory, $reversedDomain);
        $remoteFiles = array(
            $logDirectory->getChild($reversedDomain . '-access.log'),
            $logDirectory->getChild($reversedDomain . '-access.log.1'),
            $logDirectory->getChild($reversedDomain . '-error.log'),
            $logDirectory->getChild($reversedDomain . '-error.log.1'),
        );

        foreach ($remoteFiles as $remoteFile) {
            if (!$remoteFile->exists()) {
                continue;
            }

            $localFile = $localSystem->getFileSystem()->getTemporaryFile();

            $remoteFile->copy($localFile);

            $files[$localFile->getAbsolutePath()] = $localFile;
        }

        $remoteSystem->disconnect();

        if (!$files) {
            return;
        }

        $command = $this->logScript . ' ' . implode(' ', array_keys($files));

        $code = null;
        $output = $localSystem->execute($command, $code);

        foreach ($files as $file) {
            $file->delete();
        }

        if ($code != '0') {
            throw new Exception('Could not execute logrotate script: ' . implode("\n", $output));
        }

        // foreach ($output as $number => $line) {
            // if (strpos($line, '/lbcheck.html') !== false || strpos($line, 'OPTIONS *') !== false) {
                // unset($output[$number]);
            // }
        // }

        return implode("\n", array_reverse($output));
    }

    /**
     * Gets a list of available backups for the provided environment
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @return array Array with the id of the backup as key and a label as value
     */
    public function getBackups(ProjectEnvironmentEntry $environment) {
        $server = $environment->getServer();

        $system = $this->serverService->getSshSystemForServer($server);
        $authManager = $this->serverService->getAuthManager($server->getAuthManager());

        $username = $environment->getProject()->getUsername();
        $reversedDomain = $environment->getReversedDomain();

        $homeDirectory = $authManager->getHomeDirectory($username);
        $homeDirectory = $system->getFileSystem()->getFile($homeDirectory);

        $backups = array();

        $backupDirectory = $this->getBackupDirectory($homeDirectory, $reversedDomain);
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
     * Creates a backup of the provided environment
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @param string $name Optional label for the backup, no automatic
     * generation when provided
     * @return null
     */
    public function createBackup(ProjectEnvironmentEntry $environment, $name = null) {
        $server = $environment->getServer();

        $system = $this->serverService->getSshSystemForServer($server);
        $authManager = $this->serverService->getAuthManager($server->getAuthManager());

        $username = $environment->getProject()->getUsername();
        $reversedDomain = $environment->getReversedDomain();

        $homeDirectory = $authManager->getHomeDirectory($username);
        $homeDirectory = $system->getFileSystem()->getFile($homeDirectory);

        $publicDirectory = $this->getPublicDirectory($homeDirectory, $reversedDomain);
        $publicDirectory = $publicDirectory->getParent();

        $backupDirectory = $this->getBackupDirectory($homeDirectory, $reversedDomain);
        if (!$backupDirectory->exists()) {
            $backupDirectory->create();
        }

        if ($name) {
            $name = StringHelper::safeString($name);

            $backup = $backupDirectory->getChild($name);
        } else {
            $backup = $this->serverService->getBackupHelper()->rotateBackupHourly($system, $backupDirectory, $this->backupHourlyMax, $this->backupHourlyPrefix);
        }

        // create a backup
        $command = 'rsync -va --numeric-ids --delete --delete-excluded ' . $publicDirectory->getLocalPath() . '/ ' . $backup->getLocalPath();
        $code = null;

        $output = $system->execute($command, $code);

        if ($code != 0) {
            throw new Exception('Could not create backup of ' . $reversedDomain . ': ' . implode("\n", $output));
        }

        $output = $system->execute('touch ' . $backup->getLocalPath(), $code);

        if ($code != 0) {
            throw new Exception('Could set time of backup ' . $backup->getName() . ': ' . implode("\n", $output));
        }
    }

    /**
     * Rotates the hourly backups into daily ones
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @return null
     */
    public function rotateBackup(ProjectEnvironmentEntry $environment) {
        $server = $environment->getServer();

        $system = $this->serverService->getSshSystemForServer($server);
        $authManager = $this->serverService->getAuthManager($server->getAuthManager());

        $username = $environment->getProject()->getUsername();
        $reversedDomain = $environment->getReversedDomain();

        $homeDirectory = $authManager->getHomeDirectory($username);
        $homeDirectory = $system->getFileSystem()->getFile($homeDirectory);

        $backupDirectory = $this->getBackupDirectory($homeDirectory, $reversedDomain);
        if (!$backupDirectory->exists()) {
            return;
        }

        $slots = 24 / $this->backupHourlyMax;
        $slot = floor(date('H') / $slots) - 1;

        $backupName = $this->backupHourlyPrefix . $slot;
        $backup = $backupDirectory->getChild($backupName);

        $this->serverService->getBackupHelper()->rotateBackupDaily($system, $backupDirectory, $backup, $this->backupDailyMax, $this->backupDailyPrefix);
    }

    /**
     * Restores the provided backup to the environment
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $environment
     * @param string $backup Id of the backup
     * @param \shiza\orm\entry\ProjectEnvironmentEntry $destination
     * @return null
     */
    public function restoreBackup(ProjectEnvironmentEntry $environment, $backup, ProjectEnvironmentEntry $destination = null) {
        if (!$backup) {
            throw new Exception('Could not restore backup: no backup id provided');
        }

        $server = $environment->getServer();

        $system = $this->serverService->getSshSystemForServer($server);
        $authManager = $this->serverService->getAuthManager($server->getAuthManager());

        $username = $environment->getProject()->getUsername();
        $reversedDomain = $environment->getReversedDomain();

        $homeDirectory = $authManager->getHomeDirectory($username);
        $homeDirectory = $system->getFileSystem()->getFile($homeDirectory);

        if ($destination) {
            $publicDirectory = $this->getPublicDirectory($homeDirectory, $destination->getReversedDomain());
        } else {
            $publicDirectory = $this->getPublicDirectory($homeDirectory, $reversedDomain);
        }
        $destination = $publicDirectory->getParent();

        $backupDirectory = $this->getBackupDirectory($homeDirectory, $reversedDomain);

        $backup = $backupDirectory->getChild($backup);
        if (!$backup->exists()) {
            throw new Exception('Could not restore backup ' . $backup->getName() . ': file does not exist');
        }

        $command = 'rsync -va --numeric-ids --delete --delete-excluded ' . $backup->getLocalPath() . '/ ' . $destination->getLocalPath();
        $code = null;

        $output = $system->execute($command, $code);

        if ($code != 0) {
            throw new Exception('Could not restore backup ' . $backup->getName() . ': ' . implode("\n", $output));
        }
    }

    /**
     * Calculates the disk usage of the provided environments
     * @param \shiza\orm\entry\ServerEntry $server
     * @param array $environments Array of environments on the server
     * @return null
     */
    public function calculateDiskUsage(ServerEntry $server, array $environments) {
        $system = $this->serverService->getSshSystemForServer($server);
        $authManager = $this->serverService->getAuthManager($server->getAuthManager());

        foreach ($environments as $environment) {
            $username = $environment->getProject()->getUsername();
            $reversedDomain = $environment->getReversedDomain();

            $homeDirectory = $authManager->getHomeDirectory($username);
            $homeDirectory = $system->getFileSystem()->getFile($homeDirectory);

            $publicDirectory = $this->getPublicDirectory($homeDirectory, $reversedDomain);
            $publicDirectory = $publicDirectory->getParent();

            $code = null;
            $command = 'du -bs ' . $publicDirectory->getLocalPath();

            $output = $system->execute($command, $code);

            if ($code != '0' || !$output) {
                continue;
            }

            $line = array_shift($output);

            if (strpos($line, "\t") === false) {
                continue;
            }

            list($size, $path) = explode("\t", $line, 2);

            $environment->setDiskUsage($size);
            $environment->setSkipQueue();
        }
    }

    public function updateEnvironment(ProjectEnvironmentEntry $environment) {
        $server = $environment->getServer();
        if (!$server) {
            return;
        }

        $system = $this->serverService->getSshSystemForServer($server);
        $username = $environment->getProject()->getUsername();

        $authManager = $this->serverService->getAuthManager($server->getAuthManager());
        $homeDirectory = $authManager->getHomeDirectory($username);
        $homeDirectory = $system->getFileSystem()->getFile($homeDirectory);

        if ($environment->isDeleted()) {
            $this->deleteEnvironment($system, $homeDirectory, $environment);
        } else {
            $this->initializeEnvironment($system, $homeDirectory, $environment);
        }
    }

    private function deleteEnvironment(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        $reversedDomain = $environment->getReversedDomain();

        $this->disableSite($system, $homeDirectory, $environment);

        $publicDirectory = $this->getPublicDirectory($homeDirectory, $reversedDomain);
        $publicDirectory = $publicDirectory->getParent();
        if ($publicDirectory->exists()) {
            $publicDirectory->delete();
        }

        $binDirectory = $this->getBinDirectory($homeDirectory, $reversedDomain);
        if ($binDirectory->exists()) {
            $binDirectory->delete();
        }

        $configDirectory = $this->getConfigDirectory($homeDirectory, $reversedDomain);
        if ($configDirectory->exists()) {
            $configDirectory->delete();
        }

        $logDirectory = $this->getLogDirectory($homeDirectory, $reversedDomain);

        $accessLogFile = $logDirectory->getChild($reversedDomain . '-access.log');
        if ($accessLogFile->exists()) {
            $accessLogFile->delete();
        }

        $errorLogFile = $logDirectory->getChild($reversedDomain . '-error.log');
        if ($errorLogFile->exists()) {
            $errorLogFile->delete();
        }
    }

    private function initializeEnvironment(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        $this->createPublicDirectory($system, $homeDirectory, $environment);
        $this->createLogDirectory($system, $homeDirectory, $environment);
        $this->createConfiguration($system, $homeDirectory, $environment);

        if ($environment->isActive()) {
            $this->enableSite($system, $homeDirectory, $environment);
        } else {
            $this->disableSite($system, $homeDirectory, $environment);
        }
    }

    private function createPublicDirectory(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        $username = $environment->getProject()->getUsername();

        // create public directory
        $publicDirectory = $this->getPublicDirectory($homeDirectory, $environment->getReversedDomain());
        if (!$publicDirectory->exists()) {
            $publicDirectory->create();

            // www/<domain>/public
            $system->execute('chown ' . $username . ':' . $username . ' ' . $publicDirectory->getLocalPath());

            // www/<domain>
            $parent = $publicDirectory->getParent();
            $system->execute('chown ' . $username . ':' . $username . ' ' . $parent->getLocalPath());

            // www
            $parent = $parent->getParent();
            $system->execute('chown ' . $username . ':' . $username . ' ' . $parent->getLocalPath());
        }

        // create default files if public is empty
        $files = $publicDirectory->read();
        if (!$files) {
            $this->writeDefaultFiles($system, $publicDirectory, $username);
        }
    }

    private function writeDefaultFiles(SshSystem $system, File $publicDirectory, $username) {
        if (!$this->skeletonDirectory->exists() || !$this->skeletonDirectory->isDirectory()) {
            return;
        }

        $skeletonPath = $this->skeletonDirectory->getAbsolutePath();

        $files = $this->skeletonDirectory->read(true);
        foreach ($files as $localFile) {
            $path = str_replace($skeletonPath . '/', '', $localFile->getAbsolutePath());

            $remoteFile = $publicDirectory->getChild($path);
            $localFile->copy($remoteFile);
        }

        $system->execute('chown -R ' . $username . ':' . $username . ' ' . $publicDirectory->getLocalPath());
    }

    private function createLogDirectory(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        $username = $environment->getProject()->getUsername();

        // create log directory
        $logDirectory = $this->getLogDirectory($homeDirectory, $environment->getReversedDomain());
        if ($logDirectory->exists()) {
            return;
        }

        $logDirectory->create();

        // log/<domain>
        $system->execute('chown ' . $username . ':' . $username . ' ' . $logDirectory->getLocalPath());

        // log
        $parent = $logDirectory->getParent();
        $system->execute('chown ' . $username . ':' . $username . ' ' . $parent->getLocalPath());
    }

    private function createConfiguration(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        $username = $environment->getProject()->getUsername();
        $reversedDomain = $environment->getReversedDomain();

        $binDirectory = $this->getBinDirectory($homeDirectory, $reversedDomain);
        $configDirectory = $this->getConfigDirectory($homeDirectory, $reversedDomain);
        $logDirectory = $this->getLogDirectory($homeDirectory, $reversedDomain);
        $publicDirectory = $this->getPublicDirectory($homeDirectory, $reversedDomain);
        $phpWrapperFile = $binDirectory->getChild('php.cgi');

        $phpVersion = $environment->getPhpVersion();
        if (!$phpVersion) {
            $phpVersions = $this->readPhpVersions($system);
            $phpVersion = array_shift($phpVersions);

            $environment->setPhpVersion($phpVersion);
        }

        $phpBinary = $this->getPhpBinary($phpVersion);

        $variables = array(
            'username' => $username,
            'domain' => $environment->getDomain(),
            'reversedDomain' => $reversedDomain,
            'aliases' => $environment->getAliases(),
            'configDirectory' => $configDirectory->getLocalPath(),
            'logDirectory' => $logDirectory->getLocalPath(),
            'publicDirectory' => $publicDirectory->getLocalPath(),
            'phpVersion' => $phpVersion,
            'phpBinary' => $phpBinary,
            'phpWrapper' => $phpWrapperFile->getLocalPath(),
            'isVarnish' => $environment->getProject()->getVarnishMemory() ? true : false,
            'isSsl' => false,
            'sslDomain' => null,
            'sslCertificateFile' => null,
            'sslCertificateKeyFile' => null,
        );

        // generate ssl certificate
        if ($environment->isValidSsl()) {
            $sslDirectory = $this->getApacheSslDirectory($system, $username);

            $certificateFile = $sslDirectory->getChild($reversedDomain . '.crt');
            $certificateFile->write($environment->getSslCertificate());

            $certificateKeyFile = $sslDirectory->getChild($reversedDomain . '.key');
            $certificateKeyFile->write($environment->getSslCertificateKey());

            $variables['isSsl'] = true;
            $variables['sslDomain'] = $environment->getSslCommonName();
            $variables['sslCertificateFile'] = $certificateFile->getLocalPath();
            $variables['sslCertificateKeyFile'] = $certificateKeyFile->getLocalPath();
        }

        // generate php ini
        if ($environment->getPhpIni()) {
            $phpIniFile = $this->getPhpConfigFile($configDirectory);
            $phpIniFile->write($environment->getPhpIni());
        }

        // generate php wrapper
        $template = $this->templateFacade->createTemplate(self::TEMPLATE_PHP_WRAPPER, $variables);
        $phpWrapper = $this->templateFacade->render($template);

        if ($phpWrapperFile->exists()) {
            $phpWrapperFile->write($phpWrapper);
        } else {
            $phpWrapperFile->write($phpWrapper);

            $system->execute('chmod 755 ' . $phpWrapperFile->getLocalPath());
            $system->execute('chown ' . $username . ':' . $username . ' ' . $phpWrapperFile->getLocalPath());
            $system->execute('chown ' . $username . ':' . $username . ' ' . $phpWrapperFile->getParent()->getLocalPath());
        }

        // generate virtual host
        $template = $this->templateFacade->createTemplate(self::TEMPLATE_VIRTUAL_HOST, $variables);
        $virtualHost = $this->templateFacade->render($template);

        $virtualHostFile = $this->getApacheConfigFile($configDirectory);
        $virtualHostFile->write($virtualHost);
    }

    private function enableSite(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        if (!$this->isValidApacheConfiguration($system)) {
            throw new Exception('Could not enable ' . $environment . ': Apache has an invalid configuration, run apache2ctl configtest');
        }

        $reversedDomain = $environment->getReversedDomain();

        $virtualHostFile = $this->getApacheConfigFile($this->getConfigDirectory($homeDirectory, $reversedDomain));
        if (!$virtualHostFile->exists()) {
            throw new Exception('Could not enable ' . $environment . ': virtual host file does not exist');
        }

        $siteFile = $this->getApacheSiteFile($system, $reversedDomain);
        if (!$siteFile->exists()) {
            $system->execute('ln -s ' . $virtualHostFile->getLocalPath() . ' ' . $siteFile->getLocalPath());

            if (!$this->isValidApacheConfiguration($system)) {
                $siteFile->delete();

                throw new Exception('Could not enable ' . $environment . ': virtual host file causes invalid Apache configuration');
            }
        }

        $this->reloadApache($system);
    }

    private function disableSite(SshSystem $system, File $homeDirectory, ProjectEnvironmentEntry $environment) {
        $reversedDomain = $environment->getReversedDomain();

        $siteFile = $this->getApacheSiteFile($system, $reversedDomain);
        if (!$siteFile->exists()) {
            return;
        }

        if (!$this->isValidApacheConfiguration($system)) {
            throw new Exception('Could not disable ' . $environment . ': Apache has an invalid configuration, run apache2ctl configtest');
        }

        $siteFile->delete();

        $this->reloadApache($system);
    }

    private function isValidApacheConfiguration(SshSystem $system) {
        $code = null;
        $output = $system->execute('apache2ctl configtest', $code);

        if ($code != '0') {
            return false;
        }

        return true;
    }

    private function reloadApache(SshSystem $system) {
        $code = null;
        $output = $system->execute('service apache2 reload', $code);

        if ($code == '0') {
            return true;
        }

        throw new Exception('Could not reload apache: ' . implode("\n", $output));
    }

    private function getApacheSiteFile(SshSystem $system, $reversedDomain) {
        return $system->getFileSystem()->getFile('/etc/apache2/sites-enabled/' . $reversedDomain . '.conf');
    }

    private function getApacheSslDirectory(SshSystem $system, $projectCode) {
        return $system->getFileSystem()->getFile('/etc/apache2/certs/' . $projectCode);
    }

    private function getApacheConfigFile(File $configDirectory) {
        return $configDirectory->getChild('apache2.conf');
    }

    private function getPhpConfigFile(File $configDirectory) {
        return $configDirectory->getChild('php.ini');
    }

    private function getPhpBinary($version) {
        return $this->phpFarmDirectory . '/inst/php-' . $version . '/bin/php-cgi';
    }

    public function readPhpVersions(SshSystem $system) {
        $phpFarm = $system->getFileSystem()->getFile($this->phpFarmDirectory);
        $phpFarmInst = $phpFarm->getChild('inst');

        $versions = array();

        if ($phpFarmInst->exists()) {
            $versionDirectories = $phpFarmInst->read();
            foreach ($versionDirectories as $versionDirectory) {
                if (strpos($versionDirectory->getName(), 'php-') !== 0) {
                    continue;
                }

                $version = str_replace('php-', '', $versionDirectory->getName());

                $versions[$version] = $version;
            }

            $versions = array_reverse($versions, true);
        }

        return $versions;
    }

    private function getBinDirectory(File $homeDirectory, $reversedDomain) {
        return $homeDirectory->getChild('bin/' . $reversedDomain);
    }

    private function getBackupDirectory(File $homeDirectory, $reversedDomain) {
        return $homeDirectory->getChild('bak/' . $reversedDomain);
    }

    private function getConfigDirectory(File $homeDirectory, $reversedDomain) {
        return $homeDirectory->getChild('etc/' . $reversedDomain);
    }

    private function getLogDirectory(File $homeDirectory, $reversedDomain) {
        return $homeDirectory->getChild('log/apache2');
    }

    private function getPublicDirectory(File $homeDirectory, $reversedDomain) {
        return $homeDirectory->getChild('www/' . $reversedDomain . '/public');
    }

}
