# Changelog
All notable changes to the hQDB project will be documented in this file.

## [1.1.0] - 2026-03-27
### Added
- `api.php`: REST API bridge for read/write access with API key authentication.
- `hqdb.py`: CloudBot Python plugin for remote interaction via IRC.
- `about.php`: Dynamic rendering of repository markdown files.
### Changed
- Web installer now syncs markdown documentation to the web root.

## [1.0.0] - 2026-03-27
### Added
- Initial deployment of the hQDB engine on the LAMP stack.
- SQLite database backend with WAL enabled for concurrent reads.
- IP-based tracking for flood control and vote manipulation prevention.
- Role-based moderation queue (Admin/Mod).
- Pure terminal-aesthetic CSS styling.
