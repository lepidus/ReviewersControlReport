<?php

class RCRCsvWriter
{
    /** @param resource $csvFile */
    public function writeRow($csvFile, array $row): void
    {
        $fields = array_map(function ($cell) {
            $cell = (string) $cell;
            if (strpbrk($cell, ",\"\r\n\t ") !== false) {
                return '"' . str_replace('"', '""', $cell) . '"';
            }

            return $cell;
        }, $this->neutralizeFormulas($row));

        fwrite($csvFile, implode(',', $fields) . "\n");
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
