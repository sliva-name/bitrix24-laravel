# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.1] - 2026-08-14

### Docs
- README aligned with the published config, client interfaces, exceptions and `composer test`

## [1.2.0] - 2026-08-14

### Changed
- Deduplicated CRM entity clients through a shared `CrmEntityClient` base
- Unified OAuth token exchange and refresh through `TokenManager`
- Extracted `ConnectionConfig` and `Domain` helpers for connection settings
- `Bitrix24ServiceInterface` now returns client interfaces instead of concrete classes
- `Bitrix24Service` and the `bitrix24` container alias share a single singleton
- Replaced generic `RuntimeException` with dedicated package exceptions
- `callCrmMethod()` now delegates to `callMethod()`
- Incoming webhook REST success checks use a shared `isSuccessful()` helper

### Added
- `Bitrix24Service::flushCustomClients()` and `client()` lookup for built-in clients
- `TokenManager::exchangeAuthorizationCode()` for the OAuth callback flow
- Unit and integration tests for tokens, OAuth, clients, batch requests and config

### Fixed
- Refreshing a token no longer stores an un-normalized portal domain
- `HasCaching::flushCache()` no longer throws on cache stores without tag support

## [1.1.0] - 2026-08-13

### Changed
- Upgraded official SDK dependency to `bitrix24/b24phpsdk` ^1.10
- Added Laravel 13 support (`illuminate/*` ^10|^11|^12|^13)
- OAuth and webhook connections now use `ServiceBuilderFactory` instead of a custom webhook emulator
- Token refresh now calls the Bitrix24 OAuth server and persists tokens renewed by the SDK
- Task, user, CRM and list clients use current SDK scopes (`getTaskScope()`, `getUserScope()`, `core->call()`)
- Batch requests are executed through the official SDK `batch` method
- CRM, task and user clients return official SDK DTOs (`LeadItemResult`, `DealItemResult`, …) instead of `mixed`/arrays

### Added
- `Bitrix24::sdk()` / `Bitrix24Service::sdk()` to access the official `ServiceBuilder`
- Connection `scope` and `oauth_server` config (`BITRIX24_SCOPE`, `BITRIX24_OAUTH_SERVER`)
- Automatic persistence of `AuthTokenRenewedEvent` from the SDK

### Fixed
- ServiceBuilder was constructed with invalid arguments for the official SDK
- Expired tokens could not be refreshed because the repository ignored them
- `isExpiringSoon()` mutated the `expires_at` Carbon instance
- Middleware imported a non-package facade namespace

### Removed
- Custom `WebhookServiceBuilder` (webhook auth uses the official SDK)

## [1.0.0] - 2025-10-21

### Added
- Initial release of Laravel Bitrix24 integration package
- Dual authentication support (OAuth and Webhook)
- Complete CRM clients (Leads, Deals, Contacts, Companies, Tasks, Users)
- Token management with automatic refresh
- Caching support for tokens
- Ready-to-use controllers and routes
- Middleware for route protection
- ApiResponse trait for standardized JSON responses
- Comprehensive documentation
- Migration guide
- PSR-12 code standards compliance
- SOLID principles implementation

### Features
- **OAuth Authentication**: Full OAuth 2.0 flow support
- **Webhook Authentication**: Direct webhook integration
- **CRM Operations**: CRUD operations for all CRM entities
- **User Management**: User operations and current user info
- **Task Management**: Task operations
- **Token Caching**: Automatic token caching and refresh
- **Error Handling**: Comprehensive error handling
- **Logging**: Configurable logging support
- **Auto-Discovery**: Laravel package auto-discovery
- **Facade Support**: Easy-to-use facade interface

### Technical Details
- PHP 8.2+ support
- Laravel 10.x, 11.x, 12.x support
- PostgreSQL database support
- Redis/Database cache support
- HTTP client integration
- Service Provider pattern
- Repository pattern
- Interface-based architecture
