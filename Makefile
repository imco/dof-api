SHELL := /bin/bash
#FUENTE := nmx-en-dof.csv
FUENTE := catalogonoms_dof_notas.csv
TITULARESNMXCLASIFICADOS := titulares-nmx-clasificados.csv
MATCHREGEX := "((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|nmx)(([^\s])?[\w\d/]+)+\)?(?=(,\s|\.\s|\s|\.?$$)))"
DEBUGFILE := "nmx-no-localizadas.csv"

TRAININGDATA := public/nmx-knowledgeBase.csv
INPUT := /tmp/menciones-para-clasificar.csv

TESTSTRING := "NORMA Oficial de Calidad para Manta de Mostrador, D. G .N. A-1-1965. (Esta Norma cancela la DGN A-1-1958)"

all: currentTask

currentTask:
	@clear
	@./bin/clasificador.py --match=2,3 -t ${TRAININGDATA} -f ${INPUT}
#	@./bin/clasificador.py --match ${MATCHREGEX} -t ${TRAININGDATA} ${TESTSTRING}  #| csvtool col 2,3 -
#	csvquery titulares-nmx-clasificados.csv nmx-activas.csv -q 'SELECT clave FROM csv2 EXCEPT SELECT clave_nmx from csv;' > nmx-no-localizadas.csv


downloadNMXenDOF:
#	scp dev.imco.org.mx:${FUENTE} .
	psql -d catalogonomsv2 -h dev.imco.org.mx -c"\COPY catalogonoms_dof_notas TO public/catalogonoms_dof_notas.csv"


clean:
	rm nmx-en-dof.csv

fullTest: downloadNMXenDOF classify results

test: currentTask


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
