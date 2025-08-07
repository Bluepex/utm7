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
import getpass
import json
import logging
import os
import time
import urllib.parse

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
	authority=os.getenv('AUTHORITY'),
	oidc_authority=os.getenv('OIDC_AUTHORITY'),
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
			raise RuntimeError("Microsoft Entra ID requires user consent for U/P flow to succeed. Run acquire_token_interactive() instead.")

def decode_special_characters(encoded_string):
	decoded_string = urllib.parse.unquote(encoded_string)

	return decoded_string

if __name__ == "__main__":
	while True:
		username = input()
		users = username.split()

		if username is None:
			break

		user = acquire_and_use_token(users[0], decode_special_characters(users[1]))
		logging.info("%s", user + " Autenticated")

		if user:
			print("OK\n")
		else:
			logging.error("ERR: Autenticated failed")
			print("ERR\n")
