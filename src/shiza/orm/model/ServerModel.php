<?php

namespace shiza\orm\model;

use ride\library\orm\model\GenericModel;

use shiza\orm\entry\ServerEntry;

use \Exception;

class ServerModel extends GenericModel {

    protected function saveEntry($entry) {
        if (!$entry->getFingerprint()) {
            $this->testSshConnection($entry);
        }

        if ($entry->isDatabasePasswordChanged()) {
            $serverService = $this->orm->getDependencyInjector()->get('shiza\\service\\ServerService');

            $entry->encryptDatabasePassword($serverService);
        }

        if ($entry->isVarnishSecretChanged()) {
            $serverService = $this->orm->getDependencyInjector()->get('shiza\\service\\ServerService');

            $entry->encryptVarnishSecret($serverService);
        }

        parent::saveEntry($entry);
    }


    public function rotateBackups(ServerEntry $server = null) {
        if ($server) {
            $servers = array($server);
        } else {
            $servers = $this->find();
        }

        foreach ($servers as $server) {
            $this->rotateServerBackups($server);
        }
    }

    private function rotateServerBackups(ServerEntry $server) {
        $serverService = $this->orm->getDependencyInjector()->get('shiza\\service\\ServerService');

        if ($server->isWeb()) {
            $webManager = $serverService->getWebManager($server->getWebManager());
            $environmentModel = $this->orm->getProjectEnvironmentModel();

            $environments = $environmentModel->find(array('filter' => array('server' => $server)));
            foreach ($environments as $environment) {
                $webManager->rotateBackup($environment);
            }
        }

        if ($server->isDatabase()) {
            $databaseManager = $serverService->getDatabaseManager($server->getDatabaseManager());
            $databaseModel = $this->orm->getProjectDatabaseModel();

            $databases = $databaseModel->find(array('filter' => array('server' => $server)));
            foreach ($databases as $database) {
                $databaseManager->rotateBackup($database);
            }
        }
    }

    public function calculateDiskUsages() {
        $servers = $this->find();
        foreach ($servers as $server) {
            $this->calculateDiskUsage($server);
        }
    }

    public function calculateDiskUsage(ServerEntry $server) {
        $serverService = $this->orm->getDependencyInjector()->get('shiza\\service\\ServerService');

        if ($server->isWeb()) {
            $projectEnvironmentModel = $this->orm->getProjectEnvironmentModel();
            $projectEnvironments = $projectEnvironmentModel->find(array('filter' => array('server' => $server)));

            $webManager = $serverService->getWebManager($server->getWebManager());
            $webManager->calculateDiskUsage($server, $projectEnvironments);

            $projectEnvironmentModel->save($projectEnvironments);
        }

        if ($server->isDatabase()) {
            $projectDatabaseModel = $this->orm->getProjectDatabaseModel();
            $projectDatabases = $projectDatabaseModel->find(array('filter' => array('server' => $server)));

            $databaseManager = $serverService->getDatabaseManager($server->getDatabaseManager());
            $databaseManager->calculateDiskUsage($server, $projectDatabases);

            $projectDatabaseModel->save($projectDatabases);
        }
    }

    public function testSshConnection($entry) {
        $ipAddress = gethostbyname($entry->getHost());
        if ($ipAddress != $entry->getHost()) {
            $entry->setIpAddress($ipAddress);
        }

        $serverService = $this->orm->getDependencyInjector()->get('shiza\\service\\ServerService');

        $sshSystem = $serverService->getSshSystemForServer($entry);

        try {
            $sshSystem->connect();

            $entry->setFingerprint($sshSystem->getFingerprint());
            $entry->setFingerprintError(null);

            if ($entry->isWeb()) {
                $webManager = $serverService->getWebManager($entry->getWebManager());

                $entry->setPhpVersions($webManager->getPhpVersions($entry));
            }

            $sshSystem->disconnect();

            return true;
        } catch (Exception $exception) {
            $error = '';
            $tab = '';
            $symbol = '&rdsh; ';

            do {
                $error .= $exception->getMessage();
                $exception = $exception->getPrevious();
                if ($exception) {
                    $error .= "\n" . $tab . $symbol;
                    $tab .= '   ';
                }
            } while ($exception);

            $entry->setFingerprint(null);
            $entry->setFingerprintError($error);

            return false;
        }
    }

}
