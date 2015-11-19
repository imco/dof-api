#!/bin/bash
CONNECTION="-d catalogonomsv2 -h dev.imco.org.mx"
TOTAL=`psql ${CONNECTION} -c "\COPY (select count(*) from catalogonoms_dof_notas where cod_nota not in (select cod_nota from catalogonoms_menciones_en_notas)) TO STDOUT;"`
#TOTAL=32
PARTITIONS=32
BLOCKSIZE=`expr $TOTAL / $PARTITIONS + 1`

echo "BLOCKSIZE = $[BLOCKSIZE]        TOTAL = ${TOTAL}"
for ((OFFSET=0; OFFSET<${TOTAL}; OFFSET+=BLOCKSIZE))
do
	echo "$OFFSET"
	start-stop-daemon --background --no-close --exec "/usr/bin/psql" --start -- ${CONNECTION} -a <<EOF
CREATE TEMP TABLE T${OFFSET}  AS select cod_nota from catalogonoms_dof_notas where cod_nota not in (select cod_nota from catalogonoms_dof_notas_plano) ORDER BY cod_nota LIMIT ${BLOCKSIZE} OFFSET ${OFFSET};
INSERT INTO catalogonoms_dof_notas_plano (cod_nota, cod_diario, titulo, seccion, organismo, secretaria, pagina, contenido_plano, created_at, updated_at) (select cod_nota, cod_diario, titulo, seccion, organismo, secretaria, pagina, trim(entity2char(regexp_replace(contenido, '(<style.*?</style>)|(<[^>]+>)|(<script.*?</script>)|(<[^>]+>)', '', 'g'))) AS contenido_plano, NOW() AS created_at, NOW() AS updated_at FROM catalogonoms_dof_notas WHERE cod_nota IN (SELECT cod_nota FROM T${OFFSET}));
WITH t1 AS (select DISTINCT cod_nota,  (regexp_matches(contenido_plano, '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx|nom(?!\w|\d|&\wacute;)))(([^\s])?[\w\d/]+)+(?=(,\s|\.\s|\s|<|\.?$$)))', 'gi'))[1] as mencion, titulo from catalogonoms_dof_notas_plano)
INSERT INTO catalogonoms_menciones_en_notas(cod_nota, mencion, ubicacion, created_at, updated_at)  (SELECT cod_nota, mencion, CASE WHEN titulo  ~* mencion THEN 'Título' ELSE 'Contenido' END AS ubicacion, now() AS created_at, now() AS updated_at FROM t1);
EOF
done

echo "Finished"