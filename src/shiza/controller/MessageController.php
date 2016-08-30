<?php

namespace shiza\controller;

use ride\library\orm\OrmManager;

use ride\web\base\controller\AbstractController as RideAbstractController;

class MessageController extends RideAbstractController {

    public function indexAction(OrmManager $orm) {
        $messageModel = $orm->getMessageModel();

        $messages = $messageModel->find(array(
            'limit' => 25,
            'order' => array(
                'dateAdded' => 'DESC',
            ),
        ));

        $this->setTemplateView('admin/messages/index', array(
            'messages' => $messages,
        ));
    }

    public function detailAction(OrmManager $orm, $id) {
        $messageModel = $orm->getMessageModel();

        $message = $messageModel->getById($id);
        if (!$message) {
            $this->response->setNotFound();

            return;
        }

        $this->setTemplateView('admin/messages/detail', array(
            'message' => $message,
        ));
    }

}
