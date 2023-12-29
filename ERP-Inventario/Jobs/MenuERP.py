from CJuradosTesis import *
from CMatIdiomas import *
import os
import time
import sys
#import thread

def main(p_Proceso):
    ltTime1 = time.time()
    if p_Proceso == '1':
       lo = CJuradosTesis()
       llOk = lo.omJuradosTesisTodos()
       if not llOk:
          print ('*** ERROR al Cargar Jurados Tesis de Unidades Academicas ***')
          print (lo.pcError)
       else:
          print ('PROCESO CONFORME - Cargar Jurados Tesis de Unidades Academicas')
    elif p_Proceso == '2':
       lo = CMatIdiomas()
       llOk = lo.omPagosCentroIdiomas()
       if not llOk:
          print ('*** ERROR al ejecutar Matriculas de Idiomas ***')
          print (lo.pcError)
       else:
          print ('PROCESO CONFORME - Matriculas Idiomas')
    else:
       print ('PROCESO INCORRECTO')
    print (fxElapseTime(time.time() - ltTime1))


def fxElapseTime(p_nTime):
    lnHoras  = int(p_nTime/3600)
    #print 'lnHoras ', lnHoras
    lnTime   = p_nTime%3600
    #print 'lnTime ', lnTime
    lnMinuto = int(lnTime/60)
    #print 'lnMinuto ', lnMinuto
    lnSegund = lnTime - lnMinuto * 60
    #print 'lnSegund ', lnSegund
    lnCentes = lnSegund - int(lnSegund)
    #print '1)', lnCentes
    lcCentes = '%0.2f'%lnCentes
    #print '2)', lcCentes
    lcCentes = lcCentes[-3:]
    #print '3)', lcCentes
    #print p_nTime, 
    return '%03dh '%lnHoras + '%02dm '%lnMinuto + '%02d'%lnSegund + lcCentes +'s' 

main(sys.argv[1])

