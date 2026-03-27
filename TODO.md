# hQDB System Tasks & Roadmap

## High Priority
- [ ] **Automated Backups:** Write `/etc/cron.daily/hqdb-backup` bash script. 
  - *Spec:* Needs to safely lock the SQLite database, execute `.dump` or backup the raw `.sqlite` and `.wal` files from `/opt/hqdb/`, compress them, and rotate old archives (e.g., keep last 7 days).

## Future Enhancements
- [ ] **Flagging Threshold:** Modify `index.php` flagging logic. Currently, one `x` click drops a quote to the pending queue. Implement a threshold (e.g., 3 unique IPs required) to prevent single-user trolling.
- [ ] **API Rate Limiting:** Add token-bucket or sliding-window rate limiting to `api.php` if the bot integration starts getting hammered by channel spam.
- [ ] **JSON Export:** Add a public `export.php` endpoint to allow users to download a sanitized JSON dump of the approved database (similar to the newbash.org archive we imported).
- [ ] **Moderator Audit Log:** Track which admin/mod approved or rejected specific quotes in the `/mod/` panel for accountability.

## Completed
- [x] Web UI & Navigation (Terminal aesthetic, pagination)
- [x] Legacy Database Import (~8,500 bash.org quotes sanitized and loaded)
- [x] SQLite WAL backend with strict IP-tracking & flood control
- [x] PHP REST API Bridge (`api.php`)
- [x] Python CloudBot Plugin (`hqdb.py`)

