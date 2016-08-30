<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\ProjectEnvironmentEntry as OrmProjectEnvironmentEntry;

class ProjectEnvironmentEntry extends OrmProjectEnvironmentEntry {

    private $skipQueue = false;

    public function __toString() {
        return $this->getDomain();
    }

    public function getName() {
        return $this->getDomain();
    }

    public function setSkipQueue($skipQueue = true) {
        $this->skipQueue = $skipQueue;
    }

    public function willSkipQueue() {
        return $this->skipQueue;
    }

    public function getReversedDomain() {
        $domain = $this->getDomain();
        $tokens = explode('.', $domain);
        $tokens = array_reverse($tokens);

        return implode('.', $tokens);
    }

    public function isSslChanged() {
        if (!$this->getId()) {
            if ($this->isSslActive() || $this->getSslManager() || $this->getSslCommonName() || $this->getSslCertificate() || $this->getSslCertificateKey()) {
                return true;
            }

            return false;
        }

        if ($this->isValueLoaded('isSslActive') && $this->getLoadedValues('isSslActive') != $this->isSslActive()) {
            return true;
        }
        if ($this->isValueLoaded('sslManager') && $this->getLoadedValues('sslManager') != $this->getSslManager()) {
            return true;
        }
        if ($this->isValueLoaded('sslCommonName') && $this->getLoadedValues('sslCommonName') != $this->getSslCommonName()) {
            return true;
        }
        if ($this->isValueLoaded('sslCertificate') && $this->getLoadedValues('sslCertificate') != $this->getSslCertificate()) {
            return true;
        }
        if ($this->isValueLoaded('sslCertificateKey') && $this->getLoadedValues('sslCertificateKey') != $this->getSslCertificateKey()) {
            return true;
        }

        return false;
    }

    public function isValidSsl() {
        return $this->isSslActive() && $this->getSslManager() && $this->getSslCommonName() && $this->getSslCertificate() && $this->getSslCertificateKey();
    }

    public function isAliasChanged() {
        if (!$this->getId()) {
            if ($this->getAlias()) {
                return true;
            }

            return false;
        }

        if ($this->isValueLoaded('alias') && $this->getLoadedValues('alias') != $this->getAlias()) {
            return true;
        }

        return false;
    }

    public function getAliases() {
        $alias = $this->getAlias();
        if (!$alias) {
            return array();
        }

        $aliases = array();

        $tokens = explode("\n", str_replace("\r", '', $alias));
        foreach ($tokens as $token) {
            $aliases[$token] = $token;
        }

        return $aliases;
    }

    public function getDatabases() {
        $database = $this->getDatabase();
        if (!$database) {
            return array();
        }

        return explode("\n", $database);
    }

}
