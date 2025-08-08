#!/usr/local/bin/python3.8

"""
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Marcos Claudiano <marcos.claudiano@bluepex.com>, 2024
 *
 * ====================================================================
 *
 */
"""

import sys
from subprocess import check_output
import logging
import os
from dotenv import load_dotenv
import msal
import requests

logging.basicConfig(
	level=logging.INFO,
	format="%(asctime)s [%(levelname)s] %(message)s",
	handlers=[
		logging.FileHandler("/var/log/entra_id.log"),
	]
)

load_dotenv()

global_token_cache = msal.TokenCache()
global_app = msal.ClientApplication(
	os.getenv('CLIENT_ID'),
	authority="https://login.microsoftonline.com/"+os.getenv('AUTHORITY'),
	client_credential=os.getenv('CLIENT_SECRET') or None,
	token_cache=global_token_cache,
)
scopes = os.getenv("SCOPE", "").split()

def acquire_and_use_token(username, password):
	result = global_app.acquire_token_by_username_password(username, password, scopes=scopes)

	if "access_token" in result:
		if os.getenv('ENDPOINT'):
			api_result = requests.get(
				os.getenv('ENDPOINT'),
				headers={'Authorization': 'Bearer ' + result['access_token']},
			).json()
		else:
			if result["access_token"] != "":
				return result["id_token_claims"]["preferred_username"]
	else:
		if 65001 in result.get("error_codes", []):
			raise RuntimeError(
			    "Microsoft Entra ID requires user consent for U/P flow to succeed. "
			    "Run acquire_token_interactive() instead.")

def get_email_by_ip(ip_address):
    db_file = "/var/db/entraid_access.db"
    sqlite_cmd = f"/usr/local/bin/sqlite3 {db_file} 'SELECT email FROM entraid WHERE ip=\"{ip_address}\" LIMIT 1'"

    try:
        email = check_output([sqlite_cmd], shell=True).strip().decode("utf-8")
        return email if email else None
    except Exception as e:
        logging.error(f"Error querying database: {e}")
        return None

if __name__ == "__main__":
	ip_input = input().strip()
	ip_address = ip_input.split()[0]

	if not ip_address:
		logging.error("No valid IP provided")
		print("ERR\n")
		sys.exit(1)

	email = get_email_by_ip(ip_address)

	if email:
		logging.info(f"Authenticated user: {email}")
		print(f"OK user={email}\n")
	else:
		logging.error("Authentication failed for IP: " + ip_address)
		print("ERR\n")
