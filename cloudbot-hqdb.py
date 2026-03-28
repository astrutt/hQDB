# Version: 1.1.0
# Last Updated: 2026-03-27

# hqdb.py
import requests
from cloudbot import hook

# --- Configuration ---
API_URL = "https://scuttled.net/hqdb/api.php"
# This MUST match the $apiKey variable you set in api.php
API_KEY = "changeme_to_a_secure_random_string" 

@hook.command("hqdb")
def hqdb(text, reply):
    """[id] | add <quote> - Interacts with the hacker Quote Database (hQDB) on scuttled.net."""
    
    # 1. Fetch Random Quote (!hqdb)
    if not text:
        try:
            response = requests.get(f"{API_URL}?action=random", timeout=5).json()
            if response.get("success"):
                quote_data = response["data"]
                
                # Announce the meta-data first
                reply(f"[\x02hQDB #{quote_data['id']}\x02 | Score: {quote_data['score']}]")
                
                # Split the raw text by newlines and send each line to the channel
                lines = quote_data["quote_text"].split('\n')
                for line in lines:
                    if line.strip():
                        reply(line.strip())
            else:
                reply(f"hQDB Error: {response.get('error', 'EOF.')}")
        except Exception as e:
            reply(f"System fault connecting to hQDB: {e}")
        return

    parts = text.split(" ", 1)
    command = parts[0].lower()

    # 2. Add Quote to Pending Queue (!hqdb add <text>)
    if command == "add":
        if len(parts) < 2:
            reply("Usage: !hqdb add <quote text>")
            return
        
        quote_text = parts[1].strip()
        headers = {"X-API-Key": API_KEY}
        payload = {"quote_text": quote_text}
        
        try:
            response = requests.post(f"{API_URL}?action=add", headers=headers, data=payload, timeout=5).json()
            if response.get("success"):
                reply(f"ACK. Quote transmitted to hQDB buffer (ID: {response.get('id')}). Awaiting moderation.")
            else:
                reply(f"hQDB Error: {response.get('error', 'Transmission failed.')}")
        except Exception as e:
            reply(f"System fault connecting to hQDB: {e}")
        return

    # 3. Fetch Specific Quote (!hqdb <id>)
    if command.isdigit():
        quote_id = command
        try:
            response = requests.get(f"{API_URL}?action=get&id={quote_id}", timeout=5).json()
            if response.get("success"):
                quote_data = response["data"]
                
                reply(f"[\x02hQDB #{quote_data['id']}\x02 | Score: {quote_data['score']}]")
                
                lines = quote_data["quote_text"].split('\n')
                for line in lines:
                    if line.strip():
                        reply(line.strip())
            else:
                reply(f"hQDB Error: {response.get('error', 'Record not found.')}")
        except Exception as e:
            reply(f"System fault connecting to hQDB: {e}")
        return

    # 4. Fallback Usage Instructions
    reply("Usage: !hqdb [id] | !hqdb add <quote>")
