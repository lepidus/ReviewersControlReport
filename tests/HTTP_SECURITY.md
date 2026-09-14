# HTTP security regression

Run only against an isolated, disposable copy of the CI OJS database. The test
logs in as the fixture users and exercises the real core reports page, plugin
page, grid, and both CSV exports. It does not follow redirects or mock responses.

Prepare the fixture:

- Install and enable the plugin in `publicknowledge` with the core plugin installer.
- Keep `dbarnes` as manager, `dbuskins` as section editor, `phudson` as reviewer,
  `amwandenga` as author, and `admin` as site administrator. Their test passwords
  must be their usernames repeated twice; reset the administrator in the disposable
  database if the image uses another password.
- Set the title of submission 7's current publication to
  `<img src=x onerror=alert(1)>` and reviewer `phudson`'s given name to `=1+1`.
  Keep their completed review and reviewer membership from the CI seed.

```sh
RCR_TEST_FIXTURE=disposable \
RCR_TEST_URL=http://127.0.0.1:8080/index.php/publicknowledge \
php plugins/generic/reviewersControlReport/tests/http-security.php
```

The script requires PHP cURL. It checks allowed and denied roles, stored-title
escaping, formula protection in both reports, malformed dates, array input and
CSRF. It does not prove browser JavaScript behavior or cross-context isolation;
the DAO suite separately verifies that completed reviews stay in their context.
