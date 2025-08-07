#!/bin/sh

/usr/local/bin/redis-cli --raw mget $1 | tr "\n" ","

