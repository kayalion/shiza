<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\SshKeyEntry as OrmSshKeyEntry;

class SshKeyEntry extends OrmSshKeyEntry {

    public function __toString() {
        return $this->getPublicKey() . ' ' . $this->getName();
    }

    public function getLabel() {
        return $this->getName() . ' (' . $this->getPublicKeyTruncated() . ')';
    }

    public function setPublicKey($publicKey) {
        $tokens = explode(' ', $publicKey);
        if (count($tokens) >= 2) {
            $publicKey = $tokens[0] . ' ' . $tokens[1];
        }

        $name = $this->getName();
        if (!$name) {
            if (isset($tokens[2])) {
                $this->setName($tokens[2]);
            } else {
                $this->setName($this->truncatePublicKey($publicKey, 16));
            }
        }

        parent::setPublicKey($publicKey);
    }

    public function getPublicKeyTruncated($size = 20) {
        return $this->truncatePublicKey($this->getPublicKey(), $size);
    }

    private function truncatePublicKey($publicKey, $size) {
        $tokens = explode(' ', $publicKey);

        if (count($tokens) == 1) {
            return substr($publicKey, 0, $size);
        }

        $partSize = floor($size / 2);

        return $tokens[0] . ' ' . substr($tokens[1], 0, $partSize) . '...' . substr($tokens[1], $partSize * -1);
    }

}
