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
import logging
import os
import json
from dotenv import load_dotenv
import msal
import requests

logging.basicConfig(
	level=logging.INFO,
	format="%(asctime)s [%(levelname)s] %(message)s",
	handlers=[
		logging.FileHandler("/var/log/entra_id.log"),
		logging.StreamHandler()
	]
)

load_dotenv()

global_app = msal.ConfidentialClientApplication(
	client_id=os.getenv('CLIENT_ID'),
	client_credential=os.getenv('CLIENT_SECRET'),
	authority=f"https://login.microsoftonline.com/{os.getenv('AUTHORITY')}"
)

scopes = ["https://graph.microsoft.com/.default"]

def acquire_and_use_token():
	result = global_app.acquire_token_for_client(scopes=scopes)
	if "access_token" in result:
		return result['access_token']
	else:
		error_message = result.get("error_description", "Erro desconhecido ao adquirir token.")
		logging.error(f"Erro ao adquirir token: {error_message}")
		sys.exit(1)

def get_users_and_groups(typee):
	token = acquire_and_use_token()
	headers = {'Authorization': f'Bearer {token}'}

	if typee == "users":
		users_url = "https://graph.microsoft.com/v1.0/users"
		response = requests.get(users_url, headers=headers)
		response.raise_for_status()
		return response.json().get('value', [])

	elif typee == "groups":
		groups_url = "https://graph.microsoft.com/v1.0/groups"
		response = requests.get(groups_url, headers=headers)
		response.raise_for_status()
		return response.json().get('value', [])

	elif typee == "users_groups":
		groups_url = "https://graph.microsoft.com/v1.0/groups"
		response = requests.get(groups_url, headers=headers)
		response.raise_for_status()
		groups_data = response.json().get('value', [])

		groups_info = []

		for group in groups_data:
			group_info = {
				'displayName': group['displayName'],
				'description': group.get('description', ''),
				'objectguid': group['id'],
				'members': []
			}

			group_members_url = f"https://graph.microsoft.com/v1.0/groups/{group['id']}/members"
			group_members_response = requests.get(group_members_url, headers=headers)
			group_members_response.raise_for_status()
			group_members_data = group_members_response.json().get('value', [])

			for member in group_members_data:
				if member['@odata.type'] == "#microsoft.graph.user":
					group_info['members'].append(member['userPrincipalName'])

			groups_info.append(group_info)

		return groups_info
	else:
		logging.error(f"Invalid Type: {typee}")
		sys.exit(1)

def read_credentials():
	if len(sys.argv) < 2:
		print("Usage: /usr/local/bin/python3.8 /usr/local/bin/vpn_entraid_params.py <type(groups|users|users_groups)>")
		sys.exit(1)
	return sys.argv[1]

if __name__ == "__main__":
	try:
		typee = read_credentials()
		data = get_users_and_groups(typee)
		print(json.dumps(data, indent=4))
	except Exception as e:
		logging.error(f"Erro fatal: {str(e)}")
		sys.exit(1)
