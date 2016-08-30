<?php

namespace shiza\service;

use ride\library\encryption\cipher\Cipher;
use ride\library\i18n\translator\Translator;
use ride\library\system\authentication\PasswordSshAuthentication;
use ride\library\system\authentication\PublicKeySshAuthentication;
use ride\library\system\SshSystem;
use ride\library\system\System;
use ride\library\StringHelper;

use shiza\orm\entry\ProjectEnvironmentEntry;
use shiza\orm\entry\RepositoryDeployerEntry;
use shiza\orm\entry\ServerEntry;

use \Exception;

class ServerService {

    public function __construct(System $system, Cipher $cipher) {
        $this->system = $system;
        $this->cipher = $cipher;
    }

    public function getLocalSystem() {
        return $this->system;
    }

    public function getLog() {
        return $this->system->getLog();
    }

    public function getPrivateKeyFile() {
        $application = $this->system->getFileBrowser()->getApplicationDirectory();
        $privateKeyFile = $application->getChild('data/keys/server.key');
        if (!$privateKeyFile->exists()) {
            return null;
        }

        return $privateKeyFile;
    }

    public function getPublicKeyFile() {
        $application = $this->system->getFileBrowser()->getApplicationDirectory();
        $publicKeyFile = $application->getChild('data/keys/server.key.pub');
        if (!$publicKeyFile->exists()) {
            $privateKeyFile = $application->getChild('data/keys/server.key');
            $privateKeyFile->getParent()->create();

            $this->system->execute('ssh-keygen -t rsa -N "" -f ' . $privateKeyFile);
        }

        return $publicKeyFile;
    }

    public function getPublicKey() {
        $publicKeyFile = $this->getPublicKeyFile();
        if (!$publicKeyFile) {
            return null;
        }

        return $publicKeyFile->read();
    }

    public function getSshSystemForServer(ServerEntry $server) {
        if (isset($this->systems[$server->getId()])) {
            return $this->systems[$server->getId()];
        }

        $authentication = new PublicKeySshAuthentication();
        $authentication->setUsername($server->getUsername());
        $authentication->setPublicKeyFile($this->getPublicKeyFile());
        $authentication->setPrivateKeyFile($this->getPrivateKeyFile());

        $system = new SshSystem($authentication, $server->getHost(), $server->getPort());
        $system->setLog($this->system->getLog());
        if ($server->getFingerprint()) {
            $system->setHostKeys(array(
                $server->getHost() => $server->getFingerprint(),
            ));
        }

        $this->systems[$server->getId()] = $system;

        return $system;
    }

    public function getSshSystemForDeployer(RepositoryDeployerEntry $deployer) {
        if ($deployer->getUseKey()) {
            $authentication = new PublicKeySshAuthentication();
            $authentication->setPublicKeyFile($this->getPublicKeyFile());
            $authentication->setPrivateKeyFile($this->getPrivateKeyFile());
        } else {
            $authentication = new PasswordSshAuthentication();
            $authentication->setPassword($this->decrypt($deployer->getRemotePassword()));
        }

        $authentication->setUsername($deployer->getRemoteUsername());

        $system = new SshSystem($authentication, $deployer->getRemoteHost(), $deployer->getRemotePort());
        $system->setLog($this->system->getLog());
        if ($deployer->getFingerprint()) {
            $system->setHostKeys(array(
                $deployer->getRemoteHost() => $deployer->getFingerprint(),
            ));
        }

        return $system;
    }

    public function generateString($size = 16) {
        return StringHelper::generate($size, '123456789bcdfghjkmnpqrstvwxyzABCDEFGHJKLMNPQRSTUVWXYZ');
    }

    public function encrypt($plain) {
        return $this->cipher->encrypt($plain, $this->system->getSecretKey());
    }

    public function decrypt($encrypted) {
        return $this->cipher->decrypt($encrypted, $this->system->getSecretKey());
    }

    public function getBackupHelper() {
        return $this->system->getDependencyInjector()->get('shiza\\helper\\BackupHelper');
    }

    public function getCronHelper() {
        return $this->system->getDependencyInjector()->get('shiza\\helper\\CronHelper');
    }

    public function getMemoryOptions($translator, $maximum = null) {
        $values = array(
            32,
            64,
            128,
            256,
            384,
            512,
            768,
            1024,
            2048,
            3072,
            4096,
            8192,
        );

        $options = array();
        foreach ($values as $value) {
            if ($maximum && $value > $maximum) {
                break;
            }

            $options[$value] = $translator->translate('memory.' . $value);
        }

        return $options;
    }

    public function getAuthManager($id) {
        if (!$id) {
            throw new Exception('Could not get auth manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\auth\\AuthManager', $id);
    }

    public function getCronManager($id) {
        if (!$id) {
            throw new Exception('Could not get cron manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\cron\\CronManager', $id);
    }

    public function getDatabaseManager($id) {
        if (!$id) {
            throw new Exception('Could not database auth manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\database\\DatabaseManager', $id);
    }

    public function getWebManager($id) {
        if (!$id) {
            throw new Exception('Could not get web manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\web\\WebManager', $id);
    }

    public function getVarnishManager($id) {
        if (!$id) {
            throw new Exception('Could not get varnish manager: no id provided');
        }

        return $this->system->getDependencyInjector()->get('shiza\\manager\\varnish\\VarnishManager', $id);
    }

}
