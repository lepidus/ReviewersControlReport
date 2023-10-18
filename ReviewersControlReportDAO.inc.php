<?php

import('lib.pkp.classes.db.DAO');

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Support\Collection;

class ReviewersControlReportDAO extends DAO
{
    private $locale;

    public function getReviewers($journalId)
    {
        $resultGroup = Capsule::table('user_groups')
        ->select('user_group_id')
        ->where('role_id', ROLE_ID_REVIEWER)
        ->where('context_id', $journalId)
        ->first();
        $userGroupId = get_object_vars($resultGroup)['user_group_id'];

        $this->locale = AppLocale::getLocale();

        $query = Capsule::table('users AS u')
        ->select(
            'u.email',
            'us_givenName.setting_value AS givenName',
            'us_familyName.setting_value as familyName',
            'us_affiliation.setting_value as affiliation'
        )
        ->whereIn('u.user_id', function ($query) use ($userGroupId) {
            $query->select('user_id')
            ->from('user_user_groups')
            ->where('user_group_id', $userGroupId);
        })->leftJoin('user_settings AS us_givenName', 'us_givenName.user_id', '=', 'u.user_id')->where(function ($query) {
            $query->where('us_givenName.setting_name', '=', 'givenName')
            ->where('us_givenName.locale', '=', $this->locale);
        })->leftJoin('user_settings AS us_familyName', 'us_familyName.user_id', '=', 'u.user_id')->where(function ($query) {
            $query->where('us_familyName.setting_name', '=', 'familyName')
            ->where('us_familyName.locale', '=', $this->locale);
        })->leftJoin('user_settings AS us_affiliation', 'us_affiliation.user_id', '=', 'u.user_id')->where(function ($query) {
            $query->where('us_affiliation.setting_name', '=', 'affiliation')
            ->where('us_affiliation.locale', '=', $this->locale);
        });

        return $query->get();
    }
}
