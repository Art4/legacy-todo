# ADR 0005: A dedicated Uploads module owns the `public/uploads/` write surface

## Status

Accepted

## Context

`public/uploads/` is the only directory Apache serves (per ADR-0003) for user-uploaded files, and the
only place an unauthenticated attacker gets to pick a filename and write bytes. Until #191, `Page`'s
`addTodo()` wrote the uploaded file straight out of `$_FILES` with the client-supplied basename and
no extension or size check, directly into the docroot — an arbitrary-file-upload path. That is a
behavior change on the security path, which this suite's ordinary byte-for-byte clause (ADR-0004)
would otherwise re-litigate: the action is *not* a cosmetic preservation of output, it is closing a
deliberately exploitable corner and must not be undone by a future bug-path-preservation proposal.

## Decision

A new `Uploads` module (`Art4\LegacyTodo\Uploads`) is the single owner of every write to the
`public/uploads/` directory beneath the webroot. `Page`'s `addTodo()` delegates to
`$this->app->uploads()->store($files["upload"] ?? [])` and renders the returned path.

- **Stored name is server-generated** — `bin2hex(random_bytes(16))` — never the client-supplied
  basename, so no attacker-controlled string lands in the path.
- **Extensions are whitelisted** — one of gif, jpeg, jpg, pdf, png, txt, webp; anything else is
  rejected and nothing is written.
- **Tree consistency is preserved** for the fixed path: the `Upload :` output string changed from
  `uploads/<client-filename>` to `uploads/<server-filename>`, so the byte-for-byte clause holds for
  everything except the uploaded file's own (previously attacker-chosen) name.
- The uploaded file's extension is preserved so the stored file keeps a servable content type; the
  name itself is not.

## Consequences

- A future scan must not re-propose writing uploads with the client-supplied filename, nor dispatching
  them without the whitelist and size guard, on either byte-for-byte or refactor grounds.
- `Uploads` stays the single surfaced seam for `public/uploads/` writes; no page owns that concern
  directly.