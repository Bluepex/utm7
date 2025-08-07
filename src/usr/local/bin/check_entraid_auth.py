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

from dotenv import load_dotenv
import msal
import requests

logging.basicConfig(
	level=logging.INFO,
	format="%(asctime)s [%(levelname)s] %(message)s",
	handlers=[
		logging.FileHandler("/var/log/entra_id.log"),
		logging.StreamHandler(sys.stdout),
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
		print("Token was obtained from:", result["token_source"])
		if os.getenv('ENDPOINT'):
			api_result = requests.get(
					os.getenv('ENDPOINT'),
					headers={'Authorization': 'Bearer ' + result['access_token']},
				).json()
			print("Web API call result", json.dumps(api_result, indent=2))
		else:
			if result["access_token"] != "":
				return result
	else:
		print("Token acquisition failed", result)

		if 65001 in result.get("error_codes", []):
			raise RuntimeError("Microsoft Entra ID requires user consent for U/P flow to succeed. Run acquire_token_interactive() instead.")

def get_users_and_groups(username, password, type):
	result = global_app.acquire_token_by_username_password(username, password, scopes=scopes)

	if (type == "users"):
		users_url = "https://graph.microsoft.com/v1.0/users"
		users_response = requests.get(users_url, headers={'Authorization': 'Bearer ' + result['access_token']})
		users_data = users_response.json()

		return users_data

	if (type == "groups"):
		groups_url = "https://graph.microsoft.com/v1.0/groups"
		groups_response = requests.get(groups_url, headers={'Authorization': 'Bearer ' + result['access_token']})
		groups_data = groups_response.json()

		return groups_data

def read_credentials():
	username = input("Username: ")
	password = getpass.getpass("Password: ")

	return username, password

if __name__ == "__main__":
	while True:
		username, password = read_credentials()
		print (get_users_and_groups(username, password, "groups"))
