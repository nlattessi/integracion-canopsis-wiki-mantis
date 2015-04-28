#!/usr/bin/python

import sys
import xmlrpclib
import re
from bs4 import BeautifulSoup as BS

if len(sys.argv) == 1:
	print 'uso: script.py {ALARMA}'
	exit()

alarma = sys.argv[1]

wikiurl = "http://localhost:8080"
homewiki = xmlrpclib.ServerProxy(wikiurl + "?action=xmlrpc2", allow_none=True)
pagename = 'alarmas'
html = homewiki.getPageHTML(pagename)
soup = BS(html)

links = soup.find_all("h1", text=alarma)
for link in links:
	ul = link.next_element.next_element.next_element.next_element

	#Host
	host_tag = ul.find_all('li')[2].contents[0].split(':')[1].strip()
	#Fix para eliminar punto al final del host
	host_tag = host_tag.split('.')[0]
	#Descripcion
	desc = ul.find_all('li')[0].contents[0].split(':')[1].strip()
	#Warning
	warning = ul.find_all('li')[4].contents[0].split(':')[1].strip()
	#Critical
	critical = ul.find_all('li')[6].contents[0].split(':')[1].strip()
	
	print 'Host: ', host_tag
	print 'Descripcion: ', desc	
	print 'Accion warning: ', warning
	print 'Accion critical: ', critical

