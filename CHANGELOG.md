# Changelog

## 1.1.2 — 2026-06-30

- Add `/benchmarks` and `/Makefile` to `.gitattributes` export-ignore.
- Pin `testo/bridge-infection` to `0.1.6`: 0.1.7/0.1.8 (2026-06-29) misclassify failing tests as passed under mutants, producing false escapes in mutation testing.

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.1.1 — 2026-06-27

- Migrate test suite from PHPUnit to Testo. Internal change, no public API impact.

## 1.1.0 — 2026-06-14

- `CachedSettingsProvider` is now write-through: it implements
  `WritableSettingsProvider`. `set()`/`remove()` delegate to the inner provider
  and invalidate the cached entry for the key, so reads never observe a stale
  value after a write. The constructor is unchanged; when the inner provider is
  read-only, `set()`/`remove()` throw a `\LogicException`. Backward-compatible,
  additive change.

## 1.0.0 — 2026-06-13

Initial release.

- Typed settings core: `SettingDefinition` (key, type, default, secret flag, and
  optional presentation/policy metadata `label`/`group`/`help`/`choices`/`readonly`),
  `SettingKey` (validated), `SettingValue` (typed normalization), `SettingType` enum.
- Providers: `ConfigSettingsProvider`, `EnvSettingsProvider`, `ChainSettingsProvider`,
  `CachedSettingsProvider` (PSR-16 decorator). `SettingsProvider` /
  `WritableSettingsProvider` contracts.
- `Settings` facade: `string()`, `int()`, `float()`, `bool()`, `array()`, `has()`.
- `SettingsInspector` read-model: `describe()` and `describeAll()` returning
  `SettingState` (key, effective value, source, stored-override, secret, writable).
- Secret support: `Crypto\Cipher` interface (AEAD with associated data),
  `DecryptionException`, `UnknownEncryptionKeyException`.
- Exceptions: `InvalidSettingKeyException`, `SettingTypeMismatchException`,
  `UnknownSettingException`, `ReadonlySettingException`.

