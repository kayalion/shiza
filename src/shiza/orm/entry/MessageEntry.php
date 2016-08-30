<?php

namespace shiza\orm\entry;

use ride\application\orm\entry\MessageEntry as OrmMessageEntry;

use \Exception;

class MessageEntry extends OrmMessageEntry {

    public function setExceptionToBody(Exception $exception) {
        $body = '';
        do {
            $message = $exception->getMessage();
            $message = get_class($exception) . (!empty($message) ? ': ' . $message : '');
            $trace = $exception->getTraceAsString();

            $body .= $message . "\n\nTrace:\n" . $trace;

            $exception = $exception->getPrevious();
            if ($exception) {
                $body .= "\n\nCaused by:\n\n";
            }
        } while ($exception);

        $this->setBody($body);
    }

}
