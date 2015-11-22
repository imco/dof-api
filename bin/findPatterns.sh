#!/bin/bash
# 111280
CONNECTION="catalogonomsv2"
TOTAL=`psql ${CONNECTION} -c "\COPY (select count(*) from (SELECT cod_nota FROM catalogonoms_dof_notas EXCEPT SELECT cod_nota FROM catalogonoms_dof_notas_plano) t1) TO STDOUT;"`
TOTAL=100
PARTITIONS=100
BLOCKSIZE=`expr $TOTAL / $PARTITIONS + 1`
LOGDIR=`pwd`
OUTLOG="batch-processing2.log"
echo "BLOCKSIZE = $[BLOCKSIZE]        TOTAL = ${TOTAL}"
for ((OFFSET=0; OFFSET<${TOTAL}; OFFSET+=BLOCKSIZE))
do
	echo "$OFFSET"
	/sbin/start-stop-daemon --background --no-close --exec "/usr/bin/psql" --start -- ${CONNECTION} -a >> "${LOGDIR}/${OUTLOG}" 2>> "${LOGDIR}/${OUTLOG}" <<EOF
CREATE TEMP TABLE T${OFFSET}  AS select cod_nota from (SELECT cod_nota FROM catalogonoms_dof_notas EXCEPT SELECT cod_nota FROM catalogonoms_dof_notas_plano) t1 ORDER BY cod_nota LIMIT ${BLOCKSIZE} OFFSET ${OFFSET};
INSERT INTO catalogonoms_dof_notas_plano (cod_nota, cod_diario, titulo, seccion, organismo, secretaria, pagina, contenido_plano, created_at, updated_at) (select cod_nota, cod_diario, titulo, seccion, organismo, secretaria, pagina, trim(entity2char(regexp_replace(contenido, '(<style.*?</style>)|(<[^>]+>)|(<script.*?</script>)|(<[^>]+>)', '', 'g'))) AS contenido_plano, NOW() AS created_at, NOW() AS updated_at FROM catalogonoms_dof_notas NATURAL JOIN T${OFFSET});
--WITH t1 AS (select DISTINCT cod_nota,  (regexp_matches(contenido_plano, '((d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}|[^\s>]*(nmx|nom(?!\w|\d|&\wacute;)))(([^\s])?[\w\d/]+)+(?=(,\s|\.\s|\s|<|\.?$$)))', 'gi'))[1] as mencion, titulo from catalogonoms_dof_notas_plano WHERE cod_nota IN (SELECT cod_nota FROM T${OFFSET}))
--INSERT INTO catalogonoms_menciones_en_notas(cod_nota, mencion, ubicacion, created_at, updated_at)  (SELECT cod_nota, mencion, CASE WHEN titulo  ~* mencion THEN 'Título' ELSE 'Contenido' END AS ubicacion, now() AS created_at, now() AS updated_at FROM t1);
EOF
done

echo "Finished"