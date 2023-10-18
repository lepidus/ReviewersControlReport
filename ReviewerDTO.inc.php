<?php

class ReviewerDTO
{
    private $fullName;
    private $email;
    private $affiliation;

    public function __construct($email, $firstName, $lastName, $affiliation)
    {
        $this->fullName = $firstName . ' ' . $lastName;
        $this->email = $email;
        $this->affiliation = $affiliation;
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
}
