<?php

trait RCRReportDate
{
    public function formatReportDate($date): string
    {
        if (empty($date)) {
            return '';
        }

        return date('Y-m-d', strtotime($date));
    }
}
