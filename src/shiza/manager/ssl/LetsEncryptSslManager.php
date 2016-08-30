<?php

namespace shiza\manager\ssl;

use ride\library\system\System;

use shiza\orm\entry\ProjectEnvironmentEntry;

use shiza\service\ServerService;

use \Exception;

class LetsEncryptSslManager implements SslManager {

    public function __construct(ServerService $serverService) {
        $this->serverService = $serverService;
        $this->certbot = '/opt/certbot/certbot-auto';
        $this->renewalDays = 3;
        $this->isDebug = true;
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

        // initialize server connection
        $server = $environment->getServer();
        $system = $this->serverService->getSshSystemForServer($server);
        $fileSystem = $system->getFileSystem();

        // check certificate
        $certificateFile = $fileSystem->getFile('/etc/letsencrypt/live/' . $domain . '/fullchain.pem');
        $certificateKeyFile = $fileSystem->getFile('/etc/letsencrypt/live/' . $domain . '/privkey.pem');

        $expires = $this->getExpirationDate($system, $certificateFile);
        if ($expires && $expires < time() - ($this->renewalDays * 86400)) {
            return false;
        }

        // generate a new certificate
        $webManager = $this->serverService->getAuthManager($environment->getServer()->getWebManager());
        $webRoot = $webManager->getWebRoot($environment);

        $command = $this->certbot . ' certonly';
        $command .= ' --non-interactive';
        $command .= ' --text';
        $command .= ' --agrees-tos';
        $command .= ' --webroot -w ' . $webRoot;
        $command .= ' --domain ' . $domain;
        $command .= ' --email ' . $email;
        if ($this->isDebug) {
            $command .= ' --test-cert';
        }

        $code = null;
        $output = $system->execute($command, $code);

        if ($code != 0) {
            throw new Exception('Could not generate SSL certificate: ' . $command . "\n" . implode("\n", $output));
        }

        $environment->setSslCertificate($certificateFile->read());
        $environment->setSslCertificateKey($keyFile->read());
        $environment->setSslCertificateExpires($this->getExpirationDate($system, $certificateFile));

        return true;
    }

    private function getExpirationDate(System $system, File $certificateFile) {
        if (!$certificateFile->exists()) {
            return false;
        }

        $code = null;
        $output = $system->execute('openssl x509 -enddate -noout -in ' . $certificateFile->getLocalPath(), $code);

        if ($code != 0) {
            throw new Exception('Could not get expiration date of certificate: ' . $command . "\n" . implode("\n", $output));
        }

        $output = array_shift($output);
        $output = str_replace('notAfter=', '', $output);

        $format = 'M j H:i:s Y e';

        $date = date_create_from_format($format, $output);
        if ($date === false) {
            throw new Exception('Could not parse date ' . $output);
        }

        return $date->getTimestamp();
    }

}
