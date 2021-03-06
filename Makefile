# Requiere Python3 y nltk (Natural language toolkit)
#

SHELL := /bin/bash
#FUENTE := nmx-en-dof.csv
FUENTE := catalogonoms_dof_notas.csv
TITULARESNMXCLASIFICADOS := titulares-nmx-clasificados.csv

TESTSTRING := "NORMA Oficial de Calidad para Manta de Mostrador, D. G .N. A-1-1965. (Esta Norma cancela la DGN A-1-1958)"
MATCHREGEX := "((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|nmx)(([^\s])?[\w\d/]+)+\)?(?=(,\s|\.\s|\s|\.?$$)))"
TRAININGDATA := public/nmx-knowledgeBase.csv
INPUT := /tmp/menciones-para-clasificar.csv
DEBUGFILE := "nmx-no-localizadas.csv"

OUTPUT := ../apiv3
WORKING_DIRECTORY := `pwd`

all: install

install: install-laravel add-package update-laravel


# Instala las dependencias necesarias
dependencies:
	sudo apt-get install postgresql-plpython-10 && \
	sudo apt install python3 python3-pip && \
	pip3 install -U nltk && \
	(printf "import nltk \nnltk.download('punkt')\n" | python3)




currentTask:
	@clear
#	@./bin/clasificador.py --match=2,3 -t ${TRAININGDATA} -f ${INPUT}
	@./bin/clasificador.py --match ${MATCHREGEX} -t ${TRAININGDATA} ${TESTSTRING}  #| csvtool col 2,3 -
#	csvquery titulares-nmx-clasificados.csv nmx-activas.csv -q 'SELECT clave FROM csv2 EXCEPT SELECT clave_nmx from csv;' > nmx-no-localizadas.csv
#
# VERY IMPORTANT !!!
# Execute on server side
#nohup psql catalogonomsv2 -a -c "CREATE TABLE catalogonoms_dof_notas_plano AS (select *, trim(entity2char(regexp_replace(contenido, '(<style.*?</style>)|(<[^>]+>)|(<script.*?</script>)|(<[^>]+>)', '', 'g'))) AS contenido_plano from catalogonoms_dof_notas);" && psql catalogonomsv2 -a -c "CREATE TABLE catalogonoms_claves_mencionadas AS (WITH t1 AS (select DISTINCT cod_nota,  (regexp_matches(contenido_plano, '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx|nom(?"'!'"\w|\d|&\wacute;)))(([^\s])?[\w\d/]+)+(?=(,\s|\.\s|\s|<|\.?$$)))', 'gi'))[1] as mencion, titulo from catalogonoms_dof_notas_plano) SELECT cod_nota, mencion, CASE WHEN titulo  ~* mencion THEN 'Título' ELSE 'Contenido' END AS ubicacion, null::varchar as etiqueta, now() AS created_at, now() AS updated_at FROM t1);" &
#watch "ps -ex | grep psql"
# !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

downloadNMXenDOF:
#	scp dev.imco.org.mx:${FUENTE} .
	psql -d catalogonomsv2 -h dev.imco.org.mx -c"\COPY catalogonoms_dof_notas TO public/catalogonoms_dof_notas.csv"


#clean:
#	rm nmx-en-dof.csv

fullTest: downloadNMXenDOF classify results

test: currentTask test_http

test_http:
	curl "http://api3.localhost/catalogonoms/nmx/ramas"


testClassification: classify results

results: ${FUENTE} ${TITULARESNMXCLASIFICADOS}
# Imprime un texto describiendo los resultads obenidos
# Publicaciones en el DOF con MX en su titulo
# Cantidad de menciones a NMX
# NMX distintas mencionadas
# Cuenta los distintos contextos
# TODO

	clear

	@printf "Se ha analizado el título de \e[32m"`csvtool col 2 ${FUENTE}| sort | uniq | wc -l`"\e[0m notas descargadas del DOF\n\n"
	@printf "En dichas notas, se mencionan \e[32m"`cat ${TITULARESNMXCLASIFICADOS} | wc -l`"\e[0m claves de Normas Mexicanas.\n"
	@printf "De las NMX listadas en ${TITULARESNMXCLASIFICADOS} existen \e[32m"`csvtool col 2 ${TITULARESNMXCLASIFICADOS}| sort | uniq | wc -l`"\e[0m claves de NMX distintas.\n"
	@printf "\nDespués de analizar el contexto en el que se mencionan las Normas Mexicanas, se han logrado identificar \e[32m"`csvtool col 3 ${TITULARESNMXCLASIFICADOS}| sort | uniq | wc -l`"\e[0m contextos diferentes. Los cuales se pretende clasificar en menos de \e[33m10\e[0m categorias.\n"
	@printf "\nSe han identificado \e[32m"`csvtool col 6 ${TITULARESNMXCLASIFICADOS}| sort | uniq | wc -l`"\e[0m descriptores de contextos utlizables para la clasificacion.\n"
	@printf "\nExisten \e[32m"`cat ${DEBUGFILE} | wc -l`"\e[0m NMX vigentes que no se han localizado.\n"

	@read -n 1
	@clear



classify: ${FUENTE}
	rm ${TITULARESNMXCLASIFICADOS}
	csvtool col 2 ${FUENTE} | ./bin/clasificador.py --match ${MATCHREGEX} > ${TITULARESNMXCLASIFICADOS}
#	csvtool col 4 ${FUENTE} | ./bin/clasificador.py --match "(nmx-[^\s((\)|\.)+$)]+)" > ${TITULARESNMXCLASIFICADOS}
	csvquery titulares-nmx-clasificados.csv nmx-activas.csv -q 'SELECT clave FROM csv2 EXCEPT SELECT clave from csv;' > ${DEBUGFILE}





install-laravel:
	if [ ! -d $(OUTPUT) ]; then composer create-project laravel/laravel $(OUTPUT); fi;

add-package:
		if [ `grep 'IMCO\CatalogoNOMsApi\CatalogoNOMsApiServiceProvider' $(OUTPUT)/config/app.php | wc -l` == 0 ]; then sed -ie '146i\\tIMCO\\CatalogoNOMsApi\\CatalogoNOMsApiServiceProvider::class,' $(OUTPUT)/config/app.php; fi

		@echo -e "import json\nfrom pprint import pprint\nimport inspect, os\n\nwith open('$(OUTPUT)/composer.json', 'r+') as data_file:\n\tdata = json.load(data_file)\n\tif not 'IMCO\\\\\\CatalogoNOMsApi\\\\\\' in json.dumps(data['autoload']['psr-4']):\n\t\tdata['autoload']['psr-4']['IMCO\\\\\\CatalogoNOMsApi\\\\\\']= '$(WORKING_DIRECTORY)';\n\tdata_file.seek(0)\n\tdata_file.write(json.dumps(data,sort_keys=True, indent=4))\n" | /usr/bin/python3

update-laravel:
	cd $(OUTPUT); composer update; php artisan vendor:publish;

clean:
	rm -rf $(OUTPUT)

.PHONY: install-laravel add-package update-laravel
