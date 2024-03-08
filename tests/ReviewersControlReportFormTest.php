<?php

import('lib.pkp.tests.DatabaseTestCase');
import('lib.pkp.classes.user.User');
import('plugins.generic.reviewersControlReport.classes.ReviewersControlReportForm');

class ReviewersControlReportFormTest extends DatabaseTestCase
{
    private $reviewerId;
    private $locale = 'pt_BR';
    private $givenName = 'Walter';
    private $familyName = 'Salles';
    private $username = 'walter.salles';
    private $email = 'walter.salles@ancine.com.br';
    private $affiliation = 'Agência Nacional do Cinema';

    public function setUp(): void
    {
        parent::setUp();
        $this->reviewerId = $this->createUser();
    }

    protected function getAffectedTables()
    {
        return ['users', 'user_settings'];
    }

    private function createUser()
    {
        $user = new User();
        $user->setData('givenName', [$this->locale => $this->givenName]);
        $user->setData('familyName', [$this->locale => $this->familyName]);
        $user->setData('affiliation', [$this->locale => $this->affiliation]);
        $user->setData('email', $this->email);
        $user->setData('username', $this->username);
        $user->setData('password', $this->username);

        return DAORegistry::getDAO('UserDAO')->insertObject($user);
    }

    public function testGetsReviewerPersonalData()
    {
        $form = new ReviewersControlReportForm();

        $reviewerPersonalData = $form->getReviewerPersonalData();
        $emptyInterests = '';
        $expectedPersonalData = [
            $this->givenName . ' ' . $this->familyName,
            $this->email,
            $this->affiliation,
            $emptyInterests
        ];

        $this->assertEquals($expectedPersonalData, $reviewerPersonalData);
    }
}
