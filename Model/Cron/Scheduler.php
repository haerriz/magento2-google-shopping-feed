<?php
namespace Haerriz\GoogleShoppingFeed\Model\Cron;

use DateTime;
use DateTimeZone;

class Scheduler
{
    /**
     * Calculate next run datetime based on frequency, custom cron expression, and timezone
     *
     * @param string $frequency
     * @param string|null $cronExpr
     * @param string|null $timezoneStr
     * @param string|null $fromTimeStr Base time from which to calculate next run (defaults to now)
     * @return string Datetime in UTC format (Y-m-d H:i:s)
     */
    public function calculateNextRun($frequency, $cronExpr = null, $timezoneStr = null, $fromTimeStr = null)
    {
        $timezone = $timezoneStr ? new DateTimeZone($timezoneStr) : new DateTimeZone('UTC');
        $now = new DateTime($fromTimeStr ?: 'now', new DateTimeZone('UTC'));
        $now->setTimezone($timezone);

        switch ($frequency) {
            case 'manual':
                return null;

            case 'hourly':
                $now->modify('+1 hour');
                $now->setTime((int)$now->format('H'), 0, 0); // start of next hour
                break;

            case 'daily':
                $now->modify('+1 day');
                $now->setTime(2, 0, 0); // standard daily run at 2 AM local time
                break;

            case 'weekly':
                $now->modify('+1 week');
                $now->setTime(2, 0, 0);
                break;

            case 'monthly':
                $now->modify('+1 month');
                $now->setTime(2, 0, 0);
                break;

            case 'custom':
                $now = $this->calculateCustomCron($cronExpr, $now);
                break;

            default:
                throw new \InvalidArgumentException('Unsupported schedule frequency.');
        }

        $now->setTimezone(new DateTimeZone('UTC'));
        return $now->format('Y-m-d H:i:s');
    }

    /**
     * Lightweight custom cron parser/validator returning next timestamp matching expression
     *
     * @param string $expr
     * @param int $currentTimestamp
     * @return int
     */
    protected function calculateCustomCron($expr, DateTime $current)
    {
        $parts = explode(' ', preg_replace('/\s+/', ' ', trim($expr)));
        if (count($parts) !== 5) {
            throw new \InvalidArgumentException('Cron expression must contain exactly five fields.');
        }

        list($min, $hour, $day, $month, $wday) = $parts;
        foreach ([[$min, 0, 59], [$hour, 0, 23], [$day, 1, 31], [$month, 1, 12], [$wday, 0, 7]] as $field) {
            $this->validateCronPart($field[0], $field[1], $field[2]);
        }
        $candidate = clone $current;
        $candidate->setTime((int)$candidate->format('H'), (int)$candidate->format('i'), 0);
        for ($i = 0; $i < 527040; $i++) {
            $candidate->modify('+1 minute');
            if ($this->matchCronPart($min, (int)$candidate->format('i')) &&
                $this->matchCronPart($hour, (int)$candidate->format('G')) &&
                $this->matchCronPart($day, (int)$candidate->format('j')) &&
                $this->matchCronPart($month, (int)$candidate->format('n')) &&
                $this->matchCronPart($wday, (int)$candidate->format('w'))
            ) {
                return $candidate;
            }
        }

        throw new \InvalidArgumentException('Cron expression has no run time in the next year.');
    }

    /**
     * Check if a cron part matches current value
     */
    protected function matchCronPart($part, $value)
    {
        if ($part === '*') {
            return true;
        }
        
        // Match lists (e.g. 1,2,5)
        if (strpos($part, ',') !== false) {
            $items = explode(',', $part);
            return in_array((string)$value, $items, true);
        }

        // Match intervals (e.g. */5)
        if (strpos($part, '*/') !== false) {
            $step = (int)str_replace('*/', '', $part);
            return $step > 0 && ($value % $step === 0);
        }

        // Exact match
        return (int)$part === (int)$value;
    }

    private function validateCronPart($part, $minimum, $maximum)
    {
        foreach (explode(',', $part) as $item) {
            if ($item === '*') {
                continue;
            }
            if (preg_match('/^\*\/(\d+)$/', $item, $match)) {
                if ((int)$match[1] < 1) {
                    throw new \InvalidArgumentException('Cron step must be greater than zero.');
                }
                continue;
            }
            if (!ctype_digit($item) || (int)$item < $minimum || (int)$item > $maximum) {
                throw new \InvalidArgumentException('Cron expression contains an out-of-range value.');
            }
        }
    }
}
