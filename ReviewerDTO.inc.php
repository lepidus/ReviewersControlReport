<?php

class ReviewerDTO
{
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;
    private $qualityAverage;

    public function __construct($email, $fullName, $affiliation, $interests, $qualityAverage)
    {
        $this->fullName = $fullName;
        $this->email = $email;
        $this->affiliation = $affiliation;
        $this->interests = $interests;
        $this->qualityAverage = $qualityAverage;
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getAffiliation()
    {
        return $this->affiliation;
    }

    public function getInterests()
    {
        return $this->interests;
    }

    public function getQualityAverage()
    {
        return number_format($this->qualityAverage, 2, '.', '');
    }
}
