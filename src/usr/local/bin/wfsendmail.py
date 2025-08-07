#!/usr/local/bin/python
# -*- encoding: utf-8 -*-
#  
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# <desenvolvimento@bluepex.com>, 2015
# ====================================================================
#

import os
import sys
import smtplib
import datetime
from xml.dom import minidom

xmldoc = minidom.parse('/cf/conf/config.xml')

def errorMessage(message):
	arq = open('/var/log/rotate.log','a')
	arq.write("%s | WFSendBkp | " % datetime.datetime.now().strftime('%h %d %H:%M:%S')+message+'\n')
	arq.close()

try:
	smtpNodes = xmldoc.getElementsByTagName('smtp')
	server = smtpNodes[0].getElementsByTagName('ipaddress')
	port = smtpNodes[0].getElementsByTagName('port')
	mailTo = smtpNodes[0].getElementsByTagName('notifyemailaddress')
	mailFrom = smtpNodes[0].getElementsByTagName('fromaddress')
	mailPass = smtpNodes[0].getElementsByTagName('password')
	mysqlIP = xmldoc.getElementsByTagName('reports_ip')

	mysqlIP = str(mysqlIP[0].firstChild.nodeValue)
	server = str(server[0].firstChild.nodeValue)
	port = str(port[0].firstChild.nodeValue)
	mailTo = str(mailTo[0].firstChild.nodeValue)
	mailFrom = str(mailFrom[0].firstChild.nodeValue)
	mailPass = str(mailPass[0].firstChild.nodeValue)
except:
	errorMessage('Verifique as configurações de email na aba notificações do utm, email não configurado')
	os.kill(os.getpid(), 9)

body = """
Por favor verifique seu banco de dados,\n
nao foi possivel enviar os dados do relatorio do webfilter para o servidor.
Ip do servidor = %s .

BluePex UTM.
""" % mysqlIP

body = ""+body+""

subject = 'Servidor de banco de dados parado [BluePex UTM]'

headers = ["From: "+mailTo,"Subject: "+subject,"To: "+mailTo,"MIME-Version: 1.0","Content-Type: text/html"]
headers = "\r\n".join(headers)

session = smtplib.SMTP(server,port)

session.ehlo()
try:
	session.starttls()
except:
	pass
session.ehlo
session.login(mailFrom,mailPass)

session.sendmail(mailFrom,mailTo,headers+"\r\n\r\n"+body)

session.quit()

