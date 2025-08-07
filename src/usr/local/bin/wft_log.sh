#!/bin/sh
#
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# <desenvolvimento@bluepex.com>, 2015
# ====================================================================
#

while read LINE; do
   echo "${LINE}" | nc -w 1 -U /var/run/wfrotated.sock &
done
