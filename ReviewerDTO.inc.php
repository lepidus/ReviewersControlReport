<?php

class ReviewerDTO
{
    private $fullName;
    private $email;
    private $affiliation;
    private $interests;

    public function __construct($email, $firstName, $lastName, $affiliation, $interests)
    {
        $this->fullName = $firstName . ' ' . $lastName;
        $this->email = $email;
        $this->affiliation = $affiliation;
        $this->interests = $interests;
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
        $output = "";
        foreach($this->interests as $interest) {
            $output .= $interest . ", ";
        }
        return $output;
    }
}
