<?php

namespace shiza\manager\varnish;

use ride\library\template\TemplateFacade;
use ride\library\system\file\File;
use ride\library\system\SshSystem;
use ride\library\varnish\VarnishAdmin;

use shiza\orm\entry\ProjectEnvironmentEntry;
use shiza\orm\entry\ProjectEntry;
use shiza\orm\entry\ServerEntry;

use shiza\exception\VarnishCompileException;
use shiza\service\ServerService;

use \Exception;

class SshVarnishManager implements VarnishManager {

    const TEMPLATE_NGINX_SSL_HOST = 'config/nginx.ssl.host';

    const TEMPLATE_VARNISH_SERVER = 'config/varnish.server';

    public function __construct(ServerService $serverService, TemplateFacade $templateFacade) {
        $this->serverService = $serverService;
        $this->templateFacade = $templateFacade;
    }

    public function setupVarnish(ProjectEntry $project, array $projects) {
        $server = $project->getVarnishServer();
        if (!$server) {
            return;
        }

        $system = $this->serverService->getSshSystemForServer($server);

        $this->setupSsl($system, $project);
        $this->setupProject($system, $project);
        $this->setupServer($system, $projects);

        $projects[$project->getId()] = $project;

        $this->writeServerScript($system, $projects);
    }

    public function restartVarnish(ProjectEntry $project) {
        $server = $project->getVarnishServer();
        if (!$server) {
            return;
        }

        $system = $this->serverService->getSshSystemForServer($server);

        $this->restartInstance($system, $project);
    }

    public function reloadVarnish(ProjectEntry $project) {
        $server = $project->getVarnishServer();
        if (!$server) {
            return;
        }

        $system = $this->serverService->getSshSystemForServer($server);

        $this->reloadInstance($system, $project);
    }

    public function deleteVarnish(ProjectEntry $project, array $projects) {
        $server = $project->getVarnishServer();
        if (!$server) {
            return;
        }

        $system = $this->serverService->getSshSystemForServer($server);

        $this->deleteSsl($system, $project);

        $this->stopInstance($system, $project);

        if (isset($projects[$project->getId()])) {
            unset($projects[$project->getId()]);
        }

        $this->setupServer($system, $projects);
        $this->writeServerScript($system, $projects);

        $configDirectory = $this->getConfigDirectory($system, $project);
        if ($configDirectory->exists()) {
            $configDirectory->delete();
        }

        $pidFile = $this->getPidFile($system, $project);
        if ($pidFile->exists()) {
            $pidFile->delete();
        }

        $project->setVarnishServer(null);
        $project->setVarnishMemory(null);
        $project->setVarnishPort(null);
        $project->setVarnishAdminPort(null);
    }

    private function setupSsl(SshSystem $system, ProjectEntry $project) {
        $environments = $project->getEnvironments();
        foreach ($environments as $environment) {
            if (!$environment->isValidSsl()) {
                unset($environments[$environment->getId()]);
            }
        }

        if (!$environments) {
            return;
        }

        if (!$this->isValidNginxConfiguration($system)) {
            throw new Exception('Could not setup SSL for ' . $project->getCode() . ': nginx has an invalid configuration');
        }

        $sslDirectory = $this->getNginxSslDirectory($system, $project->getCode());
        if (!$sslDirectory->exists()) {
            $sslDirectory->create();
        }

        foreach ($environments as $environment) {
            $reversedDomain = $environment->getReversedDomain();

            $certificateFile = $sslDirectory->getChild($reversedDomain . '.pem');
            $certificateFile->write($environment->getSslCertificate());

            $certificateKeyFile = $sslDirectory->getChild($reversedDomain . '.key');
            $certificateKeyFile->write($environment->getSslCertificateKey());

            $variables = array(
                'domain' => $environment->getSslCommonName(),
                'port' => $project->getVarnishServer()->getVarnishPort(),
                'certificateFile' => $certificateFile->getLocalPath(),
                'certificateKeyFile' => $certificateKeyFile->getLocalPath(),
            );

            $template = $this->templateFacade->createTemplate(self::TEMPLATE_NGINX_SSL_HOST, $variables);
            $config = $this->templateFacade->render($template);

            $configFile = $sslDirectory->getChild($reversedDomain . '.config');
            $configFile->write($config);

            $siteFile = $this->getNginxSiteFile($system, $reversedDomain);
            if (!$siteFile->exists()) {
                $system->execute('ln -s ' . $configFile->getLocalPath() . ' ' . $siteFile->getLocalPath());

                if (!$this->isValidNginxConfiguration($system)) {
                    $siteFile->delete();

                    throw new Exception('Could not enable ' . $environment . ': SSL host file causes invalid nginx configuration');
                }
            }
        }

        $this->reloadNginx($system);
    }

    private function deleteSsl(SshSystem $system, ProjectEntry $project) {
        $environments = $project->getEnvironments();
        foreach ($environments as $environment) {
            if (!$environment->getSslCertificate() || !$environment->getSslCertificateKey()) {
                unset($environments[$environment->getId()]);
            }
        }

        if (!$environments) {
            return;
        }

        if (!$this->isValidNginxConfiguration($system)) {
            throw new Exception('Could not delete SSL for ' . $project->getCode() . ': nginx has an invalid configuration');
        }

        foreach ($environments as $environment) {
            $reversedDomain = $environment->getReversedDomain();

            $siteFile = $this->getNginxSiteFile($system, $reversedDomain);
            if ($siteFile->exists()) {
                $siteFile->delete();
            }
        }

        $this->reloadNginx($system);

        $sslDirectory = $this->getNginxSslDirectory($system, $project->getCode());
        if ($sslDirectory->exists()) {
            $sslDirectory->delete();
        }
    }

    private function isValidNginxConfiguration(SshSystem $system) {
        $code = null;
        $output = $system->execute('service nginx configtest', $code);

        if ($code != 0) {
            return false;
        }

        return true;
    }

    private function reloadNginx(SshSystem $system) {
        $code = null;
        $output = $system->execute('service nginx reload', $code);

        if ($code == 0) {
            return true;
        }

        throw new Exception('Could not reload nginx: ' . implode("\n", $output));
    }

    private function setupServer(SshSystem $system, array $projects) {
        $servers = array();
        foreach ($projects as $project) {
            $environments = $project->getEnvironments();
            foreach ($environments as $environment) {
                $server = $environment->getServer();

                $servers[$server->getId()] = $server;
            }
        }

        $variables = array(
            'projects' => $projects,
            'servers' => $servers,
        );

        $template = $this->templateFacade->createTemplate(self::TEMPLATE_VARNISH_SERVER, $variables);
        $vcl = $this->templateFacade->render($template);

        $vclFile = $this->getServerVclFile($system);
        $vclFile->write($vcl);

        $code = null;

        $output = $system->execute('service varnish configtest', $code);
        if ($code != '0') {
            throw new Exception('Could not setup main Varnish instance: ' . implode("\n", $output));
        }

        $output = $system->execute('service varnish reload', $code);
        if ($code != '0') {
            throw new Exception('Could not reload main Varnish instance: ' . implode("\n", $output));
        }
    }

    private function writeServerScript(SshSystem $system, array $projects) {
        $serverScript = "#!/bin/sh\n\n";

        foreach ($projects as $project) {
            $serverScript .= $this->generateInstanceCommand($system, $project) . "\n";
        }

        $file = $this->getServerScriptFile($system);
        if (!$file->exists()) {
            $file->write($serverScript);
            $file->setPermissions(0700);
        } else {
            $file->write($serverScript);
        }
    }

    private function setupProject(SshSystem $system, $project) {
        $this->updateVcl($system, $project);

        $memory = $project->getVarnishMemory();
        if (!$memory) {
            $memory = 32;

            $project->setVarnishMemory($memory);
        }

        $port = $project->getVarnishPort();
        if (!$port) {
            $port = 10000 + $project->getId();

            $project->setVarnishPort($port);
        }

        $adminPort = $project->getVarnishAdminPort();
        if (!$adminPort) {
            $adminPort = 30000 + $project->getId();

            $project->setVarnishAdminPort($adminPort);
        }

        $secret = $this->getSecret($project);

        $this->restartInstance($system, $project, $secret);

        // test instance
        $varnishAdm = $this->createVarnishAdmin($system, $project, $secret);
        $varnishAdm->connect();
        $varnishAdm->disconnect();
    }

    private function createVarnishAdmin(SshSystem $system, ProjectEntry $project, $secret = null) {
        if (!$secret) {
            $secret = $this->getSecret($project);
        }

        $ipAddress = $project->getVarnishServer()->getIpAddress();
        $adminPort = $project->getVarnishAdminPort();

        $varnishAdm = new VarnishAdmin($ipAddress, $adminPort, $secret);
        $varnishAdm->setLog($this->serverService->getLog());

        return $varnishAdm;
    }

    private function startInstance(SshSystem $system, ProjectEntry $project, $secret = null) {
        $command = $this->generateInstanceCommand($system, $project, $secret);
        // $command .= ' -F';
        // $command .= ' > /tmp/varnish.log 2> /tmp/varnish.log';

        // for some reason the instance keeps shutting down without ps aux
        // behind it
        $command .= '; ps aux';

        $code = null;
        $system->execute($command, $code);
    }

    private function stopInstance(SshSystem $system, ProjectEntry $project) {
        $pidFile = $this->getPidFile($system, $project);
        if (!$pidFile->exists()) {
            return false;
        }

        $pid = $pidFile->read();

        $output = $system->execute('kill ' . $pid);

        sleep(3);

        $pidFile->delete();

        return true;
    }

    private function restartInstance(SshSystem $system, ProjectEntry $project, $secret = null) {
        $this->stopInstance($system, $project);
        $this->startInstance($system, $project, $secret);
    }

    private function reloadInstance(SshSystem $system, ProjectEntry $project) {
        $vclFile = $this->updateVcl($system, $project);

        $varnishAdmin = $this->createVarnishAdmin($system, $project);
        $varnishAdmin->connect();
        $varnishAdmin->loadAndUseVclFromFile($vclFile->getLocalPath());
        $varnishAdmin->disconnect();
    }

    private function generateInstanceCommand(SshSystem $system, ProjectEntry $project, $secret = null) {
        $vclFile = $this->getProjectVclFile($system, $project);
        $pidFile = $this->getPidFile($system, $project);
        $secretFile = $this->initializeSecret($system, $project, $secret);

        $command = 'varnishd';
        $command .= ' -a :' . $project->getVarnishPort();
        $command .= ' -T :' . $project->getVarnishAdminPort();
        $command .= ' -f ' . $vclFile->getLocalPath();
        $command .= ' -P ' . $pidFile->getLocalPath();;
        $command .= ' -S ' . $secretFile->getLocalPath();;
        // $command .= ' -i ' . $project->getId();
        // $command .= ' -u ' . $username . ' -g ' . $username;
        $command .= ' -s malloc,' . $project->getVarnishMemory() . 'm';

        return $command;
    }

    private function getSecret(ProjectEntry $project) {
        $secret = $project->getVarnishSecret();
        $secret = $this->serverService->decrypt($secret);

        return $secret;
    }

    private function initializeSecret(SshSystem $system, ProjectEntry $project, $secret = null) {
        if (!$secret) {
            $secret = $this->getSecret($project);
        }

        $secretFile = $this->getSecretFile($system, $project);
        if (!$secretFile->exists()) {
            $secretFile->write($secret);
            $secretFile->setPermissions(0600);
        }

        return $secretFile;
    }

    private function updateVcl(SshSystem $system, ProjectEntry $project) {
        $vclFile = $this->getProjectVclFile($system, $project);
        $vcl = $this->initializeVcl($system, $project);

        $this->validateAndWriteVclFile($system, $vclFile, $vcl);

        return $vclFile;
    }

    private function initializeVcl(SshSystem $system, ProjectEntry $project) {
        $vcl = $project->getVarnishVcl();
        if ($vcl) {
            return $vcl;
        }

        $default = $system->getFileSystem()->getFile('/usr/share/doc/varnish/examples/example.vcl');
        if (!$default->exists()) {
            throw new Exception('No VCL set and no default VCL as fallback');
        }

        $vcl = $default->read();

        $project->setVarnishVcl($vcl);

        return $vcl;
    }

    private function validateAndWriteVclFile(SshSystem $system, File $vclFile, $vcl) {
        $vclTestFile = $vclFile->getCopyFile();
        $vclTestFile->write($vcl);

        $code = null;
        $output = $system->execute('varnishd -C -f ' . $vclTestFile->getLocalPath(), $code);

        if ($vclFile->exists()) {
            $vclTestFile->delete();

            $isNew = false;
        } else {
            $isNew = true;
        }

        if ($code != '0') {
            throw new VarnishCompileException(implode("\n", $output));
        } elseif (!$isNew) {
            $vclFile->write($vcl);
        }
    }

    private function getProjectVclFile(SshSystem $system, ProjectEntry $project) {
        return $this->getConfigDirectory($system, $project)->getChild('default.vcl');
    }

    private function getServerVclFile(SshSystem $system) {
        return $system->getFileSystem()->getFile('/etc/varnish/projects.vcl');
    }

    private function getServerScriptFile(SshSystem $system) {
        return $system->getFileSystem()->getFile('/root/varnish-projects.sh');
    }

    private function getPidFile(SshSystem $system, ProjectEntry $project) {
        return $system->getFileSystem()->getFile('/var/run/varnish-' . $project->getUsername() . '.pid');
    }

    private function getSecretFile(SshSystem $system, ProjectEntry $project) {
        return $this->getConfigDirectory($system, $project)->getChild('secret');
    }

    private function getConfigDirectory(SshSystem $system, ProjectEntry $project) {
        return $system->getFileSystem()->getFile('/etc/varnish/projects/' . $project->getUsername());
    }

    private function getNginxSiteFile(SshSystem $system, $reversedDomain) {
        return $system->getFileSystem()->getFile('/etc/nginx/sites-enabled/' . $reversedDomain . '.conf');
    }

    private function getNginxSslDirectory(SshSystem $system, $projectCode) {
        return $system->getFileSystem()->getFile('/etc/nginx/certs/' . $projectCode);
    }

}
