<?php

namespace shiza\controller;

use ride\library\i18n\translator\Translator;
use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use shiza\service\ServerService;

class ServerController extends AbstractController {

    public function indexAction(OrmManager $orm, ServerService $serverService) {
        $serverModel = $orm->getServerModel();

        $servers = $serverModel->find();

        $publicKey = $serverService->getPublicKey();

        $this->setTemplateView('admin/servers/index', array(
            'servers' => $servers,
            'publicKey' => $publicKey,
        ));
    }

    public function formAction(OrmManager $orm, ServerService $serverService, $id = null) {
        $serverModel = $orm->getServerModel();


        if ($id) {
            $server = $this->getEntry($serverModel, $id, 'slug');
            if (!$server) {
                $this->response->setNotFound();

                return;
            }
        } else {
            $server = $serverModel->createEntry();
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($server);
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.server.name.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('host', 'string', array(
            'label' => $translator->translate('label.host'),
            'description' => $translator->translate('label.server.host.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('port', 'integer', array(
            'label' => $translator->translate('label.port'),
            'description' => $translator->translate('label.server.port.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('username', 'string', array(
            'label' => $translator->translate('label.username'),
            'description' => $translator->translate('label.server.username.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('authManager', 'option', array(
            'label' => $translator->translate('label.manager'),
            'description' => $translator->translate('label.manager.auth.description'),
            'options' => $this->getAuthManagerOptions($translator),
            'widget' => 'select',
        ));
        $form->addRow('isWeb', 'option', array(
            'label' => $translator->translate('label.web'),
            'description' => $translator->translate('label.server.web.description'),
            'attributes' => array(
                'data-toggle-dependant' => 'option-web',
            ),
        ));
        $form->addRow('webManager', 'option', array(
            'label' => $translator->translate('label.manager'),
            'description' => $translator->translate('label.manager.web.description'),
            'options' => $this->getWebManagerOptions($translator),
            'attributes' => array(
                'class' => 'option-web option-web-1',
            ),
            'widget' => 'select',
        ));
        $form->addRow('isDatabase', 'option', array(
            'label' => $translator->translate('label.database'),
            'description' => $translator->translate('label.server.database.description'),
            'attributes' => array(
                'data-toggle-dependant' => 'option-database',
            ),
        ));
        $form->addRow('databaseManager', 'option', array(
            'label' => $translator->translate('label.manager'),
            'description' => $translator->translate('label.manager.database.description'),
            'options' => $this->getDatabaseManagerOptions($translator),
            'attributes' => array(
                'class' => 'option-database option-database-1',
            ),
            'widget' => 'select',
        ));
        $form->addRow('databaseUsername', 'string', array(
            'label' => $translator->translate('label.username'),
            'description' => $translator->translate('label.database.username.description'),
            'attributes' => array(
                'class' => 'option-database option-database-1',
            ),
        ));
        $form->addRow('databasePassword', 'password', array(
            'label' => $translator->translate('label.password'),
            'description' => $translator->translate('label.database.password.description'),
            'attributes' => array(
                'class' => 'option-database option-database-1',
            ),
        ));
        $form->addRow('isCron', 'option', array(
            'label' => $translator->translate('label.cron'),
            'description' => $translator->translate('label.server.cron.description'),
            'attributes' => array(
                'data-toggle-dependant' => 'option-cron',
            ),
        ));
        $form->addRow('cronManager', 'option', array(
            'label' => $translator->translate('label.manager'),
            'description' => $translator->translate('label.manager.cron.description'),
            'options' => $this->getCronManagerOptions($translator),
            'attributes' => array(
                'class' => 'option-cron option-cron-1',
            ),
            'widget' => 'select',
        ));
        $form->addRow('isVarnish', 'option', array(
            'label' => $translator->translate('label.varnish'),
            'description' => $translator->translate('label.server.varnish.description'),
            'attributes' => array(
                'data-toggle-dependant' => 'option-varnish',
            ),
        ));
        $form->addRow('varnishManager', 'option', array(
            'label' => $translator->translate('label.manager'),
            'description' => $translator->translate('label.manager.varnish.description'),
            'options' => $this->getVarnishManagerOptions($translator),
            'attributes' => array(
                'class' => 'option-varnish option-varnish-1',
            ),
            'widget' => 'select',
        ));
        $form->addRow('varnishPort', 'string', array(
            'label' => $translator->translate('label.port'),
            'description' => $translator->translate('label.server.varnish.port.description'),
            'attributes' => array(
                'class' => 'option-varnish option-varnish-1',
            ),
        ));
        $form->addRow('varnishAdminPort', 'string', array(
            'label' => $translator->translate('label.port.admin'),
            'description' => $translator->translate('label.server.varnish.port.admin.description'),
            'attributes' => array(
                'class' => 'option-varnish option-varnish-1',
            ),
        ));
        $form->addRow('varnishSecret', 'password', array(
            'label' => $translator->translate('label.secret'),
            'description' => $translator->translate('label.server.varnish.secret.description'),
            'attributes' => array(
                'class' => 'option-varnish option-varnish-1',
            ),
        ));
        $form->addRow('varnishMemory', 'option', array(
            'label' => $translator->translate('label.memory'),
            'description' => $translator->translate('label.server.varnish.memory.description'),
            'attributes' => array(
                'class' => 'option-varnish option-varnish-1',
            ),
            'options' => $serverService->getMemoryOptions($translator),
            'widget' => 'select',
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $server = $form->getData();

                $serverModel->save($server);

                $this->addSuccess('success.data.saved', array('data' => (string) $server));

                if (!$referer) {
                    $referer = $this->getUrl('servers');
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/servers/form', array(
            'form' => $form->getView(),
            'server' => $server,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $id) {
        $serverModel = $orm->getServerModel();

        $server = $serverModel->getById($id);
        if (!$server) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->request->getQueryParameter('referer');

        if ($this->request->isPost()) {
            try {
                $serverModel->delete($server);

                $this->addSuccess('success.data.deleted', array('data' => (string) $server));
            } catch (ValidationException $exception) {
                $this->addError('error.validation.server.used', array('data' => (string) $server));
            }

            $referer = $this->getUrl('servers');

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/delete', array(
            'title' => 'title.servers',
            'subtitle' => $server->getHost(),
            'data' => (string) $server,
            'referer' => $referer,
        ));
    }

    public function testAction(OrmManager $orm, $id) {
        $serverModel = $orm->getServerModel();

        $server = $serverModel->getById($id);
        if (!$server) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->request->getQueryParameter('referer');

        if ($this->request->isPost()) {
            $serverModel->testSshConnection($server);
            $serverModel->save($server);

            $this->addSuccess('success.data.saved', array('data' => (string) $server));

            if (!$referer) {
                $referer = $this->getUrl('servers');
            }

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/servers/test', array(
            'server' => $server,
            'referer' => $referer,
        ));
    }

    public function getAuthManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\auth\\AuthManager', 'manager.auth.');
    }

    public function getDatabaseManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\database\\DatabaseManager', 'manager.database.');
    }

    public function getWebManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\web\\WebManager', 'manager.web.');
    }

    public function getCronManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\cron\\CronManager', 'manager.cron.');
    }

    public function getVarnishManagerOptions(Translator $translator) {
        return $this->getManagerOptions($translator, 'shiza\\manager\\varnish\\VarnishManager', 'manager.varnish.');
    }

}
