#!/bin/bash

# ==============================================================================
# hQDB (hacker Quote Database) Installer & Updater
# Version: 1.1.0
# ==============================================================================

# Strict mode: exit on error, undefined variables, and pipe failures
set -euo pipefail

# Configuration
WEB_DIR="/var/www/wpm.2600.chat/hqdb"
DATA_DIR="/opt/hqdb"
DB_FILE="$DATA_DIR/hqdb.sqlite"
REPO_DIR="/root/hqdb"
WEB_USER="www-data"

echo "Starting hQDB deployment..."

# 1. Check/Install System Dependencies
if ! command -v sqlite3 &> /dev/null || ! php -m | grep -q sqlite3; then
    echo "Installing required SQLite3 dependencies for PHP and Bash..."
    apt-get update
    apt-get install -y sqlite3 php-sqlite3
fi

# 2. Setup Directories
echo "Ensuring directories exist..."
mkdir -p "$WEB_DIR"
mkdir -p "$DATA_DIR"

# 3. Database Initialization & Schema Migration
echo "Initializing/Updating SQLite Database..."
sqlite3 "$DB_FILE" <<EOF
PRAGMA journal_mode=WAL;

CREATE TABLE IF NOT EXISTS quotes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quote_text TEXT NOT NULL,
    score INTEGER DEFAULT 0,
    status TEXT DEFAULT 'pending',
    submitted_by_ip TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS moderators (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT DEFAULT 'mod',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sessions (
    session_token TEXT PRIMARY KEY,
    moderator_id INTEGER,
    ip_address TEXT,
    user_agent TEXT,
    expires_at DATETIME,
    FOREIGN KEY(moderator_id) REFERENCES moderators(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS ip_tracking (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    action TEXT NOT NULL,
    target_id INTEGER DEFAULT 0,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP
);
EOF

# 4. Check & Seed Moderators
USER_COUNT=$(sqlite3 "$DB_FILE" "SELECT COUNT(*) FROM moderators;" 2>/dev/null || echo 0)

if [ "$USER_COUNT" -eq 0 ]; then
    echo "No moderators found. Seeding initial admin accounts..."
    DEFAULT_HASH=$(php -r "echo password_hash('changeme', PASSWORD_DEFAULT);")

    sqlite3 "$DB_FILE" <<EOF
    INSERT INTO moderators (username, password_hash, role) VALUES 
    ('emmanuel', '$DEFAULT_HASH', 'admin'),
    ('RDNt', '$DEFAULT_HASH', 'admin');
EOF
    echo "Initial admins seeded with default password: changeme"
else
    echo "Moderator accounts exist. Skipping user seed. ($USER_COUNT users found)"
fi

# 5. Deploy Web Files
echo "Syncing application files to web root..."
if [ -d "$REPO_DIR" ]; then
    # Exclude git, the installer, the python bot script, AND the .htaccess file
    rsync -av --exclude='.git*' --exclude='install.sh' --exclude='hqdb.py' --exclude='.htaccess' "$REPO_DIR/" "$WEB_DIR/"
else
    echo "Warning: Source directory $REPO_DIR not found. Skipping file sync."
fi

# 6. Deploy Data Directory Protection
if [ -f "$REPO_DIR/.htaccess" ]; then
    echo "Securing data directory with .htaccess..."
    cp "$REPO_DIR/.htaccess" "$DATA_DIR/.htaccess"
fi

# 7. Enforce Strict Permissions & Ownership
echo "Applying strict www-data ownership and permissions..."

# A. Web Root Permissions (Standard secure web: 755 dirs, 644 files)
chown -R "$WEB_USER:$WEB_USER" "$WEB_DIR"
find "$WEB_DIR" -type d -exec chmod 755 {} \;
find "$WEB_DIR" -type f -exec chmod 644 {} \;

# B. Data Root Permissions (Requires group write for SQLite WAL: 775 dirs, 664 files)
chown -R "$WEB_USER:$WEB_USER" "$DATA_DIR"
find "$DATA_DIR" -type d -exec chmod 775 {} \;
find "$DATA_DIR" -type f -exec chmod 664 {} \;

# Explicitly lock down the .htaccess file in the data dir so it can't be altered
[ -f "$DATA_DIR/.htaccess" ] && chmod 644 "$DATA_DIR/.htaccess"

echo ""
echo "=============================================================================="
echo "hQDB Deployment Complete!"
echo "Web Root: $WEB_DIR"
echo "Database: $DB_FILE"
echo "=============================================================================="
