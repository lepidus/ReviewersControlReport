# Reviewers Control Report Plugin

This plugin provides a web page with a reviewers control report for the journal, under *Statistics > Reports*.

## Compatibility

The latest release of this plugin is compatible with the following PKP applications:

* OJS 3.3.0

## Installation

1. Install the plugin using the latest Package available for the application you are using.

2. Upload the package and Activate the plugin in the journals *Website > Plugins* page.

## Usage

After activating the plugin, a new page will be available under *Reports > Reviewers Control Report*.

The page lists the journal's reviewers on screen and generates two CSV reports:

* **Report by reviewers** — one row per reviewer, with the quality average, the
  number of completed reviews and the titles of the submissions they reviewed.
* **Report by reviews** — one row per completed review, with the submission,
  the review round, the assignment, due and completion dates, the
  recommendation and the quality rating, each in its own column.

Both reports can be restricted to a period, filtered by the date the review was
completed. Leaving both dates empty includes every completed review. In the
report by reviewers the quality average and the review counts cover only the
chosen period, and reviewers with no review in it are still listed, with those
columns empty.

The file is named after the report and the period it covers, for example
`reviewsControlReport-20260101-20260331.csv`.

Only reviews of the current journal are reported.

## Credits
This plugin was sponsored by the journals [Encontros Bibli](https://periodicos.ufsc.br/index.php/eb/), from Universidade Federal de Santa Catarina (UFSC) and [Revista Evidência](https://periodicos.unoesc.edu.br/evidencia), from Universidade do Oeste de Santa Catarina (Unoesc).

Developed by [Lepidus Tecnologia](https://github.com/lepidus).

## License

This project is licensed under the GNU General Public License v3. See the [LICENSE](LICENSE) file for details.
***