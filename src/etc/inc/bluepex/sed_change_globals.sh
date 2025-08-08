#!/bin/sh

sed -i \
  -e "s|'factory_shipped_password' => 'pfsense'|'factory_shipped_password' => 'b1uepexutm'|" \
  -e "s|'xml_rootobj' => 'pfsense'|'xml_rootobj' => 'bluepex'|" \
  -e "s|'product_name' => 'pfSense'|'product_name' => 'bluepex'|" \
  -e "s|'product_label' => 'pfSense'|'product_label' => 'bluepex'|" \
  -e "s|'product_label_html' => 'Netgate pfSense<sup>&#174;</sup>'|'product_label_html' => 'BluePex BluePexUTM<sup>&#174;</sup>'|" \
  -e "s|'language' => '[^']*'|'language' => 'pt_BR'|" \
  -e "s|part of pfSense (https://www.pfsense.org)|part of bluepex (https://bluepex.com)|" \
globals.inc
