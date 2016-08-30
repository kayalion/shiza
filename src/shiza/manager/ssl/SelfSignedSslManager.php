<?php

namespace shiza\manager\ssl;

use ride\library\system\System;

use shiza\orm\entry\ProjectEnvironmentEntry;

use shiza\service\ServerService;

use \Exception;

class SelfSignedSslManager implements SslManager {

    public function __construct(ServerService $serverService) {
        $this->serverService = $serverService;
        $this->days = 30;
        $this->renewalDays = 3;
    }

    public function generateCertificate(ProjectEnvironmentEntry $environment) {
        // validate input
        $domain = $environment->getSslCommonName();
        if (!$domain) {
            throw new Exception('No domain provided');
        }

        $email = $environment->getSslEmail();
        if (!$email) {
            throw new Exception('No email address provided');
        }

        if (!$this->needsGenerate($environment)) {
            return false;
        }

        $system = $this->serverService->getLocalSystem();
        $fileSystem = $system->getFileSystem();
        $project = $environment->getProject();

        $directory = $fileSystem->getTemporaryFile();
        if ($directory->exists()) {
            $directory->delete();
            $directory->create();
        }

        $certificateFile = $directory->getChild($project->getCode() . '.pem');
        $keyFile = $directory->getChild($project->getCode() . '.key');

        $command = 'openssl req';
        $command .= ' -x509';
        $command .= ' -nodes';
        $command .= ' -days ' . $this->days;
        $command .= ' -newkey rsa:2048';
        $command .= ' -keyout ' . $keyFile->getAbsolutePath();
        $command .= ' -out ' . $certificateFile->getAbsolutePath();

        $command .= ' -subj "';
        $command .= '/C=' . $environment->getSslCountry();
        $command .= '/ST=' . $environment->getSslState();
        $command .= '/L=' . $environment->getSslLocality();
        $command .= '/O=' . $environment->getSslOrganisation();
        $command .= '/CN=' . $environment->getSslCommonName();
        $command .= '/emailAddress=' . $environment->getSslEmail();
        $command .= '"';

        $output = $system->execute($command, $code);

        if ($code != 0) {
            $directory->delete();

            throw new Exception('Could not generate SSL certificate: ' . $command . "\n" . implode("\n", $output));
        }

        $environment->setSslCertificate($certificateFile->read());
        $environment->setSslCertificateKey($keyFile->read());
        $environment->setSslCertificateExpires(time() + ($this->days * 86400));

        $directory->delete();

        return true;
    }

    private function needsGenerate(ProjectEnvironmentEntry $environment) {
        $expires = $environment->getSslCertificateExpires();
        if (!$expires || $expires > time() - ($this->renewalDays * 86400)) {
            return true;
        }

        if ($environment->getSslCertificate() && $environment->getSslCertificateKey()) {
            return false;
        }

        return true;
    }

}
