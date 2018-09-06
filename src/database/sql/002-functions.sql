CREATE LANGUAGE plpython3u;

-- Funciones de aplicación

CREATE OR REPLACE FUNCTION getDataFromURL (urlrequest text)
  RETURNS json
AS $$
  import urllib.request
  response = urllib.request.urlopen(urlrequest)
  content = response.read()
  return content.decode('utf8')
$$ LANGUAGE plpython3u;

CREATE OR REPLACE FUNCTION getDiarioFullUrl (fecha date)
  RETURNS text
AS $$
  BEGIN
  return 'http://diariooficial.gob.mx/WS_getDiarioFull.php?year='||extract(year from fecha)||'&month='||extract(month from fecha)||'&day='||extract(day from fecha);
  END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION getDiarioFullUrl (fechaInicio date, fechaFin date)
  RETURNS table (url text)
AS $$
  BEGIN
  RETURN QUERY WITH fechas AS (SELECT * FROM generate_series(fechaInicio, fechaFin, '1 day'::interval) fecha) select getDiarioFullUrl(fecha::date) from fechas;
  END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION getDetalleEdicionUrl(codigo int)
  RETURNS text
AS $$
  BEGIN
  return 'http://diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario='||codigo;
  END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION getDetalleEdicionUrl(diario json)
  RETURNS TABLE (detalleEdicionURL text)
AS $$
  DECLARE
    edicionID text[];
  BEGIN
    SELECT array(SELECT trim(both '"' from (json_array_elements(diario->'ejemplares')->'id')::text)) INTO edicionID;
    
    RETURN QUERY SELECT DISTINCT 'http://diariooficial.gob.mx/BB_DetalleEdicion.php?cod_diario='||unnest from unnest(edicionID) WHERE length(unnest)>0 and unnest!='null';
  END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION getDOFTable(fechaInicio date,fechaFin date)
  RETURNS TABLE (fecha date, urlWS text, respuesta json, servicio text)
AS $$
  BEGIN
  RETURN QUERY WITH fechas AS (SELECT * FROM generate_series(fechaInicio, fechaFin, '1 day'::interval) fecha)
  select (getDOFTable(fechas.fecha::date)).* from fechas;
  END
$$ LANGUAGE plpgsql;


CREATE OR REPLACE FUNCTION getDOFTable(fechaConsulta date)
  RETURNS TABLE (fecha date, urlWS text, respuesta json, servicio text)
AS $$
  DECLARE
    diarioFull json;
    diarioFullUrl text;
    detalleEdicionUrl text[];
  BEGIN
  SELECT getDiarioFullUrl(fechaConsulta) INTO diarioFullUrl;
  SELECT getDataFromURL(diarioFullUrl) INTO diarioFull;
  
  SELECT array(SELECT getDetalleEdicionUrl(diarioFull)) INTO detalleEdicionUrl;
  
  RETURN QUERY SELECT foo.fecha, foo.url,foo.respuesta::json,foo.servicio FROM (
    SELECT fechaConsulta as fecha, diarioFullUrl as url, diarioFull::text as respuesta, 'diarioFull' as servicio WHERE (select count(*) from json_array_elements(diarioFull->'ejemplares') as ejemplares WHERE (ejemplares->'id')::text != 'null')>0 UNION
    SELECT fechaConsulta as fecha, unnest, getDataFromURL(unnest)::text, 'detalleEdicion' FROM unnest(detalleEdicionUrl)) AS foo;
  END
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION populateDOFTable(fechaInicio date,fechaFin date)
  RETURNS VOID
AS $$
  DECLARE r record;
  BEGIN
  FOR r IN SELECT fecha FROM generate_series(fechaInicio, fechaFin, '1 day'::interval) fecha
  LOOP
      PERFORM populateDOFTable(r.fecha::date);
  END LOOP;
  END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION populateDOFTable(fecha date)
  RETURNS VOID
AS $$
  BEGIN
  INSERT INTO dof SELECT * FROM getDOFTable(fecha);
  END;
$$ LANGUAGE plpgsql;

CREATE OR REPLACE FUNCTION insertNOMData() RETURNS trigger
AS $$
BEGIN
  WITH diario AS (select NEW.fecha,NEW.respuesta,NEW.servicio),
  entries as (select fecha,servicio,unnestJSON(respuesta) from diario),
  diarioFull AS ( select distinct fecha,servicio,getClaveNOM(unnestjson),btrim(COALESCE((unnestjson->'titulo')::text,(unnestjson->'tituloDecreto')::text,'SIN TITULO'),'"') AS titulo, btrim(COALESCE((unnestjson->'id')::text,(unnestjson->'cod_nota')::text,'404'),'"') AS cod_nota from entries WHERE servicio = 'diarioFull'),
  detalleEdicion AS ( select distinct fecha,servicio,getClaveNOM(unnestjson),btrim(COALESCE((unnestjson->'titulo')::text,(unnestjson->'tituloDecreto')::text,'SIN TITULO'),'"') AS titulo, btrim(COALESCE((unnestjson->'id')::text,(unnestjson->'cod_nota')::text,'404'),'"') AS cod_nota from entries WHERE servicio = 'detalleEdicion'),
  uniqueEntries AS (SELECT * FROM diarioFull UNION SELECT * FROM detalleEdicion WHERE cod_nota not in (SELECT DISTINCT cod_nota from diarioFull)),
  firstNote AS (SELECT cod_nota,min(fecha) as fecha FROM uniqueEntries group by cod_nota),
  insertValue as (SELECT fecha,cod_nota::int AS cod_nota,getclavenom as claveNOM,normalizaClaveNOM(getclavenom) as claveNOMNorm,titulo,
  'http://diariooficial.gob.mx/nota_detalle.php?codigo='||cod_nota||'&fecha='||CASE WHEN length((extract(day from fecha))::text)=1 THEN '0' ELSE '' END || extract(day from fecha)||'/'||CASE WHEN length((extract(month from fecha))::text)=1 THEN '0' ELSE '' END || extract(month from fecha)||'/'||extract(year from fecha) AS urlnota
  FROM uniqueEntries NATURAL JOIN firstNote)
 
  INSERT INTO notasNom(fecha, cod_nota, clavenom, clavenomnorm, titulo, urlnota)  (select fecha, cod_nota, clavenom, clavenomnorm, fixBadEncoding(entity2char(titulo)), urlnota from insertValue);

  RETURN NEW;
END; $$ LANGUAGE plpgsql;

CREATE TRIGGER insertNOMData AFTER INSERT ON dof
    FOR EACH ROW EXECUTE PROCEDURE insertNOMData();
    
--- Evita la inserción de duplicados y en caso de que exista un mejor registro actualiza el título para evitar errores de codificación

CREATE OR REPLACE FUNCTION beforeInsertNotasNOM() RETURNS trigger AS $beforeInsertNotasNOM$
    DECLARE
      title text;
    BEGIN
        IF EXISTS (SELECT cod_nota,claveNOMNorm FROM notasNOM WHERE cod_nota = NEW.cod_nota AND claveNOMNorm = NEW.claveNOMNorm) THEN
          IF EXISTS (with words as (select distinct regexp_split_to_table(NEW.titulo, E'\\s+|\\-+|\\.+|,+|\\(|\\)|;|\\\\|"') as word) select word from words where word ~* '(^|\w)\?[?\-!.]') THEN
            RETURN NULL;
          ELSE
            UPDATE notasNOM SET titulo = NEW.titulo WHERE cod_nota = NEW.cod_nota AND claveNOMNorm = NEW.claveNOMNorm;
            RETURN NULL;
          END IF;
        END IF;
        RETURN NEW;
    END;
$beforeInsertNotasNOM$ LANGUAGE plpgsql;

CREATE TRIGGER beforeInsertNotasNOM BEFORE INSERT ON notasNom
    FOR EACH ROW EXECUTE PROCEDURE beforeInsertNotasNOM();


--- Crea un string parseable a arreglo de strings y actualiza los productos y ramos si la NOM ya existe
CREATE OR REPLACE FUNCTION beforeInsertVigenciaNOM() RETURNS TRIGGER AS $$
BEGIN
  NEW.clavenomnorm = btrim(NEW.clavenomnorm,' ');

  IF NEW.producto IS NOT NULL THEN
      NEW.producto:= '{"'||replace(NEW.producto,'"','\"')||'"}';
  END IF;

  IF NEW.rama IS NOT NULL THEN
      NEW.rama:= '{"'||replace(NEW.rama,'"','\"')||'"}';
  END IF;

  IF EXISTS (SELECT clavenomnorm from vigenciaNOMs WHERE claveNOMNorm=NEW.claveNOMNorm) THEN
    NEW.updated_at:= NOW();
    UPDATE vigenciaNOMs set
      estatus = COALESCE(NEW.estatus,estatus),
      producto = (SELECT (ARRAY(SELECT DISTINCT UNNEST(array_cat(NEW.producto::text[], (COALESCE(producto, '{}'))::text[])) ORDER BY 1))::text),
      rama = (SELECT (ARRAY(SELECT DISTINCT UNNEST(array_cat(NEW.rama::text[], (COALESCE(rama, '{}'))::text[])) ORDER BY 1))::text),
      updated_at = NOW()
      WHERE clavenomnorm = NEW.clavenomnorm;   
    RETURN NULL;
  END IF;
  NEW.created_at:= NOW();
  NEW.updated_at:= NOW();
  RETURN NEW;
END
$$ LANGUAGE PLPGSQL;

CREATE TRIGGER beforeInsertVigenciaNOM BEFORE INSERT ON vigenciaNOMs
  FOR EACH ROW EXECUTE PROCEDURE beforeInsertVigenciaNOM();


------------------------

CREATE OR REPLACE FUNCTION unnestJSON(jsonstring json)
  RETURNS TABLE ( unnestJSON json)
AS $$
  import collections, json

  jsondata = json.JSONDecoder(object_pairs_hook=collections.OrderedDict).decode((str(jsonstring)))
  
  def unnestedArray(jsonObject):
      result = []
      result.append({});
      idx=0
      if (isinstance(jsonObject,list)):
          jsonObj = enumerate(jsonObject);
      else:
          jsonObj = jsonObject;
      if (type(jsonObj) is enumerate or type(jsonObj) is dict or type(jsonObj) is collections.OrderedDict):
          for key in jsonObj:
              if type(key) is tuple:
                  key = key[0]
              response = unnestedArray(jsonObject[key])
              if (isinstance(response,str)):
                  auxResponse = response
                  result[idx].update({key: auxResponse});

                  for key3,value in enumerate(result):
                      if (len(result[key3].keys() - [key]) == len(result[key3].keys())):
                          result[idx].update({key: auxResponse})            
              else:
                  for key2,value in enumerate(response):
                      d = result[idx].copy();
                      d.update(value)
                  
                      if (len(result[idx].keys() - value.keys()) == len(result[idx].keys())):
                          result[idx].update(value)
                      else:
                          result.append(result[idx].copy())
                          result[idx+1].update(value)
                          idx = idx +1;
      else:
          result = str(jsonObject);

      return (result)
      
  result = unnestedArray(jsondata)

  for key,value in enumerate(result):
    result[key] = json.dumps(value)
    
  return (result)
$$ LANGUAGE plpython3u;

CREATE OR REPLACE FUNCTION getClaveNOM(notajson json)
  RETURNS TABLE ( claveNOM text)
AS $$
  import json,re,html.parser
  result = []
  
  contentLine = html.parser.HTMLParser().unescape(notajson);    
  regexpr = '((?:norma\s+oficial\s+mexicana\s*(?:espec.{1,2}fica\s*)?(?:de\s+emergencia,?\s*(?:denominada\s*)?)?(?:\(?\s*emergente\s*\)?\s*)?(?:\(?\s*con\s+\car.{1,2}cter\s+(?:de\s+emergencia|emergente)\s*\)?\s*,?\s*)?(?:\s*n.{1,2}mero\s*)?(?:\s*\-\s*)?\s)|(?P<prefijo>(?<=[^\w])(\w+\s*[\-\/]\s*)*?NOM(?:[-.\/]|\s+[^a-z])+))(?P<clave>(?:(?:NOM-?)?[^;"]+?)(?:\s*(?:(?=[,.]\s|[;"]|[^\d\-\/]\s[^\d])|\d{4}|\d(?=\s+[^\d]+[\s,;:]))))';

  matches = re.findall(regexpr, contentLine, re.IGNORECASE)
  result = [];
  
  for match in matches:
      claveCorregida = match[1] + match[-1]
      claveCorregida = claveCorregida.replace("nicos- NOM","NOM").replace("electrónicos- NOM","NOM").replace("\\fNOM","NOM").replace('.)','')
      claveCorregida = re.sub('^[^\d]+$','',claveCorregida)

      if (len(claveCorregida)>0):
        result.append(claveCorregida)
            
  return (result)
$$ LANGUAGE plpython3u;

CREATE OR REPLACE FUNCTION normalizaClaveNOM(claveNOM text)
  RETURNS text
AS $$
  import re, logging
  global clavenom
  clavenom = clavenom.upper();
  clavenom = re.sub('\s*/\s*','/',clavenom)
  clavenom = re.sub('[\-\s,]+','-',clavenom)

  if (clavenom[0].isnumeric()):
      clavenom = 'NOM-'+clavenom;
  claveSplited = clavenom.split("-");

  if(len(claveSplited)>=2 and  claveSplited[1].isnumeric()):
      while len(claveSplited[1]) < 3:
          claveSplited[1] = '0' + claveSplited[1];
  elif(len(claveSplited)>=3 and claveSplited[2].isnumeric()):
      while len(claveSplited[2]) < 3:
          claveSplited[2] = '0' + claveSplited[2];
  if(claveSplited[-1].isnumeric() and len(claveSplited[-1])==2):
      if (int(claveSplited[-1])>20):
          claveSplited[-1] = '19' + claveSplited[-1];
      else:
          claveSplited[-1] = '20' + claveSplited[-1];
  
  claveNOMNormalizada = '-'.join(claveSplited);

  claveNOMRenombrada = plpy.execute("SELECT claveNOMActualizada from clavesRenombradas where claveNOMObsoleta = '" + claveNOMNormalizada + "'",1)

  if claveNOMRenombrada.nrows() > 0:
    try:
      claveNOMNormalizada = claveNOMRenombrada[0]['claveNOMActualizada']
    except Exception as inst:
      logging.error(inst);
  
  return claveNOMNormalizada
$$ LANGUAGE plpython3u;

CREATE OR REPLACE FUNCTION getPartialSentence(word text, sentence text)
  RETURNS TEXT
AS $$
  import re
  regexpr = '.*?\(?((?:\([^\)]+|[^\(]+|(\.\s+|^).*\(.*\)[^\)]+))' + word
  result = re.search(regexpr, sentence, re.IGNORECASE)
  return result.group(0) if result != None else None;
$$ LANGUAGE plpython3u;

-- Requires NLTK http://www.nltk.org/
CREATE OR REPLACE FUNCTION classifyNOM(clavenom text, titulo text)
  RETURNS TEXT
AS $$
  import nltk
  import re
  import html.parser
  import json
  
  def nom_features(clavenom,titulo):
    featureset = {}
    titulo = titulo.replace("'","\\'")
    queryresult = plpy.execute("select lower(entity2char(getpartialSentence('"+clavenom+"',E'"+titulo+"'))) as context",1);

    if queryresult.nrows() >0:
      context = queryresult[0]['context'].strip() if queryresult[0]['context'] != None else ''
      context = re.sub(clavenom + '.*$','',context, re.IGNORECASE)

    for word in context.split(' '):
      if word in featureset.keys():
        featureset[word] = featureset[word]+1;
      else:
        featureset[word] = 1;

    featureset['context'] = context;
    featureset['firstword'] = context.split(' ')[0]
    featureset['lastword'] = context.split(' ')[-1]
    featureset['countwords'] = len(context.split(' '))
    return featureset if len(context)>0 else ''

  featuresets = []
  knowledgeTable = plpy.execute("SELECT relname FROM pg_class WHERE relname='featuresets';")
  if (knowledgeTable.nrows()==0):
    plpy.execute('CREATE TEMPORARY TABLE featuresets(features text, etiqueta text)');
  
    knowledgeBase = plpy.execute('SELECT clavenom, titulo, etiqueta FROM notasnom WHERE etiqueta IS NOT NULL AND revisionHumana=True;')
    
    for value in knowledgeBase:
      features = nom_features(value['clavenom'], value['titulo'])
      featuresets.append((features, value['etiqueta']));
      plpy.execute('INSERT INTO featuresets VALUES (\''+ json.dumps(features) + '\',\'' + value['etiqueta']+'\')')
  else:
    knowledgeBase = plpy.execute('SELECT features, etiqueta FROM featuresets;')
    for value in knowledgeBase:
      featuresets.append((json.loads(value['features']), value['etiqueta']));
      
  train_set = featuresets
  classifier = nltk.NaiveBayesClassifier.train(train_set)

  titulounescape =  html.parser.HTMLParser().unescape(titulo)
  nom_featuresRes = nom_features(clavenom,titulounescape)
  return classifier.classify(nom_featuresRes) if len(nom_featuresRes)>0 else None
  
$$ LANGUAGE plpython3u;



--- HTML Entity replacement ---
--- Source: http://stackoverflow.com/questions/14961992/postgresql-replace-html-entities-function ---

create table character_entity(
    name text primary key,
    ch char(1) unique
);


create or replace function entity2char(t text)
returns text as $body$
declare
    r record;
begin
    for r in
        select distinct ce.ch, ce.name
        from
            character_entity ce
            inner join (
                select name[1] "name"
                from regexp_matches(t, '&([A-Za-z]+?);', 'g') r(name)
            ) s on ce.name = s.name
    loop
        t := replace(t, '&' || r.name || ';', r.ch);
    end loop;

    for r in
        select distinct
            hex[1] hex,
            ('x' || repeat('0', 8 - length(hex[1])) || hex[1])::bit(32)::int codepoint
        from regexp_matches(t, '&#x([0-9a-f]{1,8}?);', 'gi') s(hex)
    loop
        t := regexp_replace(t, '&#x' || r.hex || ';', chr(r.codepoint), 'gi');
    end loop;

    for r in
        select distinct
            chr(codepoint[1]::int) ch,
            codepoint[1] codepoint
        from regexp_matches(t, '&#([0-9]{1,10}?);', 'g') s(codepoint)
    loop
        t := replace(t, '&#' || r.codepoint || ';', r.ch);
    end loop;

    return t;
end;
$body$
language plpgsql immutable;

--- Funciones auxiliares
--- Source: http://www.postgresql.org/message-id/000b01cb5b29$715d0760$54171620$@com
CREATE OR REPLACE FUNCTION "titlecase" (
  "v_inputstring" varchar
)
RETURNS varchar AS
$body$
/*
select * from Format_TitleCase('MR DOG BREATH');
select * from Format_TitleCase('each word, mcclure of this string:shall be
transformed');
select * from Format_TitleCase(' EACH WORD HERE SHALL BE TRANSFORMED	TOO
incl. mcdonald o''neil o''malley mcdervet');
select * from Format_TitleCase('mcclure and others');
select * from Format_TitleCase('J & B ART');
select * from Format_TitleCase('J&B ART');
select * from Format_TitleCase('J&B ART J & B ART this''s art''s house''s
problem''s 0''shay o''should work''s EACH WORD HERE SHALL BE TRANSFORMED
TOO incl. mcdonald o''neil o''malley mcdervet');
*/

DECLARE
   v_Index  INTEGER;
   v_Char  CHAR(1);
   v_OutputString  VARCHAR(4000);
   SWV_InputString VARCHAR(4000);

BEGIN
   SWV_InputString := v_InputString;
   SWV_InputString := LTRIM(RTRIM(SWV_InputString)); --cures problem where string starts with blank space
   v_OutputString := LOWER(SWV_InputString);
   v_Index := 1;
   v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,1,1)) from 1 for 1); -- replaces 1st char of Output with uppercase of 1st char from Input
   WHILE v_Index <= LENGTH(SWV_InputString) LOOP
      v_Char := SUBSTR(SWV_InputString,v_Index,1); -- gets loop's working character
      IF v_Char IN('m','M','
',';',':','!','?',',','.','_','-','/','&','''','(',CHR(9)) then
		 --END4
         IF v_Index+1 <= LENGTH(SWV_InputString) then
            IF v_Char = '''' AND UPPER(SUBSTR(SWV_InputString,v_Index+1,1))
<> 'S' AND SUBSTR(SWV_InputString,v_Index+2,1) <> REPEAT(' ',1) then  -- if the working char is an apost and the letter after that is not S
               v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,v_Index+1,1)) from v_Index+1 for 1);
            ELSE 
               IF v_Char = '&' then    -- if the working char is an &
                  IF(SUBSTR(SWV_InputString,v_Index+1,1)) = ' ' then
                     v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,v_Index+2,1)) from v_Index+2 for 1);
                  ELSE
                     v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,v_Index+1,1)) from v_Index+1 for 1);
                  END IF;
               ELSE
                  IF UPPER(v_Char) != 'M' AND
(SUBSTR(SWV_InputString,v_Index+1,1) <> REPEAT(' ',1) AND
SUBSTR(SWV_InputString,v_Index+2,1) <> REPEAT(' ',1)) then
                     v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,v_Index+1,1)) from v_Index+1 for 1);
                  END IF;
               END IF;
            END IF;

					-- special case for handling "Mc" as in McDonald
            IF UPPER(v_Char) = 'M' AND
UPPER(SUBSTR(SWV_InputString,v_Index+1,1)) = 'C' then
               v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,v_Index,1)) from v_Index for 1);
							--MAKES THE C LOWER CASE.
               v_OutputString := OVERLAY(v_OutputString placing
LOWER(SUBSTR(SWV_InputString,v_Index+1,1)) from v_Index+1 for 1);
							-- makes the letter after the C UPPER case
               v_OutputString := OVERLAY(v_OutputString placing
UPPER(SUBSTR(SWV_InputString,v_Index+2,1)) from v_Index+2 for 1);
							--WE TOOK CARE OF THE CHAR AFTER THE C (we handled 2 letters instead of only 1 as usual), SO WE NEED TO ADVANCE.
               v_Index := v_Index+1;
            END IF;
         END IF;
      END IF; --END3

      v_Index := v_Index+1;
   END LOOP; --END2

   RETURN coalesce(v_OutputString,'');
END;
$body$
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
COST 100;

---- Fix bad encoding using dictionary
CREATE OR REPLACE FUNCTION fixBadEncoding(t text) RETURNS text AS
$$
  DECLARE r record;
BEGIN
  for r in
    SELECT distinct dic.wrong, dic.good FROM diccionario dic INNER JOIN (SELECT wrong[2] AS wrong FROM regexp_matches(t, '(^|\s|\(|\-|")(\w*(\?.\w*)+\w*)', 'g') wrong) s ON s.wrong = dic.wrong
  loop
    t:= replace(t,r.wrong,r.good);
  end loop;
  RETURN t;
END$$ LANGUAGE plpgsql;
