# Changelog

All notable changes to `php-diff` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.2] - 2026-03-16

### Changed
- Standardize composer.json: add type, homepage, scripts

## [1.0.1] - 2026-03-15

### Changed
- Standardize README badges

## [1.0.0] - 2026-03-15

### Added
- Initial release
- `Diff::strings()` — compare strings with Myers diff algorithm
- `Diff::arrays()` — compare arrays by key with added/removed/changed detection
- `Diff::objects()` — compare object properties
- `StringDiff` with unified, HTML, and structured array output
- `ArrayDiff` with filtered access to added, removed, and changed entries
- `ObjectDiff` with property change tracking
- `DiffStats` value object for diff statistics
