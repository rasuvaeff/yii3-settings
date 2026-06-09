# AGENTS.md — yii3-settings

Guidance for AI agents working on this package. Read before changing code.

## What this is

Typed runtime settings for Yii3. Typed getters (`string()`, `int()`, `float()`,
`bool()`, `array()`), multiple providers (config, env, chain), PSR-16 cache
decorator. Namespace: `Rasuvaeff\Yii3Settings`.

Public API: `Settings` (facade), `SettingKey`, `SettingValue`,
`SettingDefinition`, `SettingType`, `SettingsProvider`,
`WritableSettingsProvider`, `SettingsInspector`, `SettingState`,
`Cipher`, `DecryptionException`, `UnknownEncryptionKeyException`,
`ConfigSettingsProvider`, `EnvSettingsProvider`,
`ChainSettingsProvider`, `CachedSettingsProvider`.

DI wiring: the core `config/di.php` binds **only** `Settings` (built from the
injected `SettingsProvider`). It must NOT bind the `SettingsProvider` interface —
that key is owned by exactly one source (a storage backend such as
`yii3-settings-db`, or the application's config-only binding). Two vendor packages
binding `SettingsProvider` (or `Settings`) in the `di` group trigger a
`yiisoft/config` `Duplicate key` error.

## Golden rules

1. **Verification is mandatory.** Never claim "done" without a fresh green
   `composer build`. "Should work" does not count.
2. **No suppressions.** No `@psalm-suppress`, no baseline. Fix the root cause.
3. **Type safety is paramount.** `SettingTypeMismatchException` on type mismatch.
   Providers normalize through `SettingDefinition::cast()`; the `Settings` facade
   applies the native cast for the requested type. Never return raw untyped values.
4. **Preserve the public contract.** Update README + tests with any API change.

## Commands

No PHP/Composer on the host — run in Docker via the `composer:2` image.

```bash
docker run --rm -v "$PWD":/app -w /app composer:2 composer build
docker run --rm -v "$PWD":/app -w /app composer:2 composer cs:fix
docker run --rm -v "$PWD":/app -w /app composer:2 composer psalm
docker run --rm -v "$PWD":/app -w /app composer:2 composer test
```

Or with Make:

```bash
make build
make cs-fix
make psalm
make test
```

`composer.lock` is gitignored (library).

## Invariants & gotchas

- Setting key regex: `/^[a-z][a-z0-9_.-]*$/`.
- `cast()` always returns the definition's type — no silent type coercion beyond
  PHP's native `(int)`, `(string)`, etc.
- Env provider maps: `prefix + upper(key)`, dots → underscores.
- Chain provider: first match wins. Order is significant.
- Cache decorator: cache key = `<namespace>:v<version>:<key>` (default
  `yii3-settings:v1:<key>`). Cache failures are silent.
- Unknown setting in non-strict mode returns type default, not `null`.
- Writable provider contract exists but no DB implementation in v1.
- `secret=true` is allowed only for `SettingType::String` — enforced at construction.
- `Cipher` is a pure interface in core; no `ext-sodium` dependency.
- `SettingsInspector` provides admin-facing read-model — distinct from `SettingsProvider`.
- Code: `declare(strict_types=1)`, `final readonly class`, `#[\Override]`,
  explicit types.

## When you finish

- Update `README.md` (and `examples/` if usage changed); update `CHANGELOG.md`
  when releasing.
- Re-run `composer build` and paste the output.
