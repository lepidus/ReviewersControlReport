<?php

class RCRClosedDateInterval
{
    private $beginningDate;
    private $endDate;
    private const DAY_BEGINNING = ' 00:00:00';
    private const DAY_ENDING = ' 23:59:59';

    public function __construct(string $beginningDate, string $endDate)
    {
        $this->beginningDate = new DateTime($beginningDate . self::DAY_BEGINNING);
        $this->endDate = new DateTime($endDate . self::DAY_ENDING);
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
