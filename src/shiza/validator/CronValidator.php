<?php

namespace shiza\validator;

use ride\library\validation\validator\AbstractValidator;

use shiza\helper\CronHelper;

/**
 * Validator to check if a value is a valid crontab
 */
class CronValidator extends AbstractValidator {

    /**
     * Machine name of this validator
     * @var string
     */
    const NAME = 'cron';

    /**
     * Code of the error message when the value is not a valid crontab
     * @var string
     */
    const CODE = 'error.validation.cron';

    /**
     * Message of the error message when the value is not valid crontab
     * @var string
     */
    const MESSAGE = 'Invalid crontab';

    /**
     * Instance of the cron helper
     * @var \shiza\helper\CronHelper
     */
    private $cronHelper;

    /**
     * Sets the cron helper to this validator
     * @param \shiza\helper\CronHelper $cronHelper
     * @return null
     */
    public function setCronHelper(CronHelper $cronHelper) {
        $this->cronHelper = $cronHelper;
    }

    /**
     * Checks whether a value is empty
     * @param mixed $value
     * @return boolean True if the value is empty, false otherwise
     */
    public function isValid($value) {
        $this->resetErrors();

        if (!$this->cronHelper->isValidCrontab($value)) {
            $this->addValidationError(self::CODE, self::MESSAGE, array());

            return false;
        }

        return true;
    }

}
