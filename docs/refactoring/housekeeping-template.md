# Housekeeping

Rhythm: weekly

Recurring maintenance for this project. One Housekeeping ticket is always open, made from the tasks
below. Ideas for a refactoring are left on it as comments. A new task goes above the last one.

## Tasks

- Scan the Git history for committed secrets with gitleaks, as the CI job runs it: the whole history the first time, afterwards the commits added since the previous Housekeeping ticket's scan. Note the commit scanned up to.
- Re-check the project's quality tooling: is every tool set up so far still in place and running, and is there a further one to set up? File a ticket for each gap.
- Check whether a newer PHP patch/minor release exists for the declared minimum version; update if so.
- Run `composer update`; record the output.
- Review `composer audit`'s current report; attempt a fix for any advisory with an available patched version.
- After any dependency update, re-run PHPStan and check for newly-surfaced deprecation warnings; fix in scope.
- Re-run Semgrep's OWASP Top 10 ruleset and review new findings.
- Look for a newer PER-CS version than the one `.php-cs-fixer.dist.php` pins (`vendor/bin/php-cs-fixer list-sets`). Where there is one, switch to it and set each rule that now reports findings to `false` with `Not yet:` and its count; only the config changes, never the code.
- Run PHPMD after `composer update` and check for rules a new version added to the rule sets in `phpmd.xml.dist`: a new limit is set to today's peak with a comment naming it, any other new finding is fixed or suppressed with its reason.
- Create the next Housekeeping ticket from this file, due one rhythm from today.
