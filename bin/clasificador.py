#!/usr/bin/env python3
import nltk
import re
import html.parser
import json, csv
import sys,getopt

def getContext(word, sentence):
  import re
  regexpr = '.*?\(?((?:\([^\)]+|[^\(]+|(\.\s+|^).*\(.*\)[^\)]+))' + word
  result = re.search(regexpr, sentence, re.IGNORECASE)
  return re.sub(word + '.*$','', result.group(0), re.IGNORECASE).strip().lower() if result != None else None;

def getFeatures(clavenom,titulo):
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
  featureset['countwords'] = len(wordsArray)
  return featureset if len(context)>0 else ''

def getTrainingSet(file):
  f = open(file, 'rt')
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
          elif field == 'clavenom':
            claveNom = index
          elif field == 'etiqueta':
            etiqueta = index
        elif titulo== None or claveNom== None or etiqueta== None:
          print(str(line) + "\t" + str(not titulo or not claveNom or not etiqueta))
          print(str(titulo) + "\t" + str(claveNom) + "\t" + str(etiqueta))
          print('El archivo debe contener como mínimo los campos "titulo", "clavenom" y "etiqueta"')
          sys.exit(2)
        else:
          features = getFeatures(row[claveNom], row[titulo])
          trainingSet.append((features, row[etiqueta]))
      line += 1
  finally:
    f.close()
  return trainingSet

def main(argv):
  inputfile = 'src/database/data/knowledgebase.csv'
  testString = 'ACUERDO por el cuál se modifica la NOM-SEMARNAT-001'
  try:
    opts, args = getopt.getopt(argv,"hi:o:",["ifile=","ofile="])
  except getopt.GetoptError:
    print ('clasificador.py -i <inputfile> -o <outputfile>')
    sys.exit(2)
  for opt, arg in opts:
    if opt in ("-i", "--ifile"):
      inputfile = arg
    elif opt in ("-o", "--ofile"):
      outputfile = arg
    elif opt == '-h':
      print ('clasificador.py -i <inputfile> -o <outputfile>')
      sys.exit()

  if(len(args)>0):
    testString = testString


  print ('Input file is: '+ inputfile)
  print ('Test string is: ' + testString)

  
  #print (features)

  trainingSet = getTrainingSet(inputfile);
  classifier = nltk.NaiveBayesClassifier.train(trainingSet)
  features = getFeatures('NOM-SEMARNAT-001', testString)  
  print (classifier.classify(features))
 
if __name__ == "__main__":
   main(sys.argv[1:])