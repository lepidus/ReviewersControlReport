<?php

namespace APP\plugins\generic\reviewersControlReport\classes;

class RCRCsvWriter
{
    /** @param resource $csvFile */
    public function writeRow($csvFile, array $row): void
    {
        fputcsv($csvFile, $this->neutralizeFormulas($row), ',', '"', '');
    }

    /**
     * Reports carry text written by authors and reviewers, and a spreadsheet
     * reads a cell opening with =, +, - or @ as a formula. Prefixing the cell
     * with an apostrophe keeps it text, and leaves numbers untouched.
     */
    public function neutralizeFormulas(array $row): array
    {
        return array_map(function ($cell) {
            if (is_string($cell) && preg_match('/^(?:[\x00-\x20]*[=+\-@]|[\t\r\n])/', $cell)) {
                return "'" . $cell;
            }

            return $cell;
        }, $row);
    }
}
