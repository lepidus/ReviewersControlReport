<?php

trait StringLength
{
    public function formatStringLength(string $string, int $length = 30): string
    {
        if (strlen($string) > $length) {
            $firstStringIndex = 0;
            $threeCharactersFromTheEnd = $length - 3;
            return substr($string, $firstStringIndex, $threeCharactersFromTheEnd) . '...';
        }

        return $string;
    }
}
