# hQDB (hacker Quote Database)
**Version:** 1.1.6

hQDB is a lightweight, SQLite-backed archive designed to preserve network history, out-of-context IRC highlights, and terminal output.

## Architecture
- **Backend:** PHP 8.x, SQLite3
- **Frontend:** Pure HTML/CSS (Terminal aesthetic)
- **Bot Integration:** Python 3 (CloudBot plugin)

## Directory Structure
- `/root/hqdb/` - Primary Git repository and installation scripts.
- `/var/www/wpm.2600.chat/hqdb/` - Public web root.
- `/opt/hqdb/` - Secure database storage (`hqdb.sqlite`).

## Administration
Default installation creates an admin user. Access the moderation queue via `/mod/`.
For network issues or abuse reports, contact the network administrators.
