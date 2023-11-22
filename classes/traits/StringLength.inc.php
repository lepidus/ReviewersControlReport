<?php

trait StringLength
{
    public function formatStringLength(string $string, int $length = 30): string
    {
        if (mb_check_encoding($string, 'UTF-8')) {
            if (mb_strlen($string, 'UTF-8') > $length) {
                $firstStringIndex = 0;
                $threeCharactersFromTheEnd = $length - 3;
                return mb_substr($string, $firstStringIndex, $threeCharactersFromTheEnd, 'UTF-8') . '...';
            }
        } else {
            return __('plugins.reports.reviewersControlReport.validate.utf8');
        }

        return $string;
    }
}
