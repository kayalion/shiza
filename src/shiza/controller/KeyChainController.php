<?php

namespace shiza\controller;

use ride\library\orm\OrmManager;
use ride\library\validation\exception\ValidationException;

use ride\web\base\controller\AbstractController;

class KeyChainController extends AbstractController {

    public function indexAction(OrmManager $orm) {
        $keyChainModel = $orm->getKeyChainModel();

        $keyChains = $keyChainModel->find();

        $this->setTemplateView('admin/key-chains/index', array(
            'keyChains' => $keyChains,
        ));
    }

    public function formAction(OrmManager $orm, $id = null) {
        $keyChainModel = $orm->getKeyChainModel();
        $sshKeyModel = $orm->getSshKeyModel();

        if ($id) {
            $keyChain = $keyChainModel->getById($id);
            if (!$keyChain) {
                $this->response->setNotFound();

                return;
            }
        } else {
            $keyChain = $keyChainModel->createEntry();
        }

        $referer = $this->getReferer();
        $translator = $this->getTranslator();

        $form = $this->createFormBuilder($keyChain);
        $form->addRow('name', 'string', array(
            'label' => $translator->translate('label.name'),
            'description' => $translator->translate('label.key-chain.name.description'),
            'validators' => array(
                'required' => array(),
            ),
        ));
        $form->addRow('sshKeys', 'object', array(
            'label' => $translator->translate('label.ssh-keys'),
            'description' => $translator->translate('label.key-chain.ssh-keys.description'),
            'options' => $sshKeyModel->find(),
            'value' => 'id',
            'property' => 'label',
            'multiple' => true,
        ));
        $form->build();

        if ($form->isSubmitted()) {
            try {
                $form->validate();

                $keyChain = $form->getData();

                $keyChainModel->save($keyChain);

                $this->addSuccess('success.data.saved', array('data' => $keyChain->getName()));

                if (!$referer) {
                    $referer = $this->getUrl('ssh-key-chains');
                }

                $this->response->setRedirect($referer);

                return;
            } catch (ValidationException $exception) {
                $this->setValidationException($exception, $form);
            }
        }

        $this->setTemplateView('admin/key-chains/form', array(
            'form' => $form->getView(),
            'keyChain' => $keyChain,
            'referer' => $referer,
        ));
    }

    public function deleteAction(OrmManager $orm, $id) {
        $keyChainModel = $orm->getKeyChainModel();

        $keyChain = $keyChainModel->getById($id);
        if (!$keyChain) {
            $this->response->setNotFound();

            return;
        }

        $referer = $this->getReferer();

        if ($this->request->isPost()) {
            $keyChainModel->delete($keyChain);

            $this->addSuccess('success.data.deleted', array('data' => $keyChain->getName()));

            if (!$referer) {
                $referer = $this->getUrl('key-chains');
            }

            $this->response->setRedirect($referer);

            return;
        }

        $this->setTemplateView('admin/delete', array(
            'title' => 'title.key-chains',
            'subtitle' => $keyChain->getName(),
            'data' => $keyChain->getName(),
            'referer' => $referer,
        ));
    }


}
