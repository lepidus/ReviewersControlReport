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
        ->select('u.email', 'us.setting_value AS givenName')
        ->whereIn('u.user_id', function ($query) use ($userGroupId) {
            $query->select('user_id')
            ->from('user_user_groups')
            ->where('user_group_id', $userGroupId);
        })->leftJoin('user_settings AS us', 'us.user_id', '=', 'u.user_id')->where(function ($query) use ($givenName) {
            $query->where('us.setting_name', '=', 'givenName')
            ->where('us.locale', '=', $this->locale);
        });

        return $query->get();
    }
}
