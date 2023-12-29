#!/usr/bin/env python
#-*- coding:utf-8 -*-
import sys, json, time, random, re
from os import path
from CBase import *
from CSql import *

class CCntActivoFijo(CBase):

   def __init__(self):
       self.paData  = []
       self.paDatos = []
       self.laData  = []
       self.laDatos = []
       self.loSql   = CSql()

   def mxCargarTipoCambio(self):
       lcSql = "SELECT nTipCom, nTipVen FROM S01DCAM ORDER BY dFecCam DESC LIMIT 1"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'TIPO DE CAMBIO PARA FECHA ACTUAL NO DEFINIDO'
          return False
       self.paData['NTIPCOM'] = float(R1[0][0])
       self.paData['NTIPVEN'] = float(R1[0][1])
       return True

   # -------------------------------------------------------------------------
   # Calcula depreciacion de Activos Fijos
   # 2021-09-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omCalcularDepreciacion(self):
       print('Calculando depreciacion ...')
       lnTime1 = time.time()
       # print(111)
       llOk = self.mxValParamCalcularDepreciacion()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxValidarDepreciacion()
       if not llOk:
          self.loSql.omDisconnect();
          return False
       llOk = self.mxInicializarCalculo()
       if not llOk:
          self.loSql.omDisconnect();
          return False
       llOk = self.mxCalcularDepreciacion()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect();
       print(time.time() - lnTime1)
       return llOk;

   def mxValParamCalcularDepreciacion(self):
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9A-Z]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CPERIOD' in self.paData or not re.match('^20[0-9]{2}-[0-9]{2}$', self.paData['CPERIOD']):
          self.pcError = 'PERIODO DE CÁLCULO DE DEPRECIACIÓN NO DEFINIDO O INVÁLIDO'
          return False
       elif self.paData['CPERIOD'][-2:] < '01' or self.paData['CPERIOD'][-2:] > '12':
          self.pcError = 'MES DE PROCESO INVÁLIDO'
          return False
       elif not 'CFLAG' in self.paData or not re.match('^[S,N]{1}$', self.paData['CFLAG']):
          self.pcError = 'INDICADOR DE RECALCULO NO DEFINIDO O INVÁLIDO'
          return False
       # Variables adicionales requeridas para proceso
       self.paData['CFILERR'] = './FILES/R' + str(random.random()).replace('0.', '') + '.csv'
       self.paData['CIDDEPR'] = None
       self.loFile = None
       return True

   def mxValidarDepreciacion(self):
       lcPeriod = self.paData['CPERIOD'].replace('-', '')
       lcSql = "SELECT cIdDepr, cEstado FROM E04MDEP WHERE cPeriod = '%s'"%(lcPeriod)
       # print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) == 0:
          return True
       elif self.paData['CFLAG'] == 'N':
          self.pcError = 'DEPRECIACIÓN DE PERIODO YA CALCULADA'
          return False
       elif R1[0][1] == 'C':
          self.pcError = 'DEPRECIACIÓN YA CONTABILIZADA'
          return False
       # Eliminar la depreciacion (reprocesar)
       lcSql = "DELETE FROM E04DDEP WHERE cIdDepr = '%s'"%(R1[0][0])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO ELIMINAR CABECERA DE DEPRECIACION'
          return False
       lcSql = "DELETE FROM E04MDEP WHERE cIdDepr = '%s'"%(R1[0][0])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO ELIMINAR DETALLE DE DEPRECIACION'
          return False
       # Reinicia serial
       lcSql = "SELECT SETVAL(PG_GET_SERIAL_SEQUENCE('E04DDEP', 'nserial'), COALESCE(MAX(nSerial), 0) + 1, False) FROM E04DDEP"
       self.loSql.omExecRS(lcSql)
       return True
       
   def mxGrabarError(self, p_aFila):
       self.loFile = open(self.paData['CFILERR'], 'a')
       lcLinea = p_aFila['CFLAG'] + ';' + p_aFila['CACTFIJ'] + '\n'
       self.loFile.write(lcLinea)
       self.loFile.close()
   
   def mxInicializarCalculo(self):
       loDate = CDate()
       # Abre archivo csv de errores 
       self.loFile = open(self.paData['CFILERR'], 'w')
       self.loFile.close()
       # Siguiente correlativo  
       lcSql = "SELECT MAX(cIdDepr) FROM E04MDEP"
       #print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) == 0:
          lcIdDepr = '000'
       else:
          lcIdDepr = R1[0][0]
       self.paData['CIDDEPR'] = fxCorrelativo(lcIdDepr)
       # Periodo formato aaaamm
       lcPeriod = self.paData['CPERIOD'].replace('-', '')
       # Fecha ultima de periodo (año + mes)
       lnYear = int(self.paData['CPERIOD'][0:4])
       lnMonth = int(self.paData['CPERIOD'][5:7]) + 1
       if lnMonth == 13:   
          lnMonth = 1
          lnYear += 1
       lcMonth = '0' + str(lnMonth)
       lcMonth = lcMonth[-2:]
       ldFecha = str(lnYear) + '-' + lcMonth + '-01'
       ldMovimi = loDate.add(ldFecha, -1)
       # Graba cabecera de depreciacion 
       lcSql = "INSERT INTO E04MDEP (cIdDepr, cPeriod, dMovimi, cUsuCod) VALUES ('%s', '%s', '%s', '%s')"%\
                (self.paData['CIDDEPR'], lcPeriod, ldMovimi, self.paData['CUSUCOD'])
       #print(lcSql)   
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO INSERTAR CABECERA DE DEPRECIACION'
          return False
       # Depreciacion inicial del año
       lcPeriod = self.paData['CPERIOD'][0:4] + '00'
       lcSql = "SELECT cIdDepr FROM E04MDEP WHERE cPeriod = '%s'"%(lcPeriod)
       # print(lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) != 1:
          #print(R1)
          self.pcError = 'SALDOS INICIALES DE DEPRECIACIÓN DEL AÑO NO ENCONTRADA'
          return False
       self.paData['CDEPINI'] = R1[0][0]
       return True       

   def mxCalcularDepreciacion(self):
       # Divide los AF por tipo
       lcSql = "SELECT cTipAfj FROM E04TTIP ORDER BY cTipAfj"
      #  lcSql = "SELECT cTipAfj FROM E04TTIP WHERE cTipAfj = '03302'"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           #print(r[0])
           # Carga AFs por tipo
           self.laDatos = []
           llOk = self.mxCargarActivoFijo(r[0])
           if not llOk:
              return False
           # Calcula depreciacion de AFs
           llOk = self.mxDepreciacionCalcular()
           if not llOk:
              return False
           # Graba la depreciacion calculada
           llOk = self.mxGrabarDepreciacion()
           if not llOk:
              return False
           self.loSql.omCommit()
       return True
                     
   def mxCargarActivoFijo(self, p_cTipAfj):
       # Carga AFs de un determinado tipo de activo
       lcSql = """SELECT A.cActFij, A.nMonCal, TO_CHAR(A.dFecAlt, 'YYYY-MM-DD'), B.nFacDep, A.cTipAfj, A.nCorrel, A.nDepAcu, A.nDeprec, A.cSituac, A.mDatos
                  FROM E04MAFJ A
                  INNER JOIN E04TTIP B ON B.cTipAfj = A.cTipAfj
                  WHERE A.cTipAfj = '%s' AND A.cEstado = 'A' AND TO_CHAR(A.dFecAlt, 'YYYY-MM') < '%s' AND A.cSituac IN ('O', 'B') AND 
                  A.nMonCal > A.nDepAcu + A.nDeprec AND B.nFacDep > 0.000 ORDER BY A.cActFij"""%(p_cTipAfj, self.paData['CPERIOD'])
       #lcSql = """SELECT A.cActFij, A.nMonCal, TO_CHAR(A.dFecAlt, 'YYYY-MM-DD'), B.nFacDep, A.cTipAfj, A.nCorrel, A.nDepAcu, A.nDeprec, A.cSituac, A.mDatos
       #           FROM E04MAFJ A
       #           INNER JOIN E04TTIP B ON B.cTipAfj = A.cTipAfj
       #           WHERE A.cTipAfj = '%s' AND A.cEstado = 'A' AND TO_CHAR(A.dFecAlt, 'YYYY-MM') < '%s' AND A.cSituac IN ('O', 'B') AND 
       #           A.nMonCal > A.nDepAcu + A.nDeprec AND B.nFacDep > 0.000 and a.cactfij = '0002H' 
       #           ORDER BY A.cActFij"""%(p_cTipAfj, self.paData['CPERIOD'])
       #print(lcSql)
       # sys.exit()
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           # Verifica si baja es en el mes de proceso
           if r[8] == 'B':
              ldFecBaj = self.mxFechaBaja(r[9])
              #print(r[0], ldFecBaj )
              if ldFecBaj[0:7] != self.paData['CPERIOD']:
                 continue
              print('***')
           # Carga monto base de calculo y depreciacion anterior
           lcSql = "SELECT nMonto, nDeprec FROM E04DDEP WHERE cIdDepr = '%s' AND cActFij = '%s'"%( self.paData['CDEPINI'], r[0])
           #print(lcSql)
           R2 = self.loSql.omExecRS(lcSql)
           if R2 and len(R2) == 1:
              if float(R2[0][0]) <= 0.00 or round(float(R2[0][0]), 2) == round(float(R2[0][1]), 2):
                 # print("Hola")
                 continue
           # sys.exit()
           lcCorrel = '00000' + str(r[5])
           lcCodigo = r[4][0:2] + '-' + r[4][2:] + '-' + lcCorrel[-6:]
           # Numero de periodos a calcular la depreciacion (-1 todo el año)
           lnNumPer = int(self.paData['CPERIOD'][5:7])
           if r[2][0:4] == self.paData['CPERIOD'][0:4]:
              lnNumPer = lnNumPer - int(r[2][5:7])
           # Registro de AF
           laFila = {'CACTFIJ':r[0], 'NMONCAL':float(r[1]), 'NNUMPER':lnNumPer, 'CTIPAFJ':p_cTipAfj, 'NFACTOR':float(r[3]), \
                     'NDEPACU':float(r[6]), 'NDEPREC':float(r[7]), 'NDEPMES':0.00, 'CCODIGO':lcCodigo, 'CFLAG':''}
           self.laDatos.append(laFila)
           #print (self.laDatos)
       return True
           
   def mxDepreciacionCalcular(self):
       laDatos = self.laDatos
       self.laDatos = []
       i = -1
      #  print (laDatos)
       for laFila in laDatos:
           i += 1
           lnFactor = laFila['NFACTOR'] * laFila['NNUMPER'] / 12
           lnDeprec = round(laFila['NMONCAL'] * lnFactor / 100, 2)
         #   print(lnFactor, laFila['NMONCAL'], lnDeprec, laFila['NDEPACU'])
         #   print (round(lnDeprec + laFila['NDEPREC'], 2), laFila['NMONCAL'])
           if lnDeprec < laFila['NDEPACU']:
              print('ERR04;', laFila['CCODIGO'] + '/' + laFila['CACTFIJ'], ';', lnDeprec, ';', laFila['NDEPACU'], ';', laFila['NFACTOR'], ';', laFila['NMONCAL'])
              laFila['CFLAG'] = 'ERR04'
              self.mxGrabarError(laFila)
              continue
           elif round(lnDeprec + laFila['NDEPREC'], 2) > laFila['NMONCAL']:
              lnDeprec = laFila['NMONCAL'] - laFila['NDEPREC']
           lnDepMes = round(lnDeprec - laFila['NDEPACU'], 2)
           if lnDepMes <= 0.00:
            #   print("funcion1")
            #   print(lnDeprec, laFila['NDEPACU'])
              continue
         #   print("444")
         #   print (lnDeprec, laFila['NDEPACU'])
           laFila['NFACTOR'] = lnFactor
           laFila['NDEPMES'] = round(lnDeprec - laFila['NDEPACU'], 2)
         #   print("***")
         #   print(laFila['NDEPMES'])
           self.laDatos.append(laFila)
      #  print("---")
      #  print(self.laDatos)
       return True
              
   def mxGrabarDepreciacion(self):
      #  print("hola")
      #  print(self.laDatos)
       for laFila in self.laDatos:
         #   print(self.laDatos) 
           if laFila['CFLAG'] != '':
              continue
           lcSql = "INSERT INTO E04DDEP (cIdDepr, cActFij, nFactor, nMonto, nDeprec, cUsuCod) VALUES ('%s', '%s', %s, %s, %s, '%s')"%\
                      (self.paData['CIDDEPR'], laFila['CACTFIJ'], laFila['NFACTOR'], laFila['NMONCAL'], laFila['NDEPMES'], self.paData['CUSUCOD'])
           #  print(lcSql)
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'NO SE PUDO INSERTAR DETALLE DE DEPRECIACION'
              return False
       return True

   def mxFechaBaja(self, p_mDatos):
       laData = json.loads(p_mDatos)
       if 'DFECBAJ' in laData:
          return laData['DFECBAJ']
       return ''
   
   # -------------------------------------------------------------------------
   # Suma y graba la depreciacion acumulada total y del periodo, actualiza
   # el monto base de calculo de la depreciacion 
   # 2022-06-06 FPM Creacion
   # -------------------------------------------------------------------------
   def omDepreciacionAcumulada(self):
       print('Depreciacion acumulada ...')
       lnTime1 = time.time()
       llOk = self.mxValParamContabilizarDepreciacion()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxDepreciacionAcumulada()
       self.loSql.omDisconnect()
       print(time.time() - lnTime1)
       return llOk

   def mxDepreciacionAcumulada(self):
       # Id de saldos iniciales de depreciacion
       lcPeriod = self.paData['CPERIOD'][0:4] + '00'
       # print(lcPeriod)
       lcSql = "SELECT cIdDepr FROM E04MDEP WHERE cPeriod = '%s'"%(lcPeriod)
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) != 1:
          self.pcError = 'SALDOS INICIALES DE DEPRECIACIÓN NO ENCONTRADA'
          return False
       self.paData['CIDDEPR'] = R1[0][0]
       lcPeriod = self.paData['CPERIOD'][0:4] + '00'
       lcSql = "SELECT cIdDepr FROM E04MDEP WHERE cPeriod = '%s'"%(lcPeriod)
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) != 1:
          self.pcError = 'SALDOS INICIALES DE DEPRECIACIÓN NO ENCONTRADA'
          return False
       self.paData['CDEPINI'] = R1[0][0]

       # Carga AFs por tipo
       lcSql = "SELECT cTipAfj FROM E04TTIP WHERE nFacDep > 0.0000 ORDER BY cTipAfj"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
         #   print(r[0])
           lcSql = """SELECT DISTINCT A.cActFij FROM E04DDEP A
                      INNER JOIN E04MAFJ B ON B.cActFij = A.cActFij
                      INNER JOIN E04MDEP C ON C.cIdDepr = A.cIdDepr
                      WHERE SUBSTRING(C.cPeriod, 1, 4) = '%s' AND B.cTipAfj = '%s'"""%(self.paData['CPERIOD'][0:4], r[0])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               llOk = self.mxGrabarDepreciacionAcumulada(r2[0])
               if llOk:
                  self.loSql.omCommit()
               else:
                  return False
       return True
           
   def mxGrabarDepreciacionAcumulada(self, p_cActFij):
       laData = {'NDEPANU':0.00, 'NDEPREC':0.00, 'NMONCAL':0.00}
       lcPerIni = self.paData['CPERIOD'][0:4] + '01'
       lcPerFin = self.paData['CPERIOD'].replace('-', '')
       lcSql = """SELECT SUM(A.nDeprec) FROM E04DDEP A INNER JOIN E04MDEP B ON B.cIdDepr = A.cIdDepr WHERE A.cActFij = '%s' AND
                  B.cPeriod BETWEEN '%s' AND '%s'"""%(p_cActFij, lcPerIni, lcPerFin)
       if p_cActFij == '00LTT':
         print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       if R1[0][0] != None:
          laData['NDEPANU'] = round(float(R1[0][0]), 2)
       lcSql = "SELECT nMonto, nDeprec FROM E04DDEP WHERE cActFij = '%s' AND cIdDepr = '%s'"%(p_cActFij, self.paData['CIDDEPR'])
       #print(lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       if R1 and len(R1) == 1 and R1[0][0] != None:
          laData['NMONCAL'] = round(float(R1[0][0]), 2)
          laData['NDEPREC'] = round(float(R1[0][1]), 2)
       lcSql = "UPDATE E04MAFJ SET nMonCal = %s, nDepAcu = %s, nDeprec = %s WHERE cActFij = '%s'"%(laData['NMONCAL'], laData['NDEPANU'], laData['NDEPREC'], p_cActFij)
       # print(lcSql)
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO ACTUALIZAR LA DEPRECIACIÓN ANUAL Y LA ACUMULADA DEL ACTIVO FIJO [%s]'%(p_cActFij)
          return False
       if laData['NMONCAL'] == 0.00:
          lcSql = "UPDATE E04MAFJ SET nMonCal = nMontMN WHERE cActFij = '%s'"%(p_cActFij)
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'NO SE PUDO ACTUALIZAR MONTO BASE DE CALCULO DE DEPRECIACION [%s]'%(p_cActFij)
       return llOk

   # -------------------------------------------------------------------------
   # Contabiliza la depreciacion de Activos Fijos
   # 2021-09-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omContabilizarDepreciacion(self):
       print('Contabilizando depreciacion ...')
       lnTime1 = time.time()
       llOk = self.mxValParamContabilizarDepreciacion()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCalcularSaldos()
       if not llOk:
          self.loSql.omDisconnect();
          return False
       llOk = self.mxValidarAsientoContable()
       if not llOk:
          self.loSql.omDisconnect();
          return False
       llOk = self.mxGrabarAsientoContable()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       llOk = self.mxArchivoTrabajo()
       print(time.time() - lnTime1)
       return llOk

   def mxValParamContabilizarDepreciacion(self):
       #print(self.paData)
       loDate = CDate()
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9A-Z]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CPERIOD' in self.paData or not re.match('^202[2-9]{1}-[0-9]{2}$', self.paData['CPERIOD']):
          self.pcError = 'PERIODO DE CÁLCULO DE DEPRECIACIÓN NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'DFECHA' in self.paData or not loDate.mxValDate(self.paData['DFECHA']):
          self.pcError = 'FECHA DE ASIENTO CONTABLE NO DEFINIDA O INVÁLIDA'
          return False
       self.paData['CPERIOD'] = self.paData['CPERIOD'].replace('-', '')
       return True

   def mxCalcularSaldos(self):
       # Carga tipos de activo fijo
       lcSql = "SELECT cTipAfj, TRIM(cCntDep), TRIM(cCntCtr) FROM E04TTIP WHERE nFacDep > 0.0000 and cEstado = 'A' ORDER BY cTipAfj"
       #lcSql = "SELECT cTipAfj, TRIM(cCntDep), TRIM(cCntCtr) FROM E04TTIP WHERE nFacDep > 0.0000 AND cTipAfj = '02020' ORDER BY cTipAfj"
       # print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           print(r[0])
           laFila = {'CTIPAFJ':r[0], 'CCNTDEP':r[1], 'CCNTCTR':r[2]}
           llOk = self.mxDepreciacionAnual(laFila)
           if not llOk:
              return False
       for laTmp in self.laDatos:
           print(laTmp['CCNTDEP'], ';', laTmp['NDEPACU'], ';' , laTmp['NDEPANT'] )
       # sys.exit()
       llOk = self.mxAsientoContableDepreciacion()
       if not llOk:
          return False
       llOk = self.mxAsientoContableDepreciacion()
       return llOk
                 
   # Halla la depreciacion acumulada y la anterior del año actual
   def mxDepreciacionAnual(self, p_aFila):
       lnDepAcu = 0.00
       lnDepAnt = 0.00
       lcSql = """SELECT B.cPeriod, SUM(A.nDeprec) FROM E04DDEP A
                  INNER JOIN E04MDEP B ON B.cIdDepr = A.cIdDepr
                  INNER JOIN E04MAFJ C ON C.cActFij = A.cActFij
                  INNER JOIN E04TTIP D ON D.cTipAfj = C.cTipAfj
                  WHERE D.cTipAfj = '%s' AND SUBSTRING(B.cPeriod, 1, 4) = '%s' AND SUBSTRING(B.cPeriod, 5, 2) != '00' and D.cestado = 'A'
                  GROUP BY B.cPeriod"""%(p_aFila['CTIPAFJ'], self.paData['CPERIOD'][0:4])
      #  print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lnDeprec = 0 if r[1] == None else float(r[1])
           lnDepAcu += lnDeprec
           if r[0] < self.paData['CPERIOD']:
              lnDepAnt += lnDeprec
       # if p_aFila['CCNTDEP'] == '3952601':
       self.laDatos.append({'CCNTDEP':p_aFila['CCNTDEP'], 'CCNTCTR':p_aFila['CCNTCTR'], 'NDEPACU':lnDepAcu, 'NDEPANT':lnDepAnt})
       return True

   # Construye el asiento contable de la depreciacion
   def mxAsientoContableDepreciacion(self):
       self.laData = {'DATOS':'', 'DATOS1':''}
       laDatos = []
       laDatos1 = []
       for laFila in self.laDatos:
           lnDebMN = lnHabMN = 0.00
           if laFila['NDEPACU'] == laFila['NDEPANT']:
              continue
           elif laFila['NDEPACU'] > laFila['NDEPANT']:
              lnHabMN = round(laFila['NDEPACU'] - laFila['NDEPANT'], 2)
           else:
              lnDebMN = round(laFila['NDEPACU'] - laFila['NDEPANT'], 2)
           laTmp = {'CCTACNT':laFila['CCNTDEP'], 'CDESCNT':'', 'NDEPACU':laFila['NDEPACU'], 'NDEPANT':laFila['NDEPANT'], 'NDEBMN':lnDebMN, 'NHABMN':lnHabMN}
           laDatos.append(laTmp)
           laTmp = {'CCTACNT':laFila['CCNTDEP'], 'NDEBMN':lnDebMN, 'NHABMN':lnHabMN, 'CFLAG':''}
           laDatos1.append(laTmp)
           laTmp = {'CCTACNT':laFila['CCNTCTR'], 'NDEBMN':lnHabMN, 'NHABMN':lnDebMN, 'CFLAG':''}
           laDatos1.append(laTmp)
       self.laData = {'DATOS':laDatos, 'DATOS1':laDatos1}
       return True
       
   def mxValidarAsientoContable(self):
       # Totaliza asiento contable
       laDatos1 = []
       for laFila in self.laData['DATOS1']:
           llFlag = True
           i = -1
           for laTmp in laDatos1:
               i += 1
               #print(laTmp, laFila)
               if laTmp['CCTACNT'] == laFila['CCTACNT']:
                  laDatos1[i]['NDEBMN'] += laFila['NDEBMN']
                  laDatos1[i]['NHABMN'] += laFila['NHABMN']
                  llFlag = False
                  break
           if llFlag:
              laDatos1.append(laFila)
       # Netea debe y haber
       laDatos = []
       i = -1
       for laFila in laDatos1:
           i += 1
           if laFila['NDEBMN'] == laFila['NHABMN']:
              continue
           elif laFila['NDEBMN'] > laFila['NHABMN']:
              laFila['NDEBMN'] = laFila['NDEBMN'] - laFila['NHABMN']
              laFila['NHABMN'] = 0.00
              laFila['CFLAG'] = '0' + laFila['CCTACNT']
           else:
              laFila['NHABMN'] = laFila['NHABMN'] - laFila['NDEBMN']
              laFila['NDEBMN'] = 0.00
              laFila['CFLAG'] = '1' + laFila['CCTACNT']
           laDatos.append(laFila)
       # Ordena de acuerdo a criterio contable
       laDatos.sort(key=lambda clave:clave.get('CFLAG'))
       i = -1
       for laTmp in laDatos:
           i += 1
           lcCtaCnt = laTmp['CCTACNT'] + '%'
         #   print (lcCtaCnt)
           lcSql = "SELECT COUNT(*) FROM (SELECT DISTINCT codcta FROM D10MCTA WHERE codcta LIKE '%s') Z"%(lcCtaCnt)
         #   print (lcSql)
           R2 = self.loSql.omExecRS(lcSql)
           if R2[0][0] > 1:
            #   self.laDatos[i]['COBSERV'] = 'ERR01'
              self.pcError = 'CUENTA CONTABLE [%s] NO ES DE ÚLTIMO NIVEL'%(laTmp['CCTACNT'])
              return False
       self.laData['DATOS1'] = laDatos
      #  print (self.laData['DATOS1'])
       return True

   # Graba el asiento contable
   def mxGrabarAsientoContable(self):
       lcPeriod = self.paData['CPERIOD'].replace('-', '')
       lcNroAsi = '000000001'
       lcGlosa  = 'DEPRECIACION DEL PERIODO ' + self.paData['CPERIOD']
       lcSql = "SELECT MAX(cNroAsi) FROM D01MASI"
       R1 = self.loSql.omExecRS(lcSql)
       if R1 and R1[0][0]:
          lcNroAsi = fxCorrelativo(R1[0][0])
       lcSql = """INSERT INTO D01MASI (cNroAsi, cPeriod, cLibro, cEstado, dFecCnt, cGlosa, cOrigen, cRefere, dFecDoc, cNroRuc,
                  cCodAnt, cCodUsu, cUsuCod) VALUES ('%s', '%s', 'DI', 'A', '%s', '%s', '00R', '000000', '%s', '00000000000',
                  '', '%s', '%s')"""%(lcNroAsi, lcPeriod, self.paData['DFECHA'], lcGlosa, self.paData['DFECHA'],\
                  self.paData['CUSUCOD'], self.paData['CUSUCOD'])
       #print(lcSql)
      #  sys.exit()
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO INSERTAR CABECERA DE CONTABILIZACIÓN'
          return False
       for laTmp in self.laData['DATOS1']:
           lcSql = """INSERT INTO D01DASI (cNroAsi, cCtaCnt, cMoneda, nDebME, nHabME, nDebMN, nHabMN, nTipCam, cIndOpe, 
                      cTipCom, cDocume, cNroRuc, dFecDoc, cGlosa, cCenCos, cUsuCod) VALUES ('%s', '%s', '1', 0.00, 0.00, %s, %s,
                      0.00, '', '', '', '00000000000', '1900-01-01', '', '000', '%s')"""%(lcNroAsi, laTmp['CCTACNT'], laTmp['NDEBMN'], laTmp['NHABMN'], \
                      self.paData['CUSUCOD'])
           # print(lcSql)
           #sys.exit()
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'NO SE PUDO INSERTAR DETALLE DE CONTABILIZACIÓN'
              return False
       return True

   def mxArchivoTrabajo(self):
       # Totaliza datos de hoja de trabajo
       laDatos = []
       for laFila in self.laData['DATOS']:
           llFlag = True
           i = -1
           for laTmp in laDatos:
               i += 1
               if laTmp['CCTACNT'] == laFila['CCTACNT']:
                  laDatos[i]['NDEPACU'] += laFila['NDEPACU']
                  laDatos[i]['NDEPANT'] += laFila['NDEPANT']
                  laDatos[i]['NDEBMN']  += laFila['NDEBMN']
                  laDatos[i]['NHABMN']  += laFila['NHABMN']
                  llFlag = False
                  break
           if llFlag:
              laDatos.append(laFila)
       lcFile = 'R' + str(random.random()).replace('0.', '') + '.csv'
       loFile = open(lcFile, 'w')
       lcLinea = 'CUENTA;DEPRECIACION;SALDO CONTABLE;DEBE;HABER;OBSERVACIONES\n'
       loFile.write(lcLinea)
       for laTmp in laDatos:
           #print(laTmp)
           lcLinea = laTmp['CCTACNT'] + ';' + str(laTmp['CDESCNT']) + ';' + str(laTmp['NDEPACU']) + ';' + str(laTmp['NDEPANT']) + ';' + str(laTmp['NDEBMN']) + ';' + str(laTmp['NHABMN']) + '\n'
           loFile.write(lcLinea)
       loFile.close()
       self.paData = {'CFILE':lcFile}
       return True

   # -------------------------------------------------------------------------
   # Consultar AF
   # 2022-04-16 FPM Creacion
   # -------------------------------------------------------------------------
   def omConsultarAF(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxConsultarAF()
       self.loSql.omDisconnect();
       return llOk;

   def mxConsultarAF(self):
       lcSql = "SELECT cActFij, dFecAlt, nMonto, cEstado, cSituac, cCodOl, cDescri FROM E04MAFJ WHERE cActFij = '%s'"%(self.paData['CACTFIJ'])
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) == 0:
          self.pcError = 'NO HAY AF'
          return False
       print(R1[0][0], R1[0][1], R1[0][2], R1[0][3], R1[0][4], R1[0][5], R1[0][6])
       lcSql = """SELECT B.cPeriod, B.cTipo, A.cIdDepr, A.nMonto FROM E04DDEP A INNER JOIN E04MDEP B ON B.cIdDepr = A.cIdDepr 
                  WHERE A.cActFij = '%s' ORDER BY B.cPeriod"""%(self.paData['CACTFIJ'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           print(r[0], r[1], r[2], r[3])
       lcSql = "SELECT * FROM X04DKAR WHERE cActFij = '%s'"%(lcCodOld)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           print(r[0], r[1], r[2], r[3])
       return True

   # ------------------------------------------------------------------------
   # Dar de de baja AF de un centro de responsabilidad
   # 2022-07-13 FPM Creacion
   # ------------------------------------------------------------------------
   def omDarBaja(self):
       llOk = self.mxValParamDarBaja()
       if not llOk:
         return False
       self.loSql = CSql()
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCargarActivos()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxContabilizarBajas()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxArchivosConsulta()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       if self.laData['OK'] == 'N':
          self.loSql.omDisconnect()
          del self.laData['DATOS1']  
          del self.laData['DATOS2']  
          self.paData = self.laData
          return True   
       llOk = self.mxGrabarBajas()
       if llOk:
         #  print('COMMIT')
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       del self.laData['DATOS1']  
       del self.laData['DATOS2']  
       self.paData = self.laData
       return llOk

   def mxValParamDarBaja(self):
       loDate = CDate()
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9A-Z]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CCENRES' in self.paData or not re.match('^[0-9A-Z]{5}$', self.paData['CCENRES']):
          self.pcError = 'CENTRO DE RESPONSABILIDAD NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CDOCBAJ' in self.paData or len(self.paData['CDOCBAJ']) < 5 or len(self.paData['CDOCBAJ']) > 80:
          self.pcError = 'DOCUMENTO DE BAJA NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'DFECBAJ' in self.paData or not loDate.mxValDate(self.paData['DFECBAJ']):
          self.pcError = 'FECHA DE BAJA NO DEFINIDA O INVÁLIDA'
          return False
       return True

   def mxCargarActivos(self):
       lcSql = f"SELECT cEstado FROM S01TRES WHERE cCenRes = '{self.paData['CCENRES']}'"
      #  print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) == 0:
          self.pcError = "CENTRO DE RESPONSABILIDAD NO EXISTE"
          return False
       elif R1[0][0] != 'A':
          self.pcError = "CENTRO DE RESPONSABILIDAD NO ESTÁ ACTIVO"
          return False
       
       ldAnio = self.paData['DFECBAJ'].replace("-","")
       ldAnioIni = ldAnio[:4]+"00"
       ldAnioFin = ldAnio[:6]
       lcSql = f"""SELECT C.cActFij, C.mDatos, C.cDescri, D.cCntAct, D.cCntDep, D.cCntBaj, C.nMonCal, sum(B.nDeprec) FROM E04MDEP A INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij INNER JOIN E04TTIP D on D.cTipAfj = C.cTipAfj
                  where A.cperiod >= '%s' and A.cperiod <= '%s'
                        AND C.cCenRes = '%s'  
                  GROUP BY C.cActFij, c.cTipAfj, D.cCntAct, D.cCntDep, D.cCntBaj
                  ORDER BY C.cTipAfj, C.nCorrel"""%(ldAnioIni, ldAnioFin, self.paData['CCENRES'])
       # print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = json.loads(r[1])
           laTmp['DFECBAJ'] = self.paData['DFECBAJ']
           laTmp['CDOCBAJ'] = self.paData['CDOCBAJ']
           lmDatos = json.dumps(laTmp)
           self.laDatos.append({'CACTFIJ': r[0], 'MDATOS': lmDatos, 'CDESCRI': r[2], 'CCNTACT': r[3].strip(), 'CCNTDEP': r[4].strip(),\
                                'CCNTBAJ': r[5].strip(), 'NMONACT': float(r[6]), 'NDEPACU': float(r[7])})
       if len(self.laDatos) == 0:
          self.pcError = f"NO HAY ACTIVOS FIJOS EN CENTRO DE RESPONSABILIDAD [{self.laData['CCENRES']}]"
          return False
       return True

   def mxContabilizarBajas(self):
       laDatos = []
       # Obtiene el valor del activo y la depreciacion acumulada
       for laFila in self.laDatos:
           # Valor del AF
           llFlag = False
           i = 0
           for laTmp in laDatos:
               if laFila['CCNTACT'] == laTmp['CCTACNT']:
                  llFlag = True
                  break
               i += 1
           if llFlag:
              laDatos[i]['NHABER'] += laFila['NMONACT']
           else:
              laDatos.append({'CCTACNT': laFila['CCNTACT'], 'CDESCRI': '', 'NDEBE': 0.00, 'NHABER': laFila['NMONACT']})
           # Depreciacion acumulada
           llFlag = False
           i = 0
           for laTmp in laDatos:
               if laFila['CCNTDEP'] == laTmp['CCTACNT']:
                  llFlag = True
                  break
               i += 1
           if llFlag:
              laDatos[i]['NDEBE'] += laFila['NDEPACU']
           else:
              laDatos.append({'CCTACNT': laFila['CCNTDEP'], 'CDESCRI': '', 'NDEBE': laFila['NDEPACU'], 'NHABER': 0.00})
           # Depreciacion faltante
           lnDeprec = laFila['NMONACT'] - laFila['NDEPACU']
           if lnDeprec == 0:
              continue
           llFlag = False
           i = 0
           for laTmp in laDatos:
               if laFila['CCNTBAJ'] == laTmp['CCTACNT']:
                  llFlag = True
                  break
               i += 1
           if llFlag:
              laDatos[i]['NDEBE'] += lnDeprec
           else:
              laDatos.append({'CCTACNT': laFila['CCNTBAJ'], 'CDESCRI': '', 'NDEBE': lnDeprec, 'NHABER': 0.00})
       # Descripcion de cuenta contable
       llOk = True
       i = 0
       for laFila in laDatos:
           j = 0
           lcCtaCnt = laFila['CCTACNT'].strip() + '%'
           lcSql = f"SELECT cDescri FROM D01MCTA WHERE cCtaCnt LIKE '{lcCtaCnt}'"
           R1 = self.loSql.omExecRS(lcSql)
           for r in R1:
               lcDescri = r[0]
               j += 1
           if j == 0:
              llOk = False
              lcDescri = '*** CUENTA CONTABLE NO EXISTE ***'
           elif j > 1:
              llOk = False
              lcDescri = '*** CUENTA CONTABLE NO ES DE ÚLTIMO NIVEL ***'
           laDatos[i]['CDESCRI'] = lcDescri
           i += 1
       # Variable de retorno
       lcOk = 'S' if llOk else 'N'
       self.laData = {'OK': lcOk, 'CFILE1': '', 'CFILE2': '', 'DATOS1': self.laDatos, 'DATOS2': laDatos}
       return True

   def mxArchivosConsulta(self):
       # Archivo de consulta de AF
       lcFile = './FILES/R' + str(random.random()).replace('0.', '') + '.csv'
       try:
          with open(lcFile, 'w') as loFile:
             i = 0
             for laFila in self.laData['DATOS1']:
                 i += 1
                 lcLinea = str(i) + ';' + laFila['CACTFIJ'] + ';' + laFila['CDESCRI'] + ';' + str(laFila['NMONACT']) + ';' + str(laFila['NDEPACU']) + ';' + str(laFila['NMONACT'] - laFila['NDEPACU']) + '\n'
               #   print(lcLinea)
                 loFile.write(lcLinea)
             loFile.close()
       except IOError:
          self.pcError = 'ARCHIVO CSV DE ACTIVOS FIJOS NO SE PUDO GENERAR'
          return False   
       self.laData['CFILE1'] = lcFile
       # Archivo de consulta de contabilizacion
       lcFile = './FILES/R' + str(random.random()).replace('0.', '') + '.csv'
       try:
          with open(lcFile, 'w') as loFile:
             i = 0
             for laFila in self.laData['DATOS2']:
                 i += 1
                 lcLinea = laFila['CCTACNT'] + ';' + laFila['CDESCRI'] + ';' + str(laFila['NDEBE']) + ';' + str(laFila['NHABER']) + '\n'
                 loFile.write(lcLinea)
             loFile.close()
       except IOError:
          self.pcError = 'ARCHIVO CSV DE CONTABILIZACIÓN NO SE PUDO GENERAR'
          return False   
       self.laData['CFILE2'] = lcFile
       return True

   def mxGrabarBajas(self):
       # Cambia el estado del centro de responsabilidad
       lcSql = f"""UPDATE S01TRES SET cEstado = 'I', cUsuCod = '{self.paData['CUSUCOD']}', tModifi = NOW()
                   WHERE cCenRes = '{self.paData['CCENRES']}'"""
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO INACTIVAR CENTRO DE RESPONSABILIDAD'
          return False
       for laTmp in self.laData['DATOS1']:
           lcSql = f"""UPDATE E04MAFJ SET cEstado = 'X', cSituac = 'B', mDatos = '{laTmp['MDATOS']}', cUsuCod = '{self.paData['CUSUCOD']}', 
                       tModifi = NOW() WHERE cActFij = '{laTmp['CACTFIJ']}'"""
         #   print(lcSql)
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = f"NO SE PUDO ACTUALIZAR ACTIVO [{laTmp['CACTFIJ']}]"
              return False
       # Graba asiento contable
       lcGlosa = f"BAJA DE ACTIVO FIJO - CENTRO DE RESPONSABILIDAD [{self.paData['CCENRES']}]"
       lcSql = "SELECT MAX(cNroAsi) FROM D01MASI"
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) == 0:
          lcNroAsi = '000000000'
       else:
          lcNroAsi = R1[0][0]
       lcNroAsi = fxCorrelativo(lcNroAsi)      
       lcSql = f"""INSERT INTO D01MASI (cNroAsi, cPeriod, cLibro , dFecCnt, cGlosa , dFecDoc, cCodAnt, cCodUsu, cUsuCod) VALUES
                   ('{lcNroAsi}', '', 'DI', '{self.paData['DFECBAJ']}', '{lcGlosa}', '{self.paData['DFECBAJ']}', '*', 
                    '{self.paData['CUSUCOD']}', '{self.paData['CUSUCOD']}')"""
      #  print(lcSql)
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = "NO SE PUDO INSERTAR CABECERA DE ASIENTO CONTABLE"
          return False
       for laTmp in self.laData['DATOS2']:
           lcSql = f"""INSERT INTO D01DASI (cNroAsi, cCtaCnt, cMoneda, nDebME , nHabME , nDebMN , nHabMN , nTipCam, cIndOpe, cTipCom, cDocume, cNroRuc, dFecDoc, cGlosa, cUsuCod) VALUES
                       ('{lcNroAsi}', '{laTmp['CCTACNT']}', '1', 0.00, 0.00, {laTmp['NDEBE']}, {laTmp['NHABER']}, 0.00, '', '', '', '00000000000', NOW(), '', '{self.paData['CUSUCOD']}')""" 
         #   print(lcSql)
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = "NO SE PUDO INSERTAR DETALLE DE ASIENTO CONTABLE"
              return False
       return True

   # ------------------------------------------------------------------------
   # Dar de de baja a un activo fijo  
   # 2022-011-15 GCH Creacion
   # ------------------------------------------------------------------------
   def omDarBajaActFij(self):
       llOk = self.mxValParamDarBajaActFij()
       if not llOk:
         return False
       self.loSql = CSql()
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCargarActivoFijo_Baja()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxContabilizarBajas()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxArchivosConsulta()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       if self.laData['OK'] == 'N':
          self.loSql.omDisconnect()
          del self.laData['DATOS1']  
          del self.laData['DATOS2']  
          self.paData = self.laData
          return True   
       llOk = self.mxGrabarBajaActFij()
       if llOk:
         #  print('COMMIT')
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       del self.laData['DATOS1']  
       del self.laData['DATOS2']  
       self.paData = self.laData
       return llOk

   def mxValParamDarBajaActFij(self):
       loDate = CDate()
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9A-Z]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CACTFIJ' in self.paData or not re.match('^[0-9A-Z]{5}$', self.paData['CACTFIJ']):
          self.pcError = 'ACTIVO FIJO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CDOCBAJ' in self.paData or len(self.paData['CDOCBAJ']) < 5 or len(self.paData['CDOCBAJ']) > 80:
          self.pcError = 'DOCUMENTO DE BAJA NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'DFECBAJ' in self.paData or not loDate.mxValDate(self.paData['DFECBAJ']):
          self.pcError = 'FECHA DE BAJA NO DEFINIDA O INVÁLIDA'
          return False
       return True

   def mxCargarActivoFijo_Baja(self):
       ldAnio = self.paData['DFECBAJ'].replace("-","")
       ldAnioIni = ldAnio[:4]+"00"
       ldAnioFin = ldAnio[:6]
       lcSql = """SELECT C.cActFij, C.mDatos, C.cDescri, D.cCntAct, D.cCntDep, D.cCntBaj, C.nMonCal, sum(B.nDeprec) 
                  FROM E04MDEP A 
                  INNER JOIN E04DDEP B ON B.cIdDepr = A.cIdDepr 
                  INNER JOIN E04MAFJ C ON C.cActFij = B.cActFij INNER JOIN E04TTIP D on D.cTipAfj = C.cTipAfj
                  where A.cperiod >= '%s' and A.cperiod <= '%s'
                        AND C.cActFij = '%s'  
                  GROUP BY C.cActFij, c.cTipAfj, D.cCntAct, D.cCntDep, D.cCntBaj
                  ORDER BY C.cTipAfj, C.nCorrel"""%(ldAnioIni, ldAnioFin, self.paData['CACTFIJ'])
       #print (lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = json.loads(r[1])
           laTmp['DFECBAJ'] = self.paData['DFECBAJ']
           laTmp['CDOCBAJ'] = self.paData['CDOCBAJ']
           lmDatos = json.dumps(laTmp)
           self.laDatos.append({'CACTFIJ': r[0], 'MDATOS': lmDatos, 'CDESCRI': r[2], 'CCNTACT': r[3].strip(), 'CCNTDEP': r[4].strip(),\
                                'CCNTBAJ': r[5].strip(), 'NMONACT': float(r[6]), 'NDEPACU': float(r[7])})
       #print(self.laDatos)
       if len(self.laDatos) == 0:
          self.pcError = f"NO HAY ACTIVOS FIJOS EN CENTRO DE RESPONSABILIDAD [{self.laData['CCENRES']}]"
          return False
       return True

   def mxGrabarBajaActFij(self):
       # Actualizar Activo Fijo
       lcSql = f"""UPDATE E04MAFJ SET cEstado = 'X', cSituac = 'B', mDatos = '{self.laDatos[0]['MDATOS']}', cUsuCod = '{self.paData['CUSUCOD']}', 
                       tModifi = NOW() WHERE cActFij = '{self.laDatos[0]['CACTFIJ']}'"""
       # print(lcSql)
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = f"NO SE PUDO ACTUALIZAR ACTIVO [{self.paDatos[0]['CACTFIJ']}]"
          return False
       # Graba asiento contable
       lcGlosa = f"BAJA DE ACTIVO FIJO  [{self.laDatos[0]['CACTFIJ']}]"
       lcSql = "SELECT MAX(cNroAsi) FROM D01MASI"
       R1 = self.loSql.omExecRS(lcSql)
       if not R1 or len(R1) == 0:
          lcNroAsi = '000000000'
       else:
          lcNroAsi = R1[0][0]
       lcNroAsi = fxCorrelativo(lcNroAsi)      
       lcSql = f"""INSERT INTO D01MASI (cNroAsi, cPeriod, cLibro , dFecCnt, cGlosa , dFecDoc, cCodAnt, cCodUsu, cUsuCod) VALUES
                   ('{lcNroAsi}', '', 'DI', '{self.paData['DFECBAJ']}', '{lcGlosa}', '{self.paData['DFECBAJ']}', '*', 
                    '{self.paData['CUSUCOD']}', '{self.paData['CUSUCOD']}')"""
      #  print(lcSql)
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = "NO SE PUDO INSERTAR CABECERA DE ASIENTO CONTABLE"
          return False
       for laTmp in self.laData['DATOS2']:
           lcSql = f"""INSERT INTO D01DASI (cNroAsi, cCtaCnt, cMoneda, nDebME , nHabME , nDebMN , nHabMN , nTipCam, cIndOpe, cTipCom, cDocume, cNroRuc, dFecDoc, cGlosa, cUsuCod) VALUES
                       ('{lcNroAsi}', '{laTmp['CCTACNT']}', '1', 0.00, 0.00, {laTmp['NDEBE']}, {laTmp['NHABER']}, 0.00, '', '', '', '00000000000', NOW(), '', '{self.paData['CUSUCOD']}')""" 
         #   print(lcSql)
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = "NO SE PUDO INSERTAR DETALLE DE ASIENTO CONTABLE"
              return False
       return True

   # ----------------------------------------------------------------
   # Calcular y registrar inicio de depreciacion anual
   # 2023-01-17 FPM Creacion
   # ----------------------------------------------------------------
   def omInicioDepreciacionAnual(self):
       llOk = self.mxValParamInicioDepreciacionAnual()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerInicioDepreciacionAnual()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxInicioDepreciacionAnual()
       self.loSql.omDisconnect()
       return llOk
       
   def mxValParamInicioDepreciacionAnual(self):
       if not 'CPERIOD' in self.paData or not re.match('^20[2-9]{2}$', self.paData['CPERIOD']):
          self.pcError = 'PERIODO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CUSUCOD' in self.paData or not re.match('^[0-9A-Z]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CFLAG' in self.paData:
          self.paData['CFLAG'] = 'N'
       if not re.match('[SN]{1}$', self.paData['CFLAG']):
          self.pcError = 'FLAG NO DEFINIDO O INVÁLIDO'
          return False
       return True
         
   def mxVerInicioDepreciacionAnual(self):
       # Verifica registro de depreciacion inicial anual
       lcPeriod = self.paData['CPERIOD'] + '00'
       lcSql = f"SELECT cIdDepr FROM E04MDEP WHERE cPeriod = '{lcPeriod}'"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          return True
       lcIdDepr = R1[0][0]
       # Verifica si existe depreciaciones mensuales para el periodo indicado
       lcPeriod = self.paData['CPERIOD'] + '01'
       lcSql = f"SELECT cIdDepr FROM E04MDEP WHERE cPeriod = '{lcPeriod}'"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 1:
          self.pcError = f"YA EXISTE DEPRECIACION DE MESES EN EL PERIODO ANUAL [{self.paData['CPERIOD']}]"
          return False
       # Si existe registro de depreciacion inicial anual valida el indicador de reinicio
       if self.paData['CFLAG'] != 'S':
          self.pcError = f"YA EXISTE DEPRECIACION INICIAL DEL PERIODO ANUAL [{self.paData['CPERIOD']}]"
          return False
       # Elimina registro de depreciacion inicial anual
       lcSql = f"DELETE FROM E04DDEP WHERE cIdDepr = '{lcIdDepr}'"
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          print(lcSql)
          self.pcError = 'NO SE PUDO ELIMINAR DETALLE DE DEPRECIACION INICIAL ANUAL'
          return False
       # Reinicia nSerial  
       lcSql = "SELECT SETVAL(PG_GET_SERIAL_SEQUENCE('E04DDEP', 'nserial'), COALESCE(MAX(nSerial), 0) + 1, FALSE) FROM E04DDEP"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          print(lcSql)
          self.pcError = 'NO SE PUDO REINICIAR DETALLE DE DEPRECIACION INICIAL ANUAL'
          return False
       # Elimina cabecera de depreciacion inicial anual  
       lcSql = f"DELETE FROM E04MDEP WHERE cIdDepr = '{lcIdDepr}'"
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          print(lcSql)
          self.pcError = 'NO SE PUDO ELIMINAR DEPRECIACION INICIAL ANUAL'
          return False
       return True

   def mxInicioDepreciacionAnual(self):
       # Halla siguiente correlativo de identificador de depreciacion
       lcPeriod = self.paData['CPERIOD'] + '00'
       lcSql = "SELECT MAX(cIdDepr) FROM E04MDEP"
       R1 = self.loSql.omExecRS(lcSql)
       lcIdDepr = '000' if len(R1) == 0 else R1[0][0]
       lcIdDepr = fxCorrelativo(lcIdDepr)
       lcIdMovi = self.paData['CPERIOD'] + '-01-01'
       # Graba cabecera de depreciacion inicial anual
       lcSql = f"INSERT INTO E04MDEP (cIdDepr, cPeriod, dMovimi, cUsuCod) VALUES ('{lcIdDepr}', '{lcPeriod}', '{lcIdMovi}', '{self.paData['CUSUCOD']}')"
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          print(lcSql)
          self.pcError = 'NO SE PUDO INSERTAR CABECERA DE DEPRECIACION INICIAL ANUAL'
          return False
       # Agrupa los activos fijos para procesarlos
       lcSql = f"""SELECT DISTINCT SUBSTRING(cActFij, 1, 3) FROM E04MAFJ ORDER BY SUBSTRING(cActFij, 1, 3)"""
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           # Carga detalle de los activos fijos por grupo
           self.laDatos = []
           lcActFij = r[0] + '%'
           lcSql = f"SELECT cActFij, nMonCal FROM E04MAFJ WHERE cActFij LIKE '{lcActFij}'"
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               # Halla la depreciacion del periodo (año) inmediato anterior
               laFila = {'CACTFIJ': r2[0], 'NMONCAL': float(r2[1])}
               llOk = self.mxDepreciacionInicio(laFila)
               if not llOk:
                  return False
           # Graba depreciacion inicial anual de los activos fijos
           for laTmp in self.laDatos:
               lcSql = f"""INSERT INTO E04DDEP (cIdDepr, cActFij, nFactor, nMonto, nDeprec, cUsuCod) VALUES ('{lcIdDepr}',
                           '{laTmp['CACTFIJ']}', 0.00, {laTmp['NMONCAL']}, {laTmp['NDEPREC']}, '{self.paData['CUSUCOD']}')"""
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  print(lcSql)
                  self.pcError = 'NO SE PUDO INSERTAR DEPRECIACION INICIAL ANUAL'
                  return False
           self.loSql.omCommit()
       return True  

   def mxDepreciacionInicio(self, p_aFila):
       # Halla la depreciacion del periodo (año) anterior
       lcPerAnt = str(int(self.paData['CPERIOD']) - 1)
       lcSql = f"""SELECT COUNT(*), SUM(A.nDeprec) FROM E04DDEP A INNER JOIN E04MDEP B ON A.cIdDepr = B.cIdDepr
                   WHERE A.cActFij = '{p_aFila['CACTFIJ']}' AND SUBSTRING(B.cPeriod, 1, 4) = '{lcPerAnt}'"""
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          return True
       elif len(R1) == 1 and R1[0][0] == 0:
          return True
       self.laDatos.append({'CACTFIJ': p_aFila['CACTFIJ'], 'NMONCAL': p_aFila['NMONCAL'], 'NDEPREC': float(R1[0][1])})
       return True  


# ---------------------------------------------
# Funcion principal para ser llamado desde php
# ---------------------------------------------
def main(p_cParam):
    laData = json.loads(p_cParam)
   #  print(laData)
    if 'ID' not in laData:
       print('{"ERROR":"NO HAY ID DE PROCESO"}')
       return
    elif laData['ID'] == 'AFJ8001':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omCalcularDepreciacion()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'AFJ8002':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omContabilizarDepreciacion()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'AFJ8003':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omDepreciacionAcumulada()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'AFJ8004':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omDarBaja()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'AFJ8005':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omDarBajaActFij()
       if llOk:
          print(json.dumps(lo.paData))
          return 
    elif laData['ID'] == 'AFJ8006':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omInicioDepreciacionAnual()
       if llOk:
          print(json.dumps(lo.paData))
          return 
    elif laData['ID'] == 'AFJ8009':
       lo = CCntActivoFijo()
       lo.paData = laData
       llOk = lo.omConsultarAF()
       if llOk:
          print(json.dumps(lo.paData))
          return
    else:
       laData = {'ERROR':'ID [%s] NO DEFINIDA'%(laData['ID'])}
       print(json.dumps(laData))
       return
    laData = {'ERROR':lo.pcError}
    print(json.dumps(laData))
    return

if __name__ == "__main__":
   main(sys.argv[1])

'''
100,000 al haber en la cuenta  33 correspondiente 
99,000 al debe en la cuenta 39 correspondiente 
1,000 al debe en la cuenta 65 correspondiente.

python3 CCntActivoFijo.py '{"ID":"AFJ8001", "CPERIOD":"2022-04", "CUSUCOD":"9999", "CFLAG":"S"}'
python3 CCntActivoFijo.py '{"ID":"AFJ8002", "CPERIOD":"2022-04", "CUSUCOD":"9999", "DFECHA":"2022-04-30", "CFLAG":"S"}'
python3 CCntActivoFijo.py '{"ID":"AFJ8004", "CCENRES":"09999", "CUSUCOD":"9999", "CDOCBAJ":"123-456-789", "DFECBAJ":"2022-10-04"}'
python3 CCntActivoFijo.py '{"ID": "AFJ8006", "CPERIOD": "2023", "CUSUCOD": "1221", "CFLAG": "S"}'


'''
