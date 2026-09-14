<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

use DateTime;
use InvalidArgumentException;

class RCRClosedDateInterval
{
    private $beginningDate;
    private $endDate;
    private const DAY_BEGINNING = ' 00:00:00';
    private const DAY_ENDING = ' 23:59:59';

    public function __construct(string $beginningDate, string $endDate)
    {
        if (!$this->isValidDate($beginningDate) || !$this->isValidDate($endDate)) {
            throw new InvalidArgumentException('Dates must be valid and use the YYYY-MM-DD format.');
        }

        $this->beginningDate = new DateTime($beginningDate . self::DAY_BEGINNING);
        $this->endDate = new DateTime($endDate . self::DAY_ENDING);
    }

    private function isValidDate(string $date): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $date, $matches)) {
            return false;
        }

        return checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1]);
    }

    public function getBeginningDate(): string
    {
        return $this->beginningDate->format('Y-m-d H:i:s');
    }

    public function getEndDate(): string
    {
        return $this->endDate->format('Y-m-d H:i:s');
    }

    public function isValid(): bool
    {
        return ($this->beginningDate <= $this->endDate);
    }
}
