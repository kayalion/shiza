<?php

namespace shiza\controller;

use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use ride\web\base\controller\AbstractController;

class SshKeyController extends AbstractController {

    public function indexAction(OrmManager $orm) {
        $sshKeyModel = $orm->getSshKeyModel();

        $sshKeys = $sshKeyModel->find();

        $this->setTemplateView('admin/ssh-keys/index', array(
            'sshKeys' => $sshKeys,
        ));
    }

    public function formAction(OrmManager $orm, $id = null) {
        $sshKeyModel = $orm->getSshKeyModel();

        if ($id) {
            $sshKey = $sshKeyModel->getById($id);
            if (!$sshKey) {
                $this->response->setNotFound();

                return;
            }
        } else {
            $sshKey = $sshKeyModel->createEntry();
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($sshKey);
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.ssh-key.name.description'),
        ));
        $form->addRow('publicKey', 'text', array(
            'label' => $translator->translate('label.key.public'),
            'description' => $translator->translate('label.ssh-key.public.description'),
            'attributes' => array(
                'rows' => 10,
            ),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $sshKey = $form->getData();

                $sshKeyModel->save($sshKey);

                $this->addSuccess('success.data.saved', array('data' => $sshKey->getName()));

                if (!$referer) {
                    $referer = $this->getUrl('ssh-keys');
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/ssh-keys/form', array(
            'form' => $form->getView(),
            'sshKey' => $sshKey,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $id) {
        $sshKeyModel = $orm->getSshKeyModel();

        $sshKey = $sshKeyModel->getById($id);
        if (!$sshKey) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();

        if ($this->request->isPost()) {
            $sshKeyModel->delete($sshKey);

            $this->addSuccess('success.data.deleted', array('data' => $sshKey->getName()));

            if (!$referer) {
                $referer = $this->getUrl('ssh-keys');
            }

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/delete', array(
            'title' => 'title.ssh-keys',
            'subtitle' => $sshKey->getName(),
            'data' => $sshKey->getLabel(),
            'referer' => $referer,
        ));
    }

}
