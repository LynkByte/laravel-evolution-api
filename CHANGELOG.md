# Changelog

All notable changes to `laravel-evolution-api` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2024-01-22

### Added
- Initial release
- Full Evolution API integration for WhatsApp messaging
- Instance management (create, fetch, connect, disconnect, delete, restart)
- Message types support:
  - Text messages
  - Media messages (image, video, document)
  - Audio messages (including PTT voice notes)
  - Location messages
  - Contact messages
  - Poll messages
  - List messages
  - Reaction messages
  - Sticker messages
  - Status/Stories messages
  - Template messages
- Chat operations (fetch, archive, mark read/unread, delete messages, presence)
- Group management (create, update, participants, settings)
- Profile management (name, status, picture, privacy settings)
- Webhook configuration and handling
- Settings management
- Queue support with `SendMessageJob`
- Webhook processing with `ProcessWebhookJob`
- Instance sync with `SyncInstanceStatusJob`
- Rate limiting with configurable limits
- Metrics collection for monitoring
- Comprehensive logging
- Eloquent models:
  - `EvolutionInstance`
  - `EvolutionMessage`
  - `EvolutionContact`
  - `EvolutionWebhookLog`
- Events:
  - `MessageSent`
  - `MessageReceived`
  - `MessageDelivered`
  - `MessageRead`
  - `MessageFailed`
  - `QrCodeReceived`
  - `ConnectionUpdated`
  - `InstanceStatusChanged`
  - `WebhookReceived`
- Artisan commands:
  - `evolution-api:install`
  - `evolution-api:health-check`
  - `evolution-api:instance-status`
  - `evolution-api:retry-failed`
  - `evolution-api:prune`
- Testing utilities with `EvolutionApiFake`
- Comprehensive exception handling
- Full test suite with 50 test files

### Security
- Webhook signature verification
- API key authentication

[Unreleased]: https://github.com/lynkbyte/laravel-evolution-api/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/lynkbyte/laravel-evolution-api/releases/tag/v1.0.0
