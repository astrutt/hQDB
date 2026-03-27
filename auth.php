<?php
// Version: 1.1.6
// Last Updated: 2026-03-27
// auth.php

require_once 'db.php';

class Auth {
    private $db;
    private $cookieName = 'hqdb_mod_token';
    private $sessionDuration = '+12 hours';

    public function __construct() {
        $this->db = DB::getInstance();
    }

    /**
     * Attempts to log a user in. If successful, creates a database session and secure cookie.
     */
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT id, password_hash FROM moderators WHERE username = :username");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        // Verify the provided password against the stored bcrypt hash
        if ($user && password_verify($password, $user['password_hash'])) {
            return $this->createSession($user['id']);
        }
        return false;
    }

    /**
     * Generates the token, stores it in SQLite, and sets the browser cookie.
     */
    private function createSession($moderatorId) {
        $token = bin2hex(random_bytes(32)); // 64-character hex string
        $expiresAt = date('Y-m-d H:i:s', strtotime($this->sessionDuration));
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $stmt = $this->db->prepare("
            INSERT INTO sessions (session_token, moderator_id, ip_address, user_agent, expires_at)
            VALUES (:token, :mod_id, :ip, :ua, :expires)
        ");
        
        $stmt->execute([
            ':token' => $token,
            ':mod_id' => $moderatorId,
            ':ip' => $ipAddress,
            ':ua' => $userAgent,
            ':expires' => $expiresAt
        ]);

        // Set secure HTTP-only cookie
        setcookie($this->cookieName, $token, [
            'expires' => strtotime($this->sessionDuration),
            'path' => '/',
            'domain' => '', // Current domain
            'secure' => true, // Enforces HTTPS
            'httponly' => true, // Prevents XSS attacks from reading the cookie
            'samesite' => 'Strict' // Prevents CSRF attacks
        ]);

        return true;
    }

    /**
     * Validates the active cookie against the database and the user's current IP.
     * Returns the user data array if valid, false if invalid.
     */
    public function checkSession() {
        if (!isset($_COOKIE[$this->cookieName])) {
            return false;
        }

        $token = $_COOKIE[$this->cookieName];
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Join with moderators table to pull role and username simultaneously
        $stmt = $this->db->prepare("
            SELECT m.id, m.username, m.role, s.expires_at 
            FROM sessions s
            JOIN moderators m ON s.moderator_id = m.id
            WHERE s.session_token = :token AND s.ip_address = :ip
        ");
        
        $stmt->execute([
            ':token' => $token,
            ':ip' => $ipAddress
        ]);
        
        $session = $stmt->fetch();

        if ($session) {
            // Check if the session has expired in the database
            if (strtotime($session['expires_at']) > time()) {
                return $session; // Valid session
            } else {
                // Expired, clean it up
                $this->logout(); 
            }
        }
        
        return false;
    }

    /**
     * Destroys the session in the database and clears the user's cookie.
     */
    public function logout() {
        if (isset($_COOKIE[$this->cookieName])) {
            $token = $_COOKIE[$this->cookieName];
            
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE session_token = :token");
            $stmt->execute([':token' => $token]);

            // Expire the cookie immediately in the browser
            setcookie($this->cookieName, '', time() - 3600, '/');
        }
    }
}
