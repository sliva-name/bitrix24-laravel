# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
