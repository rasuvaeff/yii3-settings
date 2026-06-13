# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
