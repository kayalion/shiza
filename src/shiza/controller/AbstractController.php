<?php

namespace shiza\controller;

use ride\library\i18n\translator\Translator;

use ride\web\base\controller\AbstractController as RideAbstractController;

class AbstractController extends RideAbstractController {

    protected function getEntry($model, $id, $field = 'code') {
        if ($id === null) {
            return null;
        } elseif (is_numeric($id)) {
            return $model->getById($id);
        } else {
            return $model->getBy(array(
                'filter' => array(
                    $field => $id,
                ),
            ));
        }
    }

    protected function getManagerOptions(Translator $translator, $interface, $translationPrefix) {
        $options = array();

        $managers = $this->dependencyInjector->getAll($interface);
        foreach ($managers as $managerId => $manager) {
            $options[$managerId] = $translator->translate($translationPrefix . $managerId);
        }

        return $options;
    }

}
