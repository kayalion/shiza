<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\KeyChainEntry;
use ride\application\orm\entry\ProjectEntry as OrmProjectEntry;

use shiza\service\ServerService;

class ProjectEntry extends OrmProjectEntry {

    const VARNISH_SETUP = 1;

    const VARNISH_RESTART = 2;

    const VARNISH_RELOAD = 3;

    const VARNISH_DELETE = 4;

    private $skipQueue = false;

    private $isCredentialsChanged = false;

    public function __toString() {
        return $this->getName();
    }

    public function setSkipQueue($skipQueue = true) {
        $this->skipQueue = $skipQueue;
    }

    public function willSkipQueue() {
        return $this->skipQueue;
    }

    public function getUsername() {
        return strtolower($this->getCode());
    }

    public function generatePassword(ServerService $serverService) {
        $password = $serverService->generateString();
        $encryptedPassword = $serverService->encrypt($password);

        $this->setPassword($encryptedPassword);

        $this->isCredentialsChanged = true;

        return $password;
    }

    public function generateVarnishSecret(ServerService $serverService) {
        $secret = $serverService->generateString(32);
        $encryptedSecret = $serverService->encrypt($secret);

        $this->setVarnishSecret($encryptedSecret);

        return $secret;
    }

    public function getAllSshKeys() {
        $sshKeys = $this->getSshKeys();

        $keyChains = $this->getKeyChains();
        foreach ($keyChains as $keyChain) {
            $chainSshKeys = $keyChain->getSshKeys();
            foreach ($chainSshKeys as $sshKey) {
                $sshKeys[$sshKey->getId()] = $sshKey;
            }
        }

        return $sshKeys;
    }

    public function getSshKeysAsString() {
        $authorizedKeys = '';

        foreach ($this->getAllSshKeys() as $sshKey) {
            $authorizedKeys .= (string) $sshKey . "\n";
        }

        return $authorizedKeys;
    }

    public function addToSshKeys(SshKeyEntry $sshKey) {
        $this->isCredentialsChanged = true;

        parent::addToSshKeys($sshKey);
    }

    public function removeFromSshKeys(SshKeyEntry $sshKey) {
        $this->isCredentialsChanged = true;

        parent::removeFromSshKeys($sshKey);
    }

    public function setSshKeys(array $sshKeys = array()) {
        $this->isCredentialsChanged = true;

        parent::setSshKeys($sshKeys);
    }

    public function addToKeyChains(KeyChainEntry $keyChain) {
        $this->isCredentialsChanged = true;

        parent::addToKeyChains($keyChain);
    }

    public function removeFromKeyChains(KeyChainEntry $keyChain) {
        $this->isCredentialsChanged = true;

        parent::removeFromKeyChains($keyChain);
    }

    public function setKeyChains(array $keyChains = array()) {
        $this->isCredentialsChanged = true;

        parent::setKeyChains($keyChains);
    }

    public function isCredentialsChanged() {
        return $this->isCredentialsChanged;
    }

    public function isCronChanged() {
        if (!$this->getId()) {
            if ($this->getCronServer() && $this->getCronTab()) {
                return true;
            }

            return false;
        }

        if ($this->isValueLoaded('cronTab') && $this->getLoadedValues('cronTab') != $this->getCronTab()) {
            return true;
        }

        return false;
    }

    public function isVarnishChanged() {
        if (!$this->getId()) {
            if ($this->getVarnishServer()) {
                return self::VARNISH_SETUP;
            }

            return false;
        }

        if ($this->isValueLoaded('varnishServer')) {
            $loadedServer = $this->getLoadedValues('varnishServer');
            $setServer = $this->getVarnishServer();

            if (($loadedServer && (!$setServer || $loadedServer->getId() != $setServer->getId())) || (!$loadedServer && $setServer)) {
                return self::VARNISH_SETUP;
            }
        }

        if ($this->isValueLoaded('varnishMemory') && $this->getLoadedValues('varnishMemory') != $this->getVarnishMemory()) {
            if (!$this->getVarnishMemory()) {
                return self::VARNISH_DELETE;
            }

            return self::VARNISH_SETUP;
        }

        if ($this->isValueLoaded('varnishVcl') && $this->getLoadedValues('varnishVcl') != $this->getVarnishVcl()) {
            return self::VARNISH_RELOAD;
        }

        return false;
    }

    public function isServerUsed(ServerEntry $server) {
        $databases = $this->getDatabases();
        foreach ($databases as $database) {
            if ($database->getServer()->getId() == $server->getId()) {
                return true;
            }
        }

        if ($this->getCronTab() && $this->getCronServer() && $this->getCronServer()->getId() == $server->getId()) {
            return true;
        }

        return false;
    }

    public function getActiveDatabases() {
        $databases = $this->getDatabases();

        foreach ($databases as $database) {
            if ($database->isDeleted()) {
                unset($databases[$database->getId()]);
            }
        }

        return $databases;
    }

}
