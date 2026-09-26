# Housekeeping checklist

- Check whether a newer PHP patch/minor release exists for the declared minimum version; update if so.
- Run `composer update`; record the output.
- Review `composer audit`'s current report; attempt a fix for any advisory with an available patched version.
- After any dependency update, re-run PHPStan and check for newly-surfaced deprecation warnings; fix in scope.
- Re-run Semgrep's OWASP Top 10 ruleset and review new findings.
