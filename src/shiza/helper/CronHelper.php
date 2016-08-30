<?php

namespace shiza\helper;

use \Exception;

/**
 * Helper with cron functions
 */
class CronHelper {

    /**
     * Asterix for every value
     * @var string
     */
    const ASTERIX = '*';

    /**
     * Separator for an element of a list of values
     * @var string
     */
    const SEPARATOR_LIST = ',';

    /**
     * Separator for the increment value
     * @var string
     */
    const SEPARATOR_INCREMENT = '/';

    /**
     * Separator for a range of time
     * @var string
     */
    const SEPARATOR_RANGE = '-';

    /**
     * Checks if the provided crontab is valid
     * @param string $cronTab
     * @return boolean
     */
    public function isValidCrontab($cronTab) {
        if ($cronTab === null || $cronTab === '') {
            return true;
        }

        $regex = $this->buildValidationRegex();

        $lines = explode("\n", $cronTab);
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $line .= "\n";
            if (!preg_match($regex, $line)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Builds the regular expression to validate a crontab line
     * @return string
     */
    private function buildValidationRegex() {
        $numbers = array(
            'min' => '[0-5]?\d',
            'hour' => '[01]?\d|2[0-3]',
            'day' => '0?[1-9]|[12]\d|3[01]',
            'month' => '[1-9]|1[012]',
            'dow' => '[0-6]'
        );

        foreach ($numbers as $field => $number) {
            $range = "(?:$number)(?:-(?:$number)(?:\/\d+)?)?";
            $field_re[$field] = "\*(?:\/\d+)?|$range(?:,$range)*";
        }

        $field_re['month'].='|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
        $field_re['dow'].='|mon|tue|wed|thu|fri|sat|sun';

        $fields_re = '(' . join(')\s+(', $field_re) . ')';

        $replacements = '@reboot|@yearly|@annually|@monthly|@weekly|@daily|@midnight|@hourly';

        return '/^\s*(' .
                '$' .
                '|#' .
                '|\w+\s*=' .
                "|$fields_re\s+" .
                "|($replacements)\s+" .
                ')' .
                '([^\\s]+)\\s+' .
                '(.*)$/';
    }

    /**
     * Gets the cron job schedule occupation
     * @param string $crontab Full crontab
     * @return array Array with the hour as key and a minute array as value.
     * The minute array has the minute as key and the number of jobs as
     * value.
     */
    public function getOccupation($crontab) {
        $occupation = array();

        $lines = explode("\n", $crontab);
        foreach ($lines as $line) {
            $line = trim($line);
            if (!$line || strpos($line, '#') === 0 || strpos($line, '@') === 0) {
                continue;
            }

            $line = str_replace("\t", " ", $line);

            list($minute, $hour, $rest) = explode(' ', $line, 3);

            $minuteValues = $this->parseCronValue($minute, 0, 59);
            $hourValues = $this->parseCronValue($hour, 0, 23);

            foreach ($hourValues as $hour => $null) {
                foreach ($minuteValues as $minute => $null) {
                    if (!isset($occupation[$hour])) {
                        $occupation[$hour] = array($minute => 1);
                    } elseif (!isset($occupation[$hour][$minute])) {
                        $occupation[$hour][$minute] = 1;
                    } else {
                        $occupation[$hour][$minute]++;
                    }
                }
            }
        }

        return $occupation;
    }

    /**
     * Divides the cron occuppation in blocks
     * @param array $occupation
     * @param integer $block Minutes per block (1 - 60)
     * @return array $occupation in blocks
     */
    public function blockOccupation(array $occupation, $block) {
        $blocks = array();
        for ($i = $block; $i < 60; $i += $block) {
            $blocks[$i] = true;
        }

        $blockedOccupation = array();
        for ($hour = 0; $hour < 24; $hour++) {
            $blockedOccupation[$hour] = array(0 => 0);
            foreach ($blocks as $currentBlock => $null) {
                $blockedOccupation[$hour][$currentBlock] = 0;
            }

            if (!isset($occupation[$hour])) {
                continue;
            }

            $minutes = $occupation[$hour];
            foreach ($minutes as $minute => $numJobs) {
                $isBlockSet = false;
                $currentBlock = 0;

                foreach ($blocks as $blockMinutes => $null) {
                    if ($minute >= $blockMinutes) {
                        $currentBlock += $block;

                        continue;
                    }

                    $isBlockSet = true;

                    $blockedOccupation[$hour][$currentBlock] += $numJobs;

                    break;
                }

                if (!$isBlockSet) {
                    $blockedOccupation[$hour][$currentBlock] += $numJobs;
                }
            }
        }

        return $blockedOccupation;
    }

    /**
     * Gets an array with all the values of the interval value
     * @param string $value
     * @param int $min
     * @param int $max
     * @return array
     */
    protected function parseCronValue($value, $min, $max) {
        if ($value == '*') {
            return array_fill($min, $max + 1, true);
        }

        $values = array();

        $explodedValue = explode(self::SEPARATOR_LIST, $value);
        foreach ($explodedValue as $value) {
            $incrementValue = null;
            $posSeparatorIncrement = strpos($value, self::SEPARATOR_INCREMENT);
            if ($posSeparatorIncrement !== false) {
                if ($posSeparatorIncrement != 0) {
                    list($value, $incrementValue) = explode(self::SEPARATOR_INCREMENT, $value, 2);
                } else {
                    $incrementValue = substr($value, 1);
                    $value = self::ASTERIX;
                }

                if ($value == self::ASTERIX) {
                    $value = $min . self::SEPARATOR_RANGE . $max;
                }
            }

            $posSeparatorRange = strpos($value, self::SEPARATOR_RANGE);
            if ($posSeparatorRange !== false && $posSeparatorRange != 0) {
                $range = explode(self::SEPARATOR_RANGE, $value, 2);

                $loopIncrement = 1;
                if ($incrementValue) {
                    $loopIncrement = $incrementValue;
                }

                for ($i = $range[0]; $i <= $range[1]; $i += $loopIncrement) {
                    $values[(int) $i] = true;
                }
            } else {
                if ($incrementValue) {
                    do {
                       $values[(int) $value] = true;

                       $value += $incrementValue;
                    } while ($value <= $max);
                } else {
                    $values[(int) $value] = true;
                }
            }
        }

        asort($values);

        return $values;
    }

}
