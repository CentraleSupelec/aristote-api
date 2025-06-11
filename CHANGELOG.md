# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),  
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

---

## [1.7.1] - 2025-05-26

### Fixed
- 🛠️ Fixed login to Sonata Panel after Symfony upgrade

---

## [1.7.0] - 2025-05-19

### Changed
- ⬆️ Major dependencies upgrade: Symfony 7.2, PHP 8.4, PHPUnit 12

---

## [1.6.4] - 2025-05-12

### Changed
- 📴 Deactivated SSL verification when sending notifications

---

## [1.6.3] - 2025-05-12

### Added
- 🔔 Option to receive notifications at each step completion, not only after all steps

---

## [1.6.2] - 2025-04-23

### Changed
- 🔓 Health check endpoint no longer requires authentication

### Fixed
- 🐛 Send notifications after the cleanup process

---

## [1.6.1] - 2025-03-24

### Fixed
- 🛠️ Sending notifications when a job fails

---

## [1.6.0] - 2025-02-07

### Fixed
- 🛠️ Handle long-running enrichments that reached max retries in cleanup job

---

## [1.5.9] - 2024-12-16

### Added
- 🔀 Handle transcription and translation infrastructures/models by client

---

## [1.5.8] - 2024-10-29

### Fixed
- 🛠️ Fixed parsing of specific formats in `.vtt` and `.srt` files

---

## [1.5.7] - 2024-10-14

### Added
- 🔍 Validate infrastructure and model at enrichment creation

---

## [1.5.6] - 2024-10-10

### Added
- 🕒 Fill `latestEnrichmentRequestedAt` field

---

## [1.5.5] - 2024-10-10

### Changed
- 📊 Sort enrichments for job dispatching (by priority and `latestEnrichmentRequestedAt`)

### Added
- 👥 Added `contributors` field to enrichments to manage access for other end-users
