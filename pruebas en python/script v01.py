#!/usr/bin/python

import sys
import xmlrpclib
import re
from bs4 import BeautifulSoup as BS

if len(sys.argv) == 1:
	print 'uso: script.py {PAGENAME} {ALARMA} {HOST} {ESTADO}'
	exit()

if len(sys.argv[1:5]) < 4:
	print 'faltan argumentos'
	exit()

wikiurl = "http://localhost:8080"
pagename = sys.argv[1]
alarma = sys.argv[2]
host = sys.argv[3]
estado = sys.argv[4]

homewiki = xmlrpclib.ServerProxy(wikiurl + "?action=xmlrpc2", allow_none=True)
html = homewiki.getPageHTML(pagename)
soup = BS(html)

links = soup.find_all("h1", text=alarma)
for link in links:
	ul = link.next_element.next_element.next_element.next_element

	#Host
	host_tag = ul.find_all('li')[2].contents[0].split(':')[1].strip()
	#Fix para eliminar punto al final del host
	host_tag = host_tag.split('.')[0]

	if host == host_tag:
		#Descripcion
		desc = ul.find_all('li')[0].contents[0].split(':')[1].strip()
		if estado == 'Warning':
			#Warning
			accion = ul.find_all('li')[4].contents[0].split(':')[1].strip()
		elif estado == 'Critical':
			#Critical
			accion = ul.find_all('li')[6].contents[0].split(':')[1].strip()
		break

#print 'Pagename: ', pagename
#print 'Alarma: ', alarma
#print 'Host: ', host
print 'Descripcion: ', desc
#print 'Estado: ', estado
print 'Accion: ', accion