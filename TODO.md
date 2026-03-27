# hQDB System Tasks & Roadmap

## High Priority
- [ ] **Automated Backups:** Write `/etc/cron.daily/hqdb-backup` bash script. 
  - *Spec:* Needs to safely lock the SQLite database, execute `.dump` or backup the raw `.sqlite` and `.wal` files from `/opt/hqdb/`, compress them, and rotate old archives (e.g., keep last 7 days).

## Future Enhancements
- [ ] **API Rate Limiting:** Add token-bucket or sliding-window rate limiting to `api.php` if the bot integration starts getting hammered by channel spam.
- [ ] **JSON Export:** Add a public `export.php` endpoint to allow users to download a sanitized JSON dump of the approved database.
- [ ] **Moderator Audit Log:** Track which admin/mod approved or rejected specific quotes in the `/mod/` panel for accountability.

## Completed
- [x] **Flagging Threshold:** Implemented 3-IP rule to drop quotes to pending.
- [x] Web UI & Navigation (Terminal aesthetic, dynamic sliding pagination)
- [x] Legacy Database Import (~8,500 bash.org quotes sanitized and loaded)
- [x] SQLite WAL backend with strict IP-tracking & flood control
- [x] PHP REST API Bridge (`api.php`)
- [x] Python CloudBot Plugin (`hqdb.py`)

