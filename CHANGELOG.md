# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## 1.0.0 — 2026-06-05

- Initial release.

## 2.0.0 — 2026-06-09

- **BREAKING:** the package no longer binds `SettingsProvider` in its `di` config,
  and the `Settings` facade is now built from the injected `SettingsProvider`.
  The core only wires the `Settings` facade; the provider is supplied by exactly
  one source — a storage backend (`yii3-settings-db`) or the application
  (config-only). This removes the `Duplicate key "...\SettingsProvider"` and
  `Duplicate key "...\Settings"` config errors that occurred when a backend was
  installed alongside the core.
- For config-array settings without a backend, bind `SettingsProvider` to
  `ConfigSettingsProvider` in the application config (see README → "Provider
  wiring").
