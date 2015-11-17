#!/usr/bin/env python3
import fileinput
import nltk
import re
import html.parser
#import json
import sys,getopt
import csv
import re
import string
# Patrones de Expresiones Regulares que validan un token cómo perteneciente a una categoria
# Definición de las palabras que se quieren clasificar según su contexto
# 
patterns = [
  ('NOMS', '((?:norma\s+oficial\s+mexicana\s*(?:espec.{1,2}fica\s*)?(?:de\s+emergencia,?\s*(?:denominada\s*)?)?(?:\(?\s*emergente\s*\)?\s*)?(?:\(?\s*con\s+\car.{1,2}cter\s+(?:de\s+emergencia|emergente)\s*\)?\s*,?\s*)?(?:\s*n.{1,2}mero\s*)?(?:\s*\-\s*)?\s)|(?P<prefijo>(?<=[^\w])(\w+\s*[\-\/]\s*)*?NOM(?:[-.\/]|\s+[^a-z])+))(?P<clave>(?:(?:NOM-?)?[^;"]+?)(?:\s*(?:(?=[,.]\s|[;"]|[^\d\-\/]\s[^\d])|\d{4}|\d(?=\s+[^\d]+[\s,;:]))))'),
  ('NMX', '((?:norma\s+mexicana\s*(?:espec.{1,2}fica\s*)?(?:de\s+emergencia,?\s*(?:denominada\s*)?)?(?:\(?\s*emergente\s*\)?\s*)?(?:\(?\s*con\s+\car.{1,2}cter\s+(?:de\s+emergencia|emergente)\s*\)?\s*,?\s*)?(?:\s*n.{1,2}mero\s*)?(?:\s*\-\s*)?\s)|(?P<prefijo>(?<=[^\w])(\w+\s*[\-\/]\s*)*?NMX(?:[-.\/]|\s+[^a-z])+))(?P<clave>(?:(?:NOM-?)?[^;"]+?)(?:\s*(?:(?=[,.]\s|[;"]|[^\d\-\/]\s[^\d])|\d{4}|\d(?=\s+[^\d]+[\s,;:]))))')
]


# Oración en la que está localizada una palabra
def getContext(word, sentence):
  import re
  regexpr = '.*?\(?((?:\([^\)]+|[^\(]+|(\.\s+|^).*\(.*\)[^\)]+))' + word
  result = re.search(regexpr, sentence, re.IGNORECASE)
  return re.sub(word + '.*$','', result.group(0), re.IGNORECASE).strip().lower() if result != None else None;


# Obtiene las carácteristicas que determinan a que categoria pertenece una palabra según su contexto
def getFeatures(clavenom,oracion):
  titulo=oracion
  featureset = {}
  titulo = titulo.replace("'","\\'")
  context = getContext(clavenom, titulo)

  wordsArray = context.split(' ')

  for word in wordsArray:
    if word in featureset.keys():
      featureset[word] = featureset[word]+1;
    else:
      featureset[word] = 1;

  featureset['context'] = context;
  featureset['firstword'] = wordsArray[0]
  featureset['lastword'] = wordsArray[-1]
  #featureset['countwords'] = len(wordsArray)
  return featureset if len(context)>0 else ''

def getTrainingSetV2(file):
  f = open(file, 'rt', encoding='utf-8')
  trainingSet = []
  titulo = None
  claveNom = None
  etiqueta = None
  line = 0
  try:
    reader = csv.reader(f)
    for row in reader:
      for index,field in enumerate(row):
        if line == 0:
          if field == 'titulo':
            titulo = index
          elif field == 'clave':
            claveNom = index
          elif field == 'etiqueta':
            etiqueta = index
        elif titulo== None or claveNom== None or etiqueta== None:
          print(str(line) + "\t" + str(not titulo or not claveNom or not etiqueta))
          print(str(titulo) + "\t" + str(claveNom) + "\t" + str(etiqueta))
          print('El archivo debe contener como mínimo los campos "titulo", "clave" y "etiqueta"')
          sys.exit(2)
        else:
          tokens = findTokens(row[titulo], row[claveNom])
          #print(tokens)
          for token in tokens:
            #print(token[-1])
            trainingSet.append((token[-1], row[etiqueta]))
      line += 1
  finally:
    f.close()
  return trainingSet


def getTrainingSet(file):
  f = open(file, 'rt', encoding='utf-8')
  trainingSet = []
  titulo = None
  claveNom = None
  etiqueta = None
  line = 0
  try:
    reader = csv.reader(f)
    for row in reader:
      for index,field in enumerate(row):
        if line == 0:
          if field == 'titulo':
            titulo = index
          elif field == 'clave':
            claveNom = index
          elif field == 'etiqueta':
            etiqueta = index
        elif titulo== None or claveNom== None or etiqueta== None:
          print(str(line) + "\t" + str(not titulo or not claveNom or not etiqueta))
          print(str(titulo) + "\t" + str(claveNom) + "\t" + str(etiqueta))
          print('El archivo debe contener como mínimo los campos "titulo", "clave" y "etiqueta"')
          sys.exit(2)
        else:
          features = getFeatures(row[claveNom], row[titulo])
          trainingSet.append((features, row[etiqueta]))
      line += 1
  finally:
    f.close()
  return trainingSet



def findClaves(contentLine, pattern):
  result = []

  matches = re.findall(pattern, contentLine, re.IGNORECASE)

  for match in matches:
    claveCorregida = match[1] + match[-1]
    claveCorregida = claveCorregida.replace("nicos- NOM","NOM").replace("electrónicos- NOM","NOM").replace("\\fNOM","NOM").replace('.)','')
    claveCorregida = re.sub('^[^\d]+$','',claveCorregida)
    result.append(claveCorregida)

  return result

def usageMenu():
  print ('USO: ' + sys.argv[0] + ' [ENUNCIADO]')
  print ('Identifica la mensión de una NMX en un enunciado y clasifica su contexto');
  print ('')
  print ('\t-t, --training-data=TRAININGDATA\tArchivo que se usará de entrenamiento para el clasificador')
  print ('\t-f, --read-from-file=INPUTFILE\tLeer las oraciones de un archivo de entrada.')

  #print ('clasificador.py -f <inputFile> -t <inputTraining>')

def inputErrorException():
  print (sys.argv[0] + ': invalid option')
  print ('Try `'+ sys.argv[0] + ' --help` for more information.' )
  sys.exit(2)

def readInput():
  inputTraining = None
  sentence = None
  inputFile = None
  match = None
  header = True

  try:
    opts, args = getopt.getopt(sys.argv[1:],"ht:f:m:l",["help", "training-file=", "input-file=", "match=", "headerless"])
    for opt, value in opts:
      if opt in ("-t", "--training-file"):
        inputTraining = value
      elif opt in ('-f', '--input-file'):
        inputFile = value
      elif opt in ('-m' , '--match'):
        match = value
      elif opt in ('-l' , '--headerless'):
        header = False
      elif opt in ('-h', '--help'):
        usageMenu()
        sys.exit()        
    if(len(args)>0):
        sentence = args
  except getopt.GetoptError:
    inputErrorException()
  
  while(len(sys.argv)>1):
    del sys.argv[1]


  return sentence, inputFile, inputTraining, match, header

def getContextV2(contextualizable, sentence):
  word, start, end = None, None, None
  """El contenedor de una frase se define como una tupla de expresiones regulares que identifican un inicio y un final"""
  phraseWrappers = [('\(', '\)'), ('\.\s', '(\.|$)'), ('^', '(\.|$)')]

  # Cuando la clase es un string 
  if (type(contextualizable) == type('')):
    word =contextualizable
  elif (type(contextualizable) == type(()) and len(contextualizable) == 3):
    word, start, end = contextualizable
  elif (type(contextualizable) == type(()) and len(contextualizable) == 2):
    start, end = contextualizable
  else:
    return None

  context = None

  """
  Itera sobre todos los posibles delimitadores de frases hasta entrar la frase más corta que contiene la palabra de interés
  El resultado es más preciso cuando se especifica la posicion de la palabra de interés mediante una tupla (palabra, inicio, fin) o (inicio, fin)
  """
  if (start and end):
    length = end-start
    for wrapper in phraseWrappers:
      firstPartRegex = '(?<='+wrapper[0]+')([^'+re.sub('(?<=[^\\\\])[|$]|^\(|(?<=[^\\\\])\)$','',wrapper[1])+']{0,'+str(start)+'}?)'
      begining = re.search(firstPartRegex+'$', sentence[:start], re.IGNORECASE)
      if begining:

        match = re.search(
          '(.{'+str(begining.start())+'})('+firstPartRegex+'('+re.escape(sentence[start:end])+')(.*?)'+'(?='+wrapper[1]+'))'
          , sentence
          , re.IGNORECASE)
        if match and (not context or len(context)> len(match.group(2))):
          context = match.group(2)
  elif word:
    """
    whileExecuted = False
    auxContext = None
    while auxContext != context or not whileExecuted:
      whileExecuted = True#if (type(contextualizable) == type('')) word =contextualizable
      auxContext = context
      for wrapper in phraseWrappers:

        match = re.search('(?<='+wrapper[0] + ')[^(' + wrapper[1]+')]+?'+re.escape(word)+'.*?(?='+wrapper[1]+')', context if context else sentence, re.IGNORECASE)
        if match:
          context = match.group(0)
    """
    pass
  """
  context = context.strip(' "\t\r\n' + string.punctuation);


  """
  """
  strippableWords = ('normas?', 'mexicanas?', 'emergencia', 'indican?', 'n[uú]meros?', '\d+' , 'y', 'de', 'a', 'las?', 'que', 'se', ',')
  stripWordsRegex = '(?i)'

  for word in strippableWords:
    stripWordsRegex += '(^'+word + '\s+)|(\s+'+word+'$)|'
  stripWordsRegex = stripWordsRegex.strip('|')

  #print(stripWordsRegex)
  whileExecuted = False
  auxContext = None
  while auxContext != context or not whileExecuted:
    whileExecuted = True
    auxContext = context
    context = re.sub(stripWordsRegex, '', context, re.IGNORECASE).strip()
  """
  return context

def stringFeatures(inputString):
  featureset = {}
  inputString = inputString.lower()
  context = inputString
  context = re.sub('(?i)(normas?\s+mexicanas?).*', '', context)
  context = re.sub('(?<=^)?(?<=\s)?[^\s]*?\-[^\s]*?(?=$|\s|,\s)', '', context).strip("y, ")

  atricles = ('el', 'las?', 'los', 'un(a|os|as)', 'lo', 'al', 'a la', 'del', 'de el', 'de', 'a', '\d+', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diiembre', 'esta', 'normas?')
  removeArticlesRegex = '(?i)'

  for article in atricles:
    removeArticlesRegex += '((?<=^)|(?<=\s))'+article + '(?=['+string.punctuation+'\s]|$)|'
  removeArticlesRegex = removeArticlesRegex.strip('|')

  context = re.sub(removeArticlesRegex, '', context)
  context = re.sub('\s+(,?)', r"\1 ", context).strip(", y")
  context = re.sub('\s+', " ", context).upper().replace('P??BLICA', 'PÚBLICA').replace('ACLARACI??N', 'ACLARACIÓN').replace('CANCELACI??N', 'CANCELACIÓN').replace('|', '');

  context = re.sub('(?i)(,|(?<=[^\w])(para|sobre|mediante|y)(?=[^\w])).*', '', context).strip("y, ")

  clearInput = re.sub('[^\s]+[^\d\w\s]+[^\s]*', '', inputString)
  for word in nltk.word_tokenize(context):
    if (len(word)>4):
      featureset['contains({})'.format(word.lower())] = True

  #for word in wordsArray:
  #  if word in featureset.keys():
  #    featureset[word] = featureset[word]+1;
  #  else:
  #    featureset[word] = 1;

  featureset['context_summary'] = context
  #featureset['firstword'] = wordsArray[0]
  #featureset['lastword'] = wordsArray[-1]
  #featureset['countwords'] = len(wordsArray)
  return featureset


def normalizeNMX(word):
  clave = re.sub('(?i)d[^\d\w]{0,3}g[^\d\w]{0,3}n[^\d\w]{0,3}', 'NMX-', word);
  clave = re.sub('[^\d\w/]+', '-', clave);
  clave = re.sub('-(\d)-', r'-00\1-', clave);
  clave = re.sub('-(\d{2})-', r'-0\1-', clave);
  clave = re.sub('[^\d\w]+$', '', clave);
  return clave

def findTokens(sentences, match, pre=None):
  contextualizedTokens = []

  if (type(sentences) == type('')):
    sentences = {sentences}
  for sentence in sentences:
    #print(sentence, match)
    sentence = sentence.strip('\n\r\t"')
    tokensOfInterest = [(m.group(0), m.start(), m.end()) for m in re.finditer(match,sentence, re.IGNORECASE)]
    for token in tokensOfInterest:
      word, start, end = token
      context = getContextV2((word, start, end), sentence)
      if (context):
        features = stringFeatures(context)
        #contextualizedTokens.append((word, ''));
        clave = normalizeNMX(word);
        contextualizedTokens.append(((pre if pre != None else [])+[word, clave, sentence, context, '',features]));
  return contextualizedTokens

def main():
  sentences, inputFile, inputTraining, match, header = readInput()

  writer = csv.writer(sys.stdout, quoting=csv.QUOTE_ALL)

  headerString = []

  if (not sentences and inputFile):
    match = match.split(',');

    if(len(match)==2):
      reader = csv.reader(open(inputFile))
      match[0] = int(match[0])
      match[1] = int(match[1])

      tokensOfInterest = []
      line = 0
      for row in reader:
        if (line==0):
          headerString = row
        else:
          tokensOfInterest += findTokens(row[match[1]-1], re.escape(row[match[0]-1]), row)
        line +=1
  else:
    tokensOfInterest = findTokens(sentences if sentences else fileinput.input(inputFile), match)

  if (header):
    writer.writerow(headerString + ['clave_nmx', 'clave', 'titulo_nota', 'contexto_clave_nmx', 'categoria', 'features'])
  
  
    #sys.exit()
  
      
  
  if (inputTraining):
    trainingSet = getTrainingSetV2(inputTraining);
    classifier = nltk.NaiveBayesClassifier.train(trainingSet)

    for token in tokensOfInterest:
      writer.writerow(token[0:-3]+ [classifier.classify(token[-1]), token[-1]])
  """
    for tokenType, pattern in patterns:
      claves = findClaves(sentence, pattern)

      for clave in claves:
        features = getFeatures(clave, sentence)
        print (tokenType + "\t" + clave + "\t" + classifier.classify(features))
  """      

  
if __name__ == "__main__":
   main()