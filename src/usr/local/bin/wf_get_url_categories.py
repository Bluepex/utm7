#!/usr/local/bin/python3.8
# -*- coding: utf-8 -*-
#
#  Copyright (C) 2017 BluePex Security Company (R)
#  Silvio Giunge <desenvolvimento@bluepex.com>
#  All rights reserved.
#


import re
import sys
from subprocess import check_output
from argparse import ArgumentParser

# Reference of regex for url path http://stackoverflow.com/questions/3809401/what-is-a-good-regular-expression-to-match-a-url
pattern = re.compile(r"https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{2,256}\.[a-z]{2,6}\b([-a-zA-Z0-9@:%_\+.~#?&//=]*)")

def set_parser():

    parser = ArgumentParser()
    parser.add_argument(
      "-c", dest="cat_only", action="store_true", default=False,
      help="Print olny categories separated by a comma."
    )
    parser.add_argument(
      "-u", dest="url", action="store",
      help="URL to check categories."
    )

    return parser.parse_args()

def ck(cmd):
    if check_output(['{}'.format(cmd)], shell=True).decode("utf-8").rstrip() == ',':
        ret = '99,'
    else:
        ret = check_output(['{}'.format(cmd)], shell=True).decode("utf-8").rstrip()

    #ret = check_output(['{}'.format(cmd)], shell=True).decode("utf-8").rstrip() if check_output(['{}'.format(cmd)], shell=True).decode("utf-8").rstrip() else '99,'
    return ret

def main():
    if opts.cat_only:
        print(",".join(str(x) for x in r_get_codes))
    else:
        print("\n".join(r_get_codes))

if __name__ == "__main__":
    opts = set_parser()
    #s_cmd = "/usr/local/bin/bp_category.sh {}"
    s_cmd = "/usr/local/bin/bp_category_redis.sh {}"
    #url = opts.url.upper().encode("utf-8").hex().upper()
    url = "{}{}{}".format("http://", opts.url, "/").upper()

    r_get_codes = ck(s_cmd.format(url)).rstrip().split(',')

    #r_cat_file = filter(None, open('/usr/local/etc/wf_categories', 'r').read().split('\n'))
    #categories = { idx: cat for idx, cat in [ line.split(',') for line in r_cat_file ] }
    if len(r_get_codes) == 0:
        print("Check interface process, no categories returned.")
    else:
        main()
