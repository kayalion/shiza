<?php

namespace shiza\validator;

use ride\library\config\parser\IniParser;
use ride\library\validation\validator\AbstractValidator;

use \Exception;

/**
 * Validator to check if a value is in a valid INI format
 */
class IniValidator extends AbstractValidator {

    /**
     * Machine name of this validator
     * @var string
     */
    const NAME = 'ini';

    /**
     * Code of the error message when the value is not a valid crontab
     * @var string
     */
    const CODE = 'error.validation.ini';

    /**
     * Message of the error message when the value is not valid crontab
     * @var string
     */
    const MESSAGE = 'Invalid INI syntax';

    /**
     * Instance of the cron helper
     * @var \shiza\helper\CronHelper
     */
    private $iniParser;

    /**
     * Sets the cron helper to this validator
     * @param \shiza\helper\CronHelper $cronHelper
     * @return null
     */
    public function setIniParser(IniParser $iniParser) {
        $this->iniParser = $iniParser;
    }

    /**
     * Checks whether a value is empty
     * @param mixed $value
     * @return boolean True if the value is empty, false otherwise
     */
    public function isValid($value) {
        $this->resetErrors();

        if ($value == '') {
            return true;
        }

        try {
            $ini = $this->iniParser->parseToPhp($value);

            return true;
        } catch (Exception $exception) {
            $this->addValidationError(self::CODE, self::MESSAGE, array());

            return false;
        }
    }

}
