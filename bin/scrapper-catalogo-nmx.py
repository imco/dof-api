#!/usr/bin/env python3

import requests
from html.parser import HTMLParser
import re
import urllib

url_0 = "http://www.economia-nmx.gob.mx/normasmx/index.nmx"
url_1 = "http://www.economia-nmx.gob.mx/normasmx/consulta.nmx"
url_2 = "http://www.economia-nmx.gob.mx/normasmx/consultasvarias.nmx"



class DetalleNormaParser(HTMLParser):
	inTableData = False
	field = 0
	foundData=False
	column = 0
	link = ''
	def handle_starttag(self, tag, attrs):
		self.link = ''
		if tag=='td':
			self.inTableData = True
			self.newTableData = True
			self.field +=1
		if tag =="tr":
			self.newTableRow = True
			self.field = 0
		if tag=='a':
			for attr, value in attrs:
				if (attr=='href'):
					self.link= value
	def handle_endtag(self, tag):
		if tag=='td':
			self.inTableData = False
	def handle_data(self, data):
		if (len(data.strip())>0 and self.inTableData):
			if (self.newTableData and self.newTableRow and self.foundData):
				print("\t", end="")
			if (self.newTableData):
				self.newTableData=False

			if (self.newTableRow):
				self.newTableRow = False
				#print("\n", end="")

			if(self.field==2):
				if (len(self.link)>0):
					print(self.link, end="")
				else:
					print(data, end="")
					if (not self.foundData):
						matches=re.search('NMX-([^-]+)-', data, re.IGNORECASE)
						if (matches):
							print("\t" + matches.group(1), end="")
					self.foundData=True
			
	def handle_charref(self, ref):
		self.handle_entityref("#" + ref)
	def handle_entityref(self, ref):
		self.handle_data(self.unescape("&%s;" % ref))

class seleccionaNmxParser(HTMLParser):
	claves = []
	def handle_starttag(self, tag, attrs):
		if (tag=='option'):
			for attr, value in attrs:
				if (attr == 'value'):
					self.claves.append(value)

class consultaVariasParser(HTMLParser):
	inTableData = False
	field = 0
	foundData=False
	link = ''
	links = []
	def handle_starttag(self, tag, attrs):
		self.link = ''
		if tag=='td':
			self.inTableData = True
			self.newTableData = True
			self.field +=1
		if tag =="tr":
			self.newTableRow = True
			self.field = 0
		if tag=='a':
			for attr, value in attrs:
				if (attr=='href' and 'detallenorma.nmx?clave=' in value):
					self.links.append('http://www.economia-nmx.gob.mx' + value)
	def handle_endtag(self, tag):
		if tag=='td':
			self.inTableData = False
		
			
	def handle_charref(self, ref):
		self.handle_entityref("#" + ref)
	def handle_entityref(self, ref):
		self.handle_data(self.unescape("&%s;" % ref))

s = requests.session()

r = s.get(url_0)
data ={"tiponmx":"S", "clave":"clave", "claveprod":0, "palabras":""}
r = s.post(url_1, data)

# Iterar sobre las claves de NMX
parserNmx = seleccionaNmxParser()
parserNmx.feed(r.text.encode().decode('windows-1252'))
for clavenmx in parserNmx.claves:
	if 'NMX-J-673/11' in clavenmx:
		clavenmx=clavenmx.replace('Â','')
	r=s.post(url_2, {"bandera":1, "clave":clavenmx.encode('windows-1252')})

	parserConsulta = consultaVariasParser()
	parserConsulta.links = []
	parserConsulta.feed(r.text)
	# Iterar sobre los links identificados
	for url_3 in parserConsulta.links:
		r = s.post(url_3)
		# Extraer la información de interés y publicarla en forma de tabla
		parserDetalle = DetalleNormaParser()
		parserDetalle.feed(r.text)
		print("")