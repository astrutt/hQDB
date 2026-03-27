# Changelog
All notable changes to the hQDB project will be documented in this file.

## [1.1.6] - 2026-03-27
### Changed
- Hardened the flagging system: `[ x ]` drop operator is now strictly limited to authenticated moderators and administrators.
- Integrated core `Auth` class session handling into the public interface to verify moderator status natively.
- Removed public-facing flagging mechanism entirely to eliminate abuse vectors and moderator fatigue.

## [1.1.4] - 2026-03-27
### Changed
- Temporary mitigation: Flagging mechanism required a threshold of 3 unique IP addresses to demote a quote. (Replaced in v1.1.6).

## [1.1.3] - 2026-03-27
### Added
- Sliding-window pagination for handling large datasets cleanly.
- Persisted search state across paginated results.
### Fixed
- Quote ID routing bug (`?v=view&id=`) resolving empty search results on direct quote clicks.

## [1.1.2] - 2026-03-27
### Added
- `[ x ]` inline flagging operator allowing users to report quotes for moderation.

## [1.1.0] - 2026-03-27
### Added
- `api.php`: REST API bridge for read/write access with API key authentication.
- `hqdb.py`: CloudBot Python plugin for remote interaction via IRC.
- `about.php`: Dynamic rendering of repository markdown files.
### Changed
- Web installer now explicitly excludes `.htaccess` from web root and enforces strict octal permissions (`find` based) for LAMP environments.
- `about.php` restructured to remove internal README content and serve public usage/network info.

## [1.0.0] - 2026-03-27
### Added
- Initial deployment of the hQDB engine on the LAMP stack.
- SQLite database backend with WAL enabled for concurrent reads.
- IP-based tracking for flood control and vote manipulation prevention.
- Role-based moderation queue (Admin/Mod).
- Pure terminal-aesthetic CSS styling.
