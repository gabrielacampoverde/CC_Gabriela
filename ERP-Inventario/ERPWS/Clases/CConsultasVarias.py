#!/usr/bin/env python
#-*- coding: utf-8 -*-
import sys
import json
import time
import random
import re
from datetime import date
from CBase import *
from CSql1 import *

class CConsultasVarias(CBase):

   def __init__(self):
       self.paData  = []
       self.paDatos = []
       self.laData  = []
       self.laDatos = []
       self.loSql   = CSql()

   # -------------------------------------------------------------------------
   # Verifica estudiantes matriculados que no tienen DNI valido
   # 2021-11-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omConsultarProyectosInvestigacion(self):
       llOk = self.mxValParamConsultarProyectosInvestigacion()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxConsultarProyectosInvestigacion()
       self.loSql.omDisconnect()
       return True

   def mxValParamConsultarProyectosInvestigacion(self):
       if not 'CYEAR' in self.paData or not re.match('^20[0-9]{2}$', self.paData['CYEAR']):
          self.pcError = 'AÑO NO DEFINIDO O INVALIDO'
          return False
       return True

   def mxConsultarProyectosInvestigacion(self):
       laDatos = []
       lcSql = f"""SELECT TRIM(codcta), TRIM(descri) FROM D10MCTA
                   WHERE codcta LIKE '4699102%' AND anopro = '{self.paData['CYEAR']}' AND estado = 'A' ORDER BY codcta"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          laDatos.append({'CCTACNT': laTmp[0], 'CDESCRI': laTmp[1]})
          laTmp = self.loSql.fetch(R1)
       if len(laDatos) == 0:
          self.pcError = 'NO HAY PROYECTOS (CUENTAS CONTABLES) PARA MOSTRAR'
          return False
       self.paDatos = laDatos   
       return True

   # -------------------------------------------------------------------------
   # Verifica estudiantes matriculados que no tienen DNI valido
   # 2021-11-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omDetalleProyectosInvestigacion(self):
       llOk = self.mxValParamDetalleProyectosInvestigacion()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxDetalleProyectosInvestigacion()
       self.loSql.omDisconnect()
       return True

   def mxValParamDetalleProyectosInvestigacion(self):
       if not 'CYEAR' in self.paData or not re.match('^20[0-9]{2}$', self.paData['CYEAR']):
          self.pcError = 'AÑO NO DEFINIDO O INVALIDO'
          return False
       return True

   def mxDetalleProyectosInvestigacion(self):
       laDatos = []
       lcSql = f"""SELECT m.mescom, CONCAT(m.tipcom,'-',m.numcom,'#',d.numsec)::char(15), TO_CHAR(d.fecdoc, 'YYYY-MM-DD'),  d.descri,
                   CONCAT(p.cnroruc, c.cnumide, e.ccodusu), CONCAT(p.crazsoc, c.cdescri, e.cnombre),
                   CONCAT(d.tipdoc,'/',d.numdoc)::char(25), a.habsol AS valing, a.debsol as valegr
                   FROM D10AASI AS a
                   LEFT JOIN D10DASI AS d ON d.idasid = a.idasid
                   LEFT JOIN D10MASI AS m ON m.idasie = d.idasie
                   LEFT JOIN S01MPRV AS p ON p.ccodant = d.codcte AND d.tipcte = 'P' AND p.cestado = 'A'
                   LEFT JOIN S01MCLI AS c ON c.ccodant = d.codcte AND d.tipcte = 'C'
                   LEFT JOIN V_S01TUSU_1 AS e ON e.ccodusu = d.codcte AND d.tipcte = 'E'
                   WHERE a.codcta = '{self.paData['CCTACNT']}'  AND m.anocom = '{self.paData['CYEAR']}'
                   ORDER BY m.anocom, m.mescom, m.tipcom, m.numcom, d.numsec"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          laDatos.append({'CMESPRO': laTmp[0], 'CASIENT': laTmp[1].strip(), 'DDOCUME': laTmp[2], 'CDESCRI': laTmp[3].strip(), 
                          'CDOCIDE': laTmp[4].strip(), 'CIDENTI': laTmp[5].strip().replace('/', ' '), 'CNRODOC': laTmp[6].strip(), 
                          'NINGRES': float(laTmp[7]), 'NEGRESO': float(laTmp[8])})
          laTmp = self.loSql.fetch(R1)
       if len(laDatos) == 0:
          self.pcError = f"NO HAY DETALLE DE CUENTA CONTABLE [{self.paData['CCTACNT']}] PARA AÑO [{self.paData['CYEAR']}]"
          return False
       self.paDatos = laDatos   
       return True

   # -------------------------------------------------------------------------
   # Verifica estudiantes matriculados que no tienen DNI valido
   # 2021-11-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omVerificarMatriculadosDNI(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerificarMatriculadosDNI()
       self.loSql.omDisconnect()
       if not llOk:
          return False
       self.mxPrintVerificarMatriculadosDNI()
       return True

   def mxVerificarMatriculadosDNI(self):
       i = 0
       lcSql = "SELECT cCtaCte, cNroDni FROM C10MCCT WHERE SUBSTRING(cNroDni, 1, 1) NOT IN ('0', '1', '2', '3', '4', '5', '6', '7', '8', '9')"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = f"SELECT cCodAlu FROM C10DCCT WHERE cCtaCte = '{r[0]}' ORDER BY cCodAlu LIMIT 1"
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) == 0:
              #print('ERROR-1: ', r[0])
              continue
           lcCodAlu = R2[0][0]   
           lcSql = f"SELECT cNombre FROM V_A01MALU WHERE cCodAlu = '{lcCodAlu}'"
           R2 = self.loSql.omExecRS(lcSql)
           self.laDatos.append({'CNRODNI': r[1], 'CCODALU': lcCodAlu, 'CNOMBRE': R2[0][0]})   
       return True
       
   def mxPrintVerificarMatriculadosDNI(self):
       for laTmp in self.laDatos:
           print(laTmp['CNRODNI'] + ';' + laTmp['CCODALU'] + ';' + laTmp['CNOMBRE'])
       return True    

   # -------------------------------------------------------------------------
   # Revisa pagos del centro de idiomas
   # 2021-11-23 FPM Creacion
   # -------------------------------------------------------------------------
   def omPagadosCentroIdiomas(self):
       llOk = self.mxCargarCsv()
       if not llOk:
          return False
       llOk = self.loSql.omConnect(2)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxPagadosCentroIdiomas()
       self.loSql.omDisconnect()
       if not llOk:
          return False
       self.mxPrintPagadosCentroIdiomas()
       return True

   def mxCargarCsv(self):
       i = 0
       j = 0
       lcAluCod = '*'
       loFile = open('./Idiomas.csv', 'r')
       for x in loFile:
           i += 1
           laTmp = x.split(';')
           lcCodAlu = laTmp[1].strip()
           if lcAluCod == '*':
              j = 1
              lcAluCod = lcCodAlu
              lcNroDni = laTmp[0].strip()
           elif lcAluCod != lcCodAlu:
              self.laDatos.append([lcNroDni, lcAluCod, j])
              lcAluCod = lcCodAlu
              lcNroDni = laTmp[0].strip()
              j = 1
           else:
              j += 1
           #if i == 300:
           #   break   
       self.laDatos.append([lcNroDni, lcAluCod, j])
       loFile.close()
       return True

   def mxPagadosCentroIdiomas(self):
       laDatos = []
       for laTmp in self.laDatos:
           lcSql = f"SELECT COUNT(*) FROM B01DPAG WHERE cCodAlu = '{laTmp[1]}' AND cEstado IN ('B', 'C') AND cProyec LIKE '202%'"
           R1 = self.loSql.omExecRS(lcSql)
           lnCount = 0 if len(R1) == 0 else R1[0][0]
           lcSql = f"SELECT COUNT(*) FROM B01DPAG WHERE cCodAlu = '{laTmp[1]}' AND cEstado IN ('B', 'C')"
           R1 = self.loSql.omExecRS(lcSql)
           lnCount1 = 0 if len(R1) == 0 else R1[0][0]
           laDatos.append([laTmp[0], laTmp[1], laTmp[2], lnCount, lnCount1])
       self.laDatos = laDatos    
       return True
       
   def mxPrintPagadosCentroIdiomas(self):
       for laTmp in self.laDatos:
           print(laTmp[0] + ';' + laTmp[1] + ';' + str(laTmp[2]) + ';' + str(laTmp[3]) + ';' + str(laTmp[4]))
       return True

   # ----------------------------------------------------------------
   # Actualiza celulares de archivo csv
   # 2022-11-30 FPM Creacion
   # ----------------------------------------------------------------
   def omActualizarCelular(self):
       llOk = self.mxCelularesCsv()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxActualizarCelular()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk
          
   def mxCelularesCsv(self):
       lcAluCod = '*'
       loFile = open('./AlumnosDeudores.csv', 'r')
       for x in loFile:
           laTmp = x.split(';')
           lcCodAlu = laTmp[1].strip()
           if lcCodAlu != lcAluCod:
              lcAluCod = lcCodAlu
              lcNroDni = laTmp[2].strip()
              lcNroCel = laTmp[4].strip()
              if not re.match('^[0-9]{10}$', lcCodAlu):
                 print('ERR1:', lcCodAlu)
                 continue
              elif not re.match('^[E0-9]{8}$', lcNroDni):
                 print('ERR2:', lcNroDni)
                 continue
              if lcNroCel != '':
                 lcNroCel = lcNroCel.replace('+51', '')
                 i = lcNroCel.find('|')
                 #print('1)', lcNroCel, i)
                 if i > 0:
                    lcNroCel = lcNroCel[:i]
                    #print('2)', lcNroCel)
                 lcNroCel = lcNroCel.replace(' ', '')
                 #print('3)', lcNroCel)
                 if not re.match('^9[0-9]{8}$', lcNroCel):
                    print('ERR3:', lcCodAlu, lcNroCel)
                    #sys.exit(1)
                    continue
              self.laDatos.append({'CCODALU': lcCodAlu, 'CNRODNI': lcNroDni, 'CNROCEL': lcNroCel})
       loFile.close()
       return True
          
   def mxActualizarCelular(self):
       for laTmp in self.laDatos:
           lcSql = f"SELECT cNroDni FROM A01MALU WHERE cCodAlu = '{laTmp['CCODALU']}'"
           R1 = self.loSql.omExecRS(lcSql)
           if len(R1) == 0:
              print('ERR11:', f"DNI [{R1[0][0]}] NO EXISTE")
              continue
           elif R1[0][0] != laTmp['CNRODNI']:
              print('ERR12:', f"DNI [{R1[0][0]}] NO CORRESPONDE")
              continue
           lcSql = f"SELECT cNroCel FROM S01MPER WHERE cNroDni = '{R1[0][0]}'"
           R1 = self.loSql.omExecRS(lcSql)
           if len(R1) == 0:
              print('ERR13:', f"DNI [{R1[0][0]}] NO EXISTE")
              continue
           elif R1[0][0] == laTmp['CNROCEL']:
              #print('ERR14:', f"CELULAR ES EL MISMO")
              continue
           lcSql = f"UPDATE S01MPER SET cNroCel = '{laTmp['CNROCEL']}' WHERE cNroDni = '{laTmp['CNRODNI']}'"   
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              print(lcSql)
              print('ERR14:', f"NO SE PUDO ACTUALIZAR")
       return True

   # ----------------------------------------------------------------
   # Verifica usuarios DNI
   # 2022-12-18 FPM Creacion
   # ----------------------------------------------------------------
   def omVerificarUsuariosDNI(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerificarUsuariosDNI()
       self.loSql.omDisconnect()
       llOk = self.mxPrintUsuariosDNI()
       return llOk
          
   def mxVerificarUsuariosDNI(self):
       # Usuarios con diferente codigo
       lcSql = "SELECT DISTINCT SUBSTRING(cCodUsu, 1, 2) FROM S01TUSU ORDER BY SUBSTRING(cCodUsu, 1, 2)"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcCodUsu = r[0] + '%'
           lcSql = f"SELECT cCodUsu, cNroDni, TRIM(cUsuInf), cEstado FROM S01TUSU WHERE cCodUsu LIKE '{lcCodUsu}' ORDER BY cCodUsu"
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               if not re.match('^[0-9]{8}$', r2[1]):
                  self.laDatos.append({'CCODUSU': r2[0], 'CNRODNI': r2[1], 'CUSUINF': r2[2], 'CESTADO': r2[3], 'CFLAG': '1'})
               if r2[0].strip() != r2[2].strip():
                  self.laDatos.append({'CCODUSU': r2[0], 'CNRODNI': r2[1], 'CUSUINF': r2[2], 'CESTADO': r2[3], 'CFLAG': '2'})
       # Usuarios con mismo DNI
       lcSql = "SELECT DISTINCT SUBSTRING(cNroDni, 1, 4) FROM S01TUSU ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cCodUsu, TRIM(cNroDni), TRIM(cUsuInf), cEstado FROM S01TUSU WHERE cNroDni LIKE '{lcNroDni}' ORDER BY cNroDni"
           #print(lcSql)
           R2 = self.loSql.omExecRS(lcSql)
           laFila = {'CNRODNI': '*', 'CESTADO': '*'}
           for r2 in R2:
               if r2[1] == '' or r2[1] == '00000000':
                  continue
               if r2[1] == laFila['CNRODNI'] and r2[3] == laFila['CESTADO']:
                  self.laDatos.append(laFila)
                  self.laDatos.append({'CCODUSU': r2[0], 'CNRODNI': r2[1], 'CUSUINF': r2[2], 'CESTADO': r2[3], 'CFLAG': '4'})
               laFila = {'CCODUSU': r2[0], 'CNRODNI': r2[1], 'CUSUINF': r2[2], 'CESTADO': r2[3], 'CFLAG': '4'}   
       return True

   def mxPrintUsuariosDNI(self):
       for laTmp in self.laDatos:
           print(laTmp['CCODUSU'] + ';' + laTmp['CUSUINF'] + ';' + laTmp['CNRODNI'] + ';' + laTmp['CESTADO'] + ';' + laTmp['CFLAG'])
       return True

   # ----------------------------------------------------------------
   # Verifica si interesado tiene deudas
   # 2022-12-20 FPM Creacion
   # ----------------------------------------------------------------
   def omConsultarDeuda(self):
       llOk = self.mxValParamConsultarDeuda()
       if not llOk:
          return False
       # Conecta con UCSMASBANC
       llOk = self.loSql.omConnect(3)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxConsultarDeuda()
       self.loSql.omDisconnect();
       return llOk;

   def mxValParamConsultarDeuda(self):
       # CCODIGO: puede ser DNI o codigo de alumno
       # CFLAG: S: detalle de deuda
       #        N: sin detalle de deuda
       #        E: Excepcion, si no existe DNI o codigo de alumno devuelve cero como deuda
       if not 'CCODIGO' in self.paData or not (re.match('^[0-9]{10}$', self.paData['CCODIGO']) or re.match('^[A-Z0-9]{8}$', self.paData['CCODIGO'])):
          self.pcError = 'CODIGO DE IDENTIFICACION NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CFLAG' in self.paData:
          self.paData['CFLAG'] = 'N'
       if not re.match('^[SNE]{1}$', self.paData['CFLAG']):
          self.pcError = 'FLAG DE DETALLE INVÁLIDO'
          return False
       return True

   def mxConsultarDeuda(self):
       laCodAlu = []
       # Conecta con UCSMERP
       loSql = CSql()
       llOk = loSql.omConnect()
       if not llOk:
          self.pcError = loSql.pcError
          return False
       laData = {'CNRODNI': '*', 'CNOMBRE': '', 'NDEUDA': 0.00, 'DATOS': ''}
       if len(self.paData['CCODIGO']) == 10:
          # Busca por codigo de estudiante
          lcSql = f"SELECT cNroDni, REPLACE(cNombre, '/', ' ') FROM V_A01MALU WHERE cCodAlu = '{self.paData['CCODIGO']}'"
          R1 = loSql.omExecRS(lcSql)
          if len(R1) == 0 and self.paData['CFLAG'] in ['S', 'N']:
             self.pcError = 'CÓDIGO DE ESTUDIANTE NO EXISTE - REVISE'
             return False
          elif len(R1) == 0 and self.paData['CFLAG'] == 'E':
             self.paData = laData
             return True
          laData['CNRODNI'] = R1[0][0]
          laData['CNOMBRE'] = R1[0][1]
          lcSql = f"SELECT cCodAlu FROM A01MALU WHERE cNroDni = '{laData['CNRODNI']}' ORDER BY cCodAlu";
          R1 = loSql.omExecRS(lcSql);
          for r in R1:
              laCodAlu.append(R1[0][0])
          llFlag = True    
          for lcCodAlu in laCodAlu:
              if lcCodAlu == self.paData['CCODIGO']:
                 llFlag = False
                 break
          if llFlag:
              laCodAlu.append(self.paData['CCODIGO'])
       else:
          # Busca por DNI
          lcSql = f"SELECT REPLACE(cNombre, '/', ' ') FROM S01MPER WHERE cNroDni = '{self.paData['CCODIGO']}'"
          R1 = loSql.omExecRS(lcSql)
          #print(lcSql)
          #print(R1)
          if len(R1) == 0 and self.paData['CFLAG'] in ['S', 'N']:
             self.pcError = 'DNI DE ESTUDIANTE NO EXISTE'
             return False
          elif len(R1) == 0 and self.paData['CFLAG'] == 'E':
             self.paData = laData
             return True
          laData['CNRODNI'] = self.paData['CCODIGO']
          laData['CNOMBRE'] = R1[0][0]
          lcSql = f"SELECT cCodAlu FROM A01MALU WHERE cNroDni = '{laData['CNRODNI']}' ORDER BY cCodAlu";
          R1 = loSql.omExecRS(lcSql);
          for r in R1:
              laCodAlu.append(R1[0][0])
       if len(laCodAlu) == 0:
          self.pcError = 'NO HAY CÓDIGOS DE ESTUDIANTE DEFINIDOS - REVISE'
          return False
       laDatos = []
       lnDeuda = 0.00
       for lcCodAlu in laCodAlu:
           lcSql = f"""SELECT cCodAlu, cDesTas, TRIM(cProyec), cNroCuo, cConcep, cDesCon, nMonto, TO_CHAR(dEmisio, 'YYYY-MM-DD'), TO_CHAR(dVencim, 'YYYY-MM-DD')
                       FROM B09DSAL WHERE cCodAlu = '{lcCodAlu}' AND cEstado IN ('A', 'H') ORDER BY cProyec, cNroCuo"""
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               lnDeuda += float(r2[6])
               if self.paData['CFLAG'] == 'S':
                  laDatos.append({'CCODALU': r2[0], 'CDESTAS': r2[1], 'CPROYEC': r2[2], 'CNROCUO': r2[3], 'CCONCEP': r2[4], 'CDESCON': r2[5],\
                                  'NMONTO': float(r2[6]), 'DEMISIO': r2[7], 'DVENCIM': r2[8]})
       laData['DATOS'] = laDatos   
       laData['NDEUDA'] = lnDeuda   
       self.paData = laData
       return True

   # ----------------------------------------------------------------
   # Extrae estudiantes sin DNI
   # 2022-12-20 FPM Creacion
   # ----------------------------------------------------------------
   def omEstudiantesDNIError(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxEstudiantesDNIError()
       self.loSql.omDisconnect();
       llOk = self.mxEstudiantesDNIErrorCsv()
       return llOk;

   def mxEstudiantesDNIError(self):
       lcSql = f"SELECT DISTINCT cCodalu FROM C10DCCT WHERE cEstado = 'A' AND LEFT(cProyec,4) IN ('2020','2021','2022')"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = f"SELECT cNroDni FROM A01MALU WHERE cCodAlu = '{r[0]}'"
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) == 0:
              self.laDatos.append([r[0], '*'])
           elif not re.match('^[0-9]{8}$', R2[0][0]):
              self.laDatos.append([r[0], R2[0][0]])
       return True

   def mxEstudiantesDNIErrorCsv(self):
       loFile = open('./EstudiantesDNIError.csv', 'w')
       loFile.write('CCODALU;CNRODNI\n')
       for laTmp in self.laDatos:
           lcLinea = laTmp[0] + ';' + laTmp[1] + '\n'
           loFile.write(lcLinea)
       loFile.close()
       return True

   # ----------------------------------------------------------------
   # Saca relacion de estudiantes con deuda
   # 2023-01-05 FPM Creacion
   # ----------------------------------------------------------------
   def omEstudiantesDeuda(self):
       llOk = self.loSql.omConnect(-1)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxEstudiantesDeuda()
       self.loSql.omDisconnect();
       return llOk;

   def mxEstudiantesDeuda(self):
       loSql = CSql()
       llOk = loSql.omConnect()
       loFile = open('./Deudores1.csv', 'w')
       lcSql = f"SELECT DISTINCT SUBSTRING(CCODALU, 1, 4) FROM B09DSAL"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcCodAlu = r[0] + '%'
           lcSql = f"SELECT DISTINCT CCODALU FROM B09DSAL WHERE cCodAlu LIKE '{lcCodAlu}'"
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               self.mxDatosEstudiante(loSql, loFile, r2[0])
       loFile.close()        
       loSql.omDisconnect()
       return True

   def mxDatosEstudiante(self, p_oSql, p_oFile, p_cCodAlu):
       lcSql = f"""SELECT B.cNroCel FROM A01MALU A INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni
                   WHERE A.cCodAlu = '{p_cCodAlu}'""" 
       R1 = p_oSql.omExecRS(lcSql)
       if len(R1) == 0:
          return
       lcLinea = R1[0][0] + '\n'
       # loFile.write(lcLinea)
       p_oFile.write(lcLinea)
       return

   # ----------------------------------------------------------------
   # 
   # 2023-01-05 FPM Creacion
   # ----------------------------------------------------------------
   def omEmailDeudaIdiomas(self):
       llOk = self.mxCargarEmails()
       if not llOk:
          self.pcError = 'ERROR 1'
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxEmailDeudaIdiomas()
       self.loSql.omDisconnect();
       return llOk;

   def mxCargarEmails(self):
       lcDniNro = '*'
       loFile = open('./Idiomas3.csv', 'r')
       for lcLinea in loFile:
           laTmp = lcLinea.split(';')
           lcNroDni = laTmp[0].strip()
           if lcDniNro == '*':
              lcDniNro = lcNroDni
           elif lcNroDni != lcDniNro:
              self.laDatos.append(lcDniNro)
              lcDniNro = lcNroDni
       self.laDatos.append(lcDniNro)
       loFile.close()
       
       return True

   def mxEmailDeudaIdiomas(self):
       for lcNroDni in self.laDatos:
           lcSql = f"SELECT cEmail FROM S01MPER WHERE cNroDni = '{lcNroDni}'"
           R1 = self.loSql.omExecRS(lcSql)
           if len(R1) == 0:
              continue
           print(R1[0][0])
       return True

   # ----------------------------------------------------------------
   # 
   # 2023-01-05 FPM Creacion
   # ----------------------------------------------------------------
   def omCompararS01MPER(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       loSql = CSql()
       llOk = loSql.omConnect(2)
       if not llOk:
          self.pcError = loSql.pcError
          return False
       llOk = self.mxCompararS01MPER1(loSql)
       if not llOk:
          loSql.omDisconnect()
          self.loSql.omDisconnect();
       llOk = self.mxCompararS01MPER2(loSql)
       loSql.omDisconnect()
       self.loSql.omDisconnect();
       return llOk;

   def mxCompararS01MPER1(self, p_oSql):
       # UCSMERP - UCSMINS
       lcSql = "SELECT DISTINCT(SUBSTRING(cNroDni, 1, 4)) FROM S01MPER ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cNroDni, cNroDoc FROM S01MPER WHERE cNroDni LIKE '{lcNroDni}' AND cTipDoc = '1' ORDER BY cNroDni"
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               lcSql = f"SELECT cNroDni, cTipDoc, cDocExt FROM S01MPER WHERE cNroDni = '{r2[0]}'"
               R3 = p_oSql.omExecRS(lcSql)
               if len(R3) == 0:
                  print('ERR11;' + r2[0] + ';*')
               elif R3[0][1] != '1':
                  print('ERR12;' + r2[0] + ';' + r2[1] + ';' + R3[0][1] + ';' + R3[0][2])
       # UCSMINS - UCSMERP
       lcSql = "SELECT DISTINCT(SUBSTRING(cNroDni, 1, 4)) FROM S01MPER ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = p_oSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cNroDni FROM S01MPER WHERE cNroDni LIKE '{lcNroDni}' AND cTipDoc = '1' ORDER BY cNroDni"
           R2 = p_oSql.omExecRS(lcSql)
           for r2 in R2:
               lcSql = f"SELECT cNroDni, cTipDoc FROM S01MPER WHERE cNroDni = '{r2[0]}'"
               R3 = self.loSql.omExecRS(lcSql)
               if len(R3) == 0:
                  print('ERR21;' + r2[0] + ';*')
               elif R3[0][1] != '1':
                  print('ERR22;' + r2[0] + ';' + R3[0][1])
       return True

   def mxCompararS01MPER2(self, p_oSql):
       # UCSMERP - UCSMINS
       lcSql = "SELECT DISTINCT(SUBSTRING(cNroDni, 1, 4)) FROM S01MPER WHERE cTipDoc != '1' ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cNroDni, cTipDoc, cNroDoc FROM S01MPER WHERE cNroDni LIKE '{lcNroDni}' AND cTipDoc != '1' ORDER BY cNroDni"
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               lcSql = f"SELECT cNroDni, cTipDoc, cDocExt FROM S01MPER WHERE cNroDni = '{r2[0]}'"
               R3 = p_oSql.omExecRS(lcSql)
               if len(R3) == 0:
                  print('ERR31;' + r2[0] + ';*')
               elif R3[0][1] != r2[1]:
                  print('ERR32;' + r2[0] + ';' + r2[1] + ';' + R3[0][1])
               elif R3[0][2] != r2[2]:
                  print('ERR33;' + r2[0] + ';' + r2[2] + ';' + R3[0][2])
       # UCSMINS - UCSMERP
       lcSql = "SELECT DISTINCT(SUBSTRING(cNroDni, 1, 4)) FROM S01MPER WHERE cTipDoc != '1' ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = p_oSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cNroDni, cTipDoc, cDocExt FROM S01MPER WHERE cNroDni LIKE '{lcNroDni}' AND cTipDoc != '1' ORDER BY cNroDni"
           R2 = p_oSql.omExecRS(lcSql)
           for r2 in R2:
               lcSql = f"SELECT cNroDni, cTipDoc, cNroDoc FROM S01MPER WHERE cNroDni = '{r2[0]}'"
               R3 = self.loSql.omExecRS(lcSql)
               if len(R3) == 0:
                  print('ERR41;' + r2[0] + ';*')
               elif R3[0][1] != r2[1]:
                  print('ERR42;' + r2[0] + ';' + r2[1] + ';' + R3[0][1])
               elif R3[0][2] != r2[2]:
                  print('ERR43;' + r2[0] + ';' + r2[2] + ';' + R3[0][2])
       return True

   # ----------------------------------------------------------------
   # Actualiza S0IMPER, de UCSMERP a UCSMINS
   # 2023-01-05 FPM Creacion
   # 2023-01-13 FPM Lista faltantes del S01MPER de UCSMINS a UCSMERP
   # ----------------------------------------------------------------
   def omActualizarS01MPER(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       loSql = CSql()
       llOk = loSql.omConnect(2)
       if not llOk:
          self.pcError = loSql.pcError
          return False
       llOk = self.mxActualizarS01MPER(loSql)
       if llOk:
          loSql.omCommit()
       llOk = self.mxListarS01MPER(loSql)
       loSql.omDisconnect()
       self.loSql.omDisconnect()
       return llOk;

   def mxActualizarS01MPER(self, p_oSql):
       # UCSMERP - UCSMINS
       lcSql = "SELECT DISTINCT(SUBSTRING(cNroDni, 1, 4)) FROM S01MPER ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cNroDni FROM S01MPER WHERE cNroDni LIKE '{lcNroDni}' ORDER BY cNroDni"
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               if r2[0].strip() == '':
                  continue
               llOk = self.mxS01MPERActualizar(p_oSql, r2[0])
               if not llOk:
                  return False
           p_oSql.omCommit()       
       return True
                  
   def mxS01MPERActualizar(self, p_oSql, p_cNroDni):
       lcSql = f"SELECT cNroDni FROM S01MPER WHERE cNroDni = '{p_cNroDni}'"
       R1 = p_oSql.omExecRS(lcSql)
       if len(R1) == 1:
          return True
       lcSql = f"SELECT cEstado, cNombre, cSexo, cNroCel, cEmail, cTipDoc, cNroDoc, cClave, dNacimi, cDirecc FROM S01MPER WHERE cNroDni = '{p_cNroDni}'"
       R1 = self.loSql.omExecRS(lcSql)
       lcNombre = R1[0][1].replace("'", "''")
       lcEmail  = R1[0][4].replace("'", "")
       lcDirecc = R1[0][9].replace("'", " ").upper()
       lcNroCel = R1[0][3]
       lcNroCel = lcNroCel[-12:]
       lcSql = f"""INSERT INTO S01MPER (cNroDni, cNombre, cSexo, cEstado, cTipDoc, cDocExt, cUbiNac, dNacimi, cEmail , cNroCel, cUbiDir, cDirecc, cColPro, cClave, cCodUsu) 
                   VALUES ('{p_cNroDni}', '{lcNombre}', '{R1[0][2]}', '{R1[0][0]}', '{R1[0][5]}', '{R1[0][6]}', '000000', '{R1[0][8]}',
                   '{lcEmail}', '{lcNroCel}', '000000', '{lcDirecc}', '*', '{R1[0][7]}', 'U666')"""
       llOk = p_oSql.omExec(lcSql)
       if not llOk:
          print(lcSql)
          self.pcError = 'ERR1'
          return False
       return True

   def mxListarS01MPER(self, p_oSql):
       # UCSMINS - UCSMERP
       lcSql = "SELECT DISTINCT(SUBSTRING(cNroDni, 1, 4)) FROM S01MPER ORDER BY SUBSTRING(cNroDni, 1, 4)"
       R1 = p_oSql.omExecRS(lcSql)
       for r in R1:
           lcNroDni = r[0] + '%'
           lcSql = f"SELECT cNroDni FROM S01MPER WHERE cNroDni LIKE '{lcNroDni}' ORDER BY cNroDni"
           R2 = p_oSql.omExecRS(lcSql)
           for r2 in R2:
               if r2[0].strip() == '':
                  continue
               llOk = self.mxS01MPERListar(p_oSql, r2[0])
               if not llOk:
                  return False
       loFile = open('./S01MPER1.csv', 'w')
       for laTmp in self.laDatos:
           lcLinea = laTmp['CNRODNI'] + ';' + laTmp['CNOMBRE'] + ';' + laTmp['CTIPDOC'] + ';' + laTmp['CDOCEXT'] + '\n'
           loFile.write(lcLinea)
       loFile.close()
       return True
                  
   def mxS01MPERListar(self, p_oSql, p_cNroDni):
       lcSql = f"SELECT cNroDni FROM S01MPER WHERE cNroDni = '{p_cNroDni}'"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 1:
          return True
       lcSql = f"SELECT cNroDni, cNombre, cTipDoc, cDocExt FROM S01MPER WHERE cNroDni = '{p_cNroDni}'"
       R1 = p_oSql.omExecRS(lcSql)
       self.laDatos.append({'CNRODNI': R1[0][0], 'CNOMBRE': R1[0][1], 'CTIPDOC': R1[0][2], 'CDOCEXT': R1[0][3]})
       return True

   # ----------------------------------------------------------------
   # Valida calculo de mora
   # 2023-01-11 FPM Creacion
   # ----------------------------------------------------------------
   def omValidarMora(self):
       lnTime1 = time.time()
       llOk = self.loSql.omConnect(3)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       loFile = open('./Mora.csv', 'w')
       llOk = self.mxValidarMora1(loFile)
       if not llOk:
          loFile.close()
          loSql.omDisconnect()
          return False
       llOk = self.mxValidarMora2(loFile)
       if not llOk:
          loFile.close()
          loSql.omDisconnect()
          return False
       llOk = self.mxValidarMora3(loFile)
       if not llOk:
          loFile.close()
          loSql.omDisconnect()
          return False
       llOk = self.mxValidarMora9(loFile)
       if not llOk:
          loFile.close()
          loSql.omDisconnect()
          return False
       loFile.close()
       self.loSql.omDisconnect()
       print(time.time() - lnTime1)
       return llOk

   def mxValidarMora1(self, p_oFile):
       print('Validando mora de cuotas no vencidas...')
       lcSql = "SELECT cCodAlu, cNombre, TO_CHAR(dVencim, 'YYYY-MM-DD'), nDeuda, nMora, nMonto FROM B09DSAL WHERE cEstado = 'A' AND nMora > 0 AND dVencim >= NOW()::DATE"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcLinea = 'ERR1;' + r[0] + ';' + r[1] + ';' + r[2] + ';' + str(float(r[3])) + ';' + str(float(r[4])) + ';' + str(float(r[5])) + '\n'
           p_oFile.write(lcLinea)
       return True

   def mxValidarMora2(self, p_oFile):
       print('Validando mora de mellizos...')
       lcSql = "SELECT cCodigo, nMora FROM B09DSAL WHERE cEstado = 'A' AND nMora > 0 AND SUBSTRING(cCodAlu, 1, 2) = '12' AND cCodigo != '00000000'"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = f"""SELECT cCodAlu, cNombre, TO_CHAR(dVencim, 'YYYY-MM-DD'), nDeuda, nMora, nMonto FROM B09DSAL WHERE cCodigo = '{r[0]}'"""
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) == 0:
              lcLinea = 'ERR2;' + r[0] + '\n'
              p_oFile.write(lcLinea)
           elif float(r[1]) != float(R2[0][5]):
              lcLinea = 'ADV2;' + r2[0] + ';' + r2[1] + ';' + r2[2] + ';' + str(float(r2[3])) + ';' + str(float(r2[4])) + ';' + str(float(r2[5])) + '\n'
              p_oFile.write(lcLinea)
       return True

   def mxValidarMora3(self, p_oFile):
       print('Validando vencidos con mora 0...')
       lcSql = f"""SELECT cCodAlu, cNombre, TO_CHAR(dVencim, 'YYYY-MM-DD'), nDeuda, nMora, nMonto FROM B09DSAL WHERE dVencim < NOW()::DATE AND cEstado = 'A' AND 
                   nMora = 0"""
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcLinea = 'ERR3;' + r[0] + ';' + r[1] + ';' + r[2] + ';' + str(float(r[3])) + ';' + str(float(r[4])) + ';' + str(float(r[5])) + '\n'
           p_oFile.write(lcLinea)
       return True

   def mxValidarMora4(self, p_oFile):
       print('Validando vencidos con mora negativa...')
       lcSql = f"""SELECT cCodAlu, cNombre, TO_CHAR(dVencim, 'YYYY-MM-DD'), nDeuda, nMora, nMonto FROM B09DSAL WHERE dVencim < NOW()::DATE AND cEstado = 'A' AND 
                   nMora < 0"""
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcLinea = 'ERR4;' + r[0] + ';' + r[1] + ';' + r[2] + ';' + str(float(r[3])) + ';' + str(float(r[4])) + ';' + str(float(r[5])) + '\n'
           p_oFile.write(lcLinea)
       return True

   def mxValidarMora9(self, p_oFile):
       print('Generando muestra de cuotas con mora...')
       lcSql = "SELECT DISTINCT SUBSTRING(TO_CHAR(dVencim, 'YYYY-MM-DD'), 1, 7) FROM B09DSAL WHERE cEstado = 'A' AND nMora > 0"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = f"""SELECT cCodAlu, cNombre, TO_CHAR(dVencim, 'YYYY-MM-DD'), nDeuda, nMora, nMonto FROM B09DSAL WHERE SUBSTRING(cCodAlu, 1, 2) = '00' AND
                       SUBSTRING(TO_CHAR(dVencim, 'YYYY-MM-DD'), 1, 7) = '{r[0]}' AND cEstado = 'A' AND nMora > 0 ORDER BY cCodAlu LIMIT 2"""
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               lcLinea = 'EVAL;' + r2[0] + ';' + r2[1] + ';' + r2[2] + ';' + str(float(r2[3])) + ';' + str(float(r2[4])) + ';' + str(float(r2[5])) + '\n'
               p_oFile.write(lcLinea)
       return True

   # ----------------------------------------------------------------
   # Inicio de sesion de usuario UCSM a traves de servidor local
   # 2023-01-12 FPM Creacion
   # ----------------------------------------------------------------
   def omLoginUsuarioUCSM(self):
       llOk = self.mxValParamLoginUsuarioUCSM()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxLoginUsuarioUCSM()
       self.loSql.omDisconnect()
       self.paData = self.laData
       return llOk
       
   def mxValParamLoginUsuarioUCSM(self):
       print(self.paData)
       return True
          
   def mxLoginUsuarioUCSM(self):
       self.laData = {'CNRODNI': '', 'CNOMBRE': '', 'CCODUSU': '', 'CCENCOS': '*', 'CDESCCO': 'SIN ASIGNAR', 'CCARGO': '', 'CNIVEL': ''}
       lcSql = f"SELECT cNombre, cClave, cClaAca FROM S01MPER WHERE cNroDni = '{self.paData['CNRODNI']}'"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 1 and not (R1[0][1] == self.paData['CCLAVE'] or R1[0][2] == self.paData['CCLAVE']):
          self.pcError = 'CLAVE INCORRECTA'
          return False
       elif len(R1) == 1 and R1[0][1] == self.paData['CCLAVE']:
          self.laData.update({'CNRODNI': self.paData['CNRODNI'], 'CNOMBRE': R1[0][0].replace('/', ' ')})
          llOk = self.mxValUsuarioDocente()
          return llOk
       lcSql = f"SELECT cNroDni, cNombre, cClave FROM S01MPER WHERE cNroDoc = '{self.paData['CNRODNI']}' AND cEstado = 'A'"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 1 and R1[0][2] != self.paData['CCLAVE']:
          self.pcError = 'CLAVE INCORRECTA'
          return False
       elif len(R1) == 1 and R1[0][2] == self.paData['CCLAVE']:
          self.laData.update({'CNRODNI': R1[0][0], 'CNOMBRE': R1[0][1].replace('/', ' ')})
          llOk = self.mxValUsuarioDocente()
          return llOk
       self.pcError = 'DOCUMENTO DE IDENTIDAD NO EXISTE'
       return False
       
   def mxValUsuarioDocente(self):
       lcSql = f"SELECT cEstado, cCodUsu, TRIM(cCargo), cNivel FROM S01TUSU WHERE cNroDni = '{self.laData['CNRODNI']}'"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = f"DNI [{self.laData['CNRODNI']}] NO TIENE USUARIO ASOCIADO"
          return False
       elif R1[0][0] != 'A':
          self.pcError = 'USUARIO NO ESTÁ ACTIVO'
          return False
       self.laData.update({'CCODUSU': R1[0][1], 'CCARGO': R1[0][2], 'CNIVEL': R1[0][3]})   
       lcSql = f"""SELECT A.cCenCos, B.cDescri FROM S01PCCO A INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos 
                   WHERE A.cCodUsu = '{self.laData['CCODUSU']}' AND A.cEstado = 'A' ORDER BY cCenCos LIMIT 1"""
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 1:
          self.laData.update({'CCENCOS': R1[0][0], 'CDESCCO': R1[0][1]})   
       return True

   # ----------------------------------------------------------------
   # Eliminar deudas codigo 12 vencidas
   # 2023-01-13 FPM Creacion
   # ----------------------------------------------------------------
   def omEliminarCodigo12(self):
       llOk = self.mxValParamEliminarCodigo12()
       if not llOk:
          return False
       llOk = self.loSql.omConnect(3)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxEliminarCodigo12()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       llOk = self.mxMostrarCodigo12()
       self.paData = {'OK': 'OK'}
       return llOk
       
   def mxValParamEliminarCodigo12(self):
       print(self.paData)
       return True
          
   def mxEliminarCodigo12(self):
       lcSql = f"""SELECT nIdDeud, cCodAlu, cCodigo, TO_CHAR(dVencim, 'YYYY-MM-DD') FROM B09DSAL WHERE SUBSTRING(cCodAlu, 1, 2) = '12' AND 
                   cEstado = 'A' AND dVencim < '2023-02-01'"""
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           self.laDatos.append({'NIDDEUD': r[0], 'CCODALU': r[1], 'CCODIGO': r[2], 'DVENCIM': r[3]})   
           lcSql = f"SELECT nIdDeud, cCodAlu, cCodigo, TO_CHAR(dVencim, 'YYYY-MM-DD') FROM B09DSAL WHERE SUBSTRING(cCodAlu, 1, 2) != '12' AND cCodigo = '{r[2]}'"
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) == 1:
              self.laDatos.append({'NIDDEUD': R1[0][0], 'CCODALU': R1[0][1], 'CCODIGO': R1[0][2], 'DVENCIM': R1[0][3]})   
       if len(self.laDatos) == 0:
          self.pcError = 'NO HAY PAGOS A ANULAR'
          return False
       for laTmp in self.laDatos:
           lcSql = f"UPDATE B09DSAL SET cEstado = 'X' WHERE nIdDeud = {laTmp['NIDDEUD']}" # OJOFPM
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              print(lcSql)
              self.pcError = 'NO SE PUDO ANULAR PAGO'
              return False
       return True

   def mxMostrarCodigo12(self):
       loFile = open('./Anulados12.csv', 'w')
       for laTmp in self.laDatos:
           lcLinea = str(laTmp['NIDDEUD']) + ';' + laTmp['CCODALU'] + ';' + laTmp['CCODIGO'] + ';' + laTmp['DVENCIM'] + '\n'
           loFile.write(lcLinea)
       loFile.close()

   # ----------------------------------------------------------------
   #
   # 2023-01-20 FPM Creacion
   # ----------------------------------------------------------------
   def omCobranzasCierre2022(self):
       lnTime1 = time.time()
       llOk = self.loSql.omConnect(2)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCobranzasCierre2022()
       self.loSql.omDisconnect()
       print(time.time() - lnTime1)
       return llOk

   def mxCobranzasCierre2022(self):
       laMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic']
       loDate = CDate()
       ldFecha = '2022-01-07'
       while True:
          if ldFecha[0:4] == '2023':
             ldFecha = '2022-12-31'
          lcSql = f"""SELECT COUNT(*), SUM(nMonto) FROM B03MDEU WHERE cPaquet IN ('D') AND cEstado = 'C' AND
                      dFecha >= '2022-01-01' AND dFecha <= '{ldFecha}'"""
          #print(lcSql)
          R1 = self.loSql.omExecRS(lcSql)
          laTmp = self.loSql.fetch(R1)
          if laTmp[1] == None:
             lcFecha = ldFecha[-2:] + '-' + laMeses[int(ldFecha[5:7]) - 1]
             print(lcFecha + ';' + str(laTmp[0]) + ';0.00')
          else:
             lcFecha = ldFecha[-2:] + '-' + laMeses[int(ldFecha[5:7]) - 1]
             print(lcFecha + ';' + str(laTmp[0]) + ';' + str(float(laTmp[1])))
          if ldFecha == '2022-12-31':
             break
          ldFecha = loDate.add(ldFecha, 7)
       return True

   def mxCobranzasCierre2022_1(self):
       laMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic']
       loDate = CDate()
       ldFecha = '2022-01-07'
       while True:
          if ldFecha[0:4] == '2023':
             ldFecha = '2022-12-31'
          lcSql = f"""SELECT COUNT(A.*), SUM(B.nMonto) FROM B05MCPJ A
                      INNER JOIN B03MDEU B ON B.cIdDeud = A.cIdDeud
                      WHERE B.cEstado IN ('C') AND B.dFecha >= '2022-01-01' AND B.dFecha <= '{ldFecha}'"""
          R1 = self.loSql.omExecRS(lcSql)
          laTmp = self.loSql.fetch(R1)
          if laTmp[1] == None:
             lcFecha = ldFecha[-2:] + '-' + laMeses[int(ldFecha[5:7]) - 1]
             print(lcFecha + ';' + str(laTmp[0]) + ';0.00')
          else:
             lcFecha = ldFecha[-2:] + '-' + laMeses[int(ldFecha[5:7]) - 1]
             print(lcFecha + ';' + str(laTmp[0]) + ';' + str(float(laTmp[1])))
          if ldFecha == '2022-12-31':
             break
          ldFecha = loDate.add(ldFecha, 7)
       return True
   # ----------------------------------------------------------------
   #
   # 2023-01-20 FPM Creacion
   # ----------------------------------------------------------------
   def omCobranzas2023(self):
       lnTime1 = time.time()
       llOk = self.mxFechaActual()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCobranzas2023()
       self.loSql.omDisconnect()
       llOk = self.mxPrintData2023()
       print(time.time() - lnTime1)
       return llOk

   def mxFechaActual(self):
       laMeses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic']
       loActual = datetime.datetime.now()
       self.paData['DFECHA'] = loActual.strftime('%Y-%m-%d')
       #self.paData['DFECHA'] = '2023-03-06'
       #loActual = datetime.date(2023, 3, 6)
       lcDay = '0' + str(loActual.day)
       lcActual = laMeses[loActual.month - 1] + '-' + lcDay[-2:]
       self.laData = {'CFECHA': lcActual}
       return True

   def mxCobranzas2023(self):
       # 2023-0
       laData = {'20230C': 0, '20230P': 0.00, '20230V': 0.00}
       lcSql = f"""SELECT R.cProyec, SUM(R.nCuota) AS nCuota, SUM(R.nMonPag) AS nMonPag,
                   SUM(CASE WHEN R.nPorVen - R.nMonPag <= 0.00 THEN 0.00 ELSE R.nPorVen - R.nMonPag END) AS nVencid,
                   SUM(CASE WHEN R.nPorVen + R.nVencim - R.nMonPag <= 0.00 THEN 0.00
                            WHEN R.nPorVen - R.nMonPag <= 0.00             THEN R.nPorVen + R.nVencim - R.nMonPag
                                                                             ELSE R.nVencim END) AS nPorVen
                    FROM
                    (SELECT A.cCodalu, A.cProyec,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nCuota,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim < CURRENT_DATE)  THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nPorVen,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim >= CURRENT_DATE) THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nVencim,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN (A.nAbono - A.nDebito) ELSE 0.00 END) AS nMonPag
                      FROM C10DCCT A
                      WHERE LEFT(A.cDocume, 2) = '01' AND A.cEstado = 'A' AND A.dEmisio <= '{self.paData['DFECHA']}'
                      GROUP BY A.cCodalu, A.cProyec) AS R
                    WHERE R.cProyec IN ('2022-3')
                    GROUP BY R.cProyec ORDER BY R.cProyec"""
       #print(lcSql)             
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp != None:
          laData['20230P'] = 0.00 if laTmp[2] == None else float(laTmp[2])
          laData['20230V'] = 0.00 if laTmp[3] == None else float(laTmp[3])
       # Cantidad de estudiantes 2023-0
       lcSql = f"""SELECT R.cProyec, COUNT(*) FROM (
                   SELECT A.cProyec, A.cCodalu, SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN A.nAbono - A.nDebito ELSE 0.00 END) AS nTotpag
                   FROM C10DCCT A
                   WHERE LEFT(A.cDocume, 2) = '01' 
                   AND A.cEstado = 'A'
                   AND A.cProyec = '2022-3'
                   AND A.dEmisio <= '{self.paData['DFECHA']}'
                   GROUP BY A.cProyec, A.cCodalu) R
                   WHERE R.ntotpag > 0.0
                   GROUP BY R.cProyec"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp != None:
          laData['20230C'] = 0 if laTmp[1] == None else laTmp[1]
       self.laData.update(laData)
       # Semestre 2023-1
       laData = {'20231C': 0, '20231E': 0.00, '20231P': 0.00, '20231V': 0.00}
       lcSql = f"""SELECT R.cProyec, SUM(R.nCuota) AS nCuota, SUM(R.nMonPag) AS nMonPag,
                   SUM(CASE WHEN R.nPorVen - R.nMonPag <= 0.00 THEN 0.00 ELSE R.nPorVen - R.nMonPag END) AS nVencid,
                   SUM(CASE WHEN R.nPorVen + R.nVencim - R.nMonPag <= 0.00 THEN 0.00
                            WHEN R.nPorVen - R.nMonPag <= 0.00             THEN R.nPorVen + R.nVencim - R.nMonPag
                                                                             ELSE R.nVencim END) AS nPorVen
                    FROM
                    (SELECT A.cCodalu, A.cProyec,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nCuota,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim < CURRENT_DATE)  THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nPorVen,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim >= CURRENT_DATE) THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nVencim,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN (A.nAbono - A.nDebito) ELSE 0.00 END) AS nMonPag
                      FROM C10DCCT A
                      WHERE LEFT(A.cDocume, 2) = '01' AND A.cEstado = 'A' AND A.dEmisio <= '{self.paData['DFECHA']}'
                      GROUP BY A.cCodalu, A.cProyec) AS R
                    WHERE R.cProyec IN ('2023-1')
                    GROUP BY R.cProyec ORDER BY R.cProyec"""
       # print(lcSql)            
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp != None:
          laData = {'20231E': 0.00 if laTmp[1] == None else float(laTmp[1]), 
                    '20231P': 0.00 if laTmp[2] == None else float(laTmp[2]),
                    '20231V': 0.00 if laTmp[3] == None else float(laTmp[3])}
       self.laData.update(laData)
       # Semestre 2023-1 Virtual
       laData = {'20231C-V': 0, '20231E-V': 0.00, '20231P-V': 0.00, '20231V-V': 0.00}
       lcSql = f"""SELECT R.cProyec, SUM(R.nCuota) AS nCuota, SUM(R.nMonPag) AS nMonPag,
                   SUM(CASE WHEN R.nPorVen - R.nMonPag <= 0.00 THEN 0.00 ELSE R.nPorVen - R.nMonPag END) AS nVencid,
                   SUM(CASE WHEN R.nPorVen + R.nVencim - R.nMonPag <= 0.00 THEN 0.00
                            WHEN R.nPorVen - R.nMonPag <= 0.00             THEN R.nPorVen + R.nVencim - R.nMonPag
                                                                             ELSE R.nVencim END) AS nPorVen
                    FROM
                    (SELECT A.cCodalu, A.cProyec,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nCuota,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim < CURRENT_DATE)  THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nPorVen,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim >= CURRENT_DATE) THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nVencim,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN (A.nAbono - A.nDebito) ELSE 0.00 END) AS nMonPag
                      FROM C10DCCT A
                      WHERE LEFT(A.cDocume, 2) = '18' AND A.cEstado = 'A' AND A.dEmisio <= '{self.paData['DFECHA']}'
                      GROUP BY A.cCodalu, A.cProyec) AS R
                    WHERE R.cProyec IN ('2023-1')
                    GROUP BY R.cProyec ORDER BY R.cProyec"""
       # print(lcSql)            
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp != None:
          laData = {'20231E-V': 0.00 if laTmp[1] == None else float(laTmp[1]), 
                    '20231P-V': 0.00 if laTmp[2] == None else float(laTmp[2]),
                    '20231V-V': 0.00 if laTmp[3] == None else float(laTmp[3])}
       #print(laData)             
       self.laData.update(laData)
       #print(self.laData)
       #sys(exit(0))
       # Cantidad de estudiantes 2023-1
       lcSql = f"""SELECT R.cProyec, COUNT(*) FROM (
                   SELECT A.cProyec, A.cCodalu, SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN A.nAbono - A.nDebito ELSE 0.00 END) AS nTotpag
                   FROM C10DCCT A
                   WHERE LEFT(A.cDocume, 2) = '01' 
                   AND A.cEstado = 'A'
                   AND A.cProyec = '2023-1'
                   AND A.dEmisio <= '{self.paData['DFECHA']}'
                   GROUP BY A.cProyec, A.cCodalu) R
                   WHERE R.ntotpag > 0.0
                   GROUP BY R.cProyec"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       self.laData['20231C'] = laTmp[1]
       # Cantidad de estudiantes 2023-1 Virtual
       lcSql = f"""SELECT R.cProyec, COUNT(*) FROM (
                   SELECT A.cProyec, A.cCodalu, SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN A.nAbono - A.nDebito ELSE 0.00 END) AS nTotpag
                   FROM C10DCCT A
                   WHERE LEFT(A.cDocume, 2) = '18' 
                   AND A.cEstado = 'A'
                   AND A.cProyec = '2023-1'
                   AND A.dEmisio <= '{self.paData['DFECHA']}'
                   GROUP BY A.cProyec, A.cCodalu) R
                   WHERE R.ntotpag > 0.0
                   GROUP BY R.cProyec"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       self.laData['20231C-V'] = laTmp[1]
       # 2023-B8
       lcSql = f"""SELECT COUNT(*), SUM(nmonto) AS npago FROM (
                    SELECT ccodalu, SUM(nabono-ndebito) AS nmonto
                      FROM C10DCCT
                      WHERE LEFT(cdocume, 2) = '01' AND cproyec = '2023-1' AND ccuota = '01'
                        AND cestado = 'A' AND cconcep LIKE 'TE9%' AND nabono > 0.00
                        AND demisio <= '{self.paData['DFECHA']}'
                        AND ccodalu IN (SELECT ccodalu FROM C10DCCT WHERE cproyec = '2023-1' AND cconcep = 'TE0531'  AND cestado = 'A')
                      GROUP BY cCodalu
                    ) AS R"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp == None:
          laData = {'2023B8C': 0.00, '2023B8M': 0.00}
       else:   
          laData = {'2023B8C': 0.00 if laTmp[0] == None else laTmp[0], 
                    '2023B8M': 0.00 if laTmp[1] == None else float(laTmp[1])}
       self.laData.update(laData)
       # Cobranza 2022
       lcSql = f"""SELECT R.cProyec, SUM(R.nCuota) AS nCuota, SUM(R.nMonPag) AS nMonPag,
                   SUM(CASE WHEN R.nPorVen - R.nMonPag <= 0.00 THEN 0.00 ELSE R.nPorVen - R.nMonPag END) AS nVencid,
                   SUM(CASE WHEN R.nPorVen + R.nVencim - R.nMonPag <= 0.00 THEN 0.00
                            WHEN R.nPorVen - R.nMonPag <= 0.00             THEN R.nPorVen + R.nVencim - R.nMonPag
                                                                             ELSE R.nVencim END) AS nPorVen
                    FROM
                    (SELECT A.cCodalu, A.cProyec,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nCuota,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim < '{self.paData['DFECHA']}')  THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nPorVen,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim >= '{self.paData['DFECHA']}') THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nVencim,
                            SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN (A.nAbono - A.nDebito) ELSE 0.00 END) AS nMonPag
                      FROM C10DCCT A
                      WHERE LEFT(A.cDocume, 2) = '01' AND A.cEstado = 'A' AND A.dEmisio <= '{self.paData['DFECHA']}'
                      GROUP BY A.cCodalu, A.cProyec) AS R
                    WHERE R.cProyec IN ('2021-3', '2022-1', '2022-2')
                    GROUP BY R.cProyec ORDER BY R.cProyec"""
       #print(lcSql)             
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp is not None:
          #print(laTmp[0])
          if laTmp[0].strip() == '2021-3':
             laData = {'20220E': 0.00 if laTmp[1] == None else float(laTmp[1]), 
                       '20220P': 0.00 if laTmp[2] == None else float(laTmp[2]),
                       '20220V': 0.00 if laTmp[3] == None else float(laTmp[3])}
          elif laTmp[0].strip() == '2022-1':
             laData = {'20221E': 0.00 if laTmp[1] == None else float(laTmp[1]), 
                       '20221P': 0.00 if laTmp[2] == None else float(laTmp[2]),
                       '20221V': 0.00 if laTmp[3] == None else float(laTmp[3])}
          else:
             laData = {'20222E': 0.00 if laTmp[1] == None else float(laTmp[1]), 
                       '20222P': 0.00 if laTmp[2] == None else float(laTmp[2]),
                       '20222V': 0.00 if laTmp[3] == None else float(laTmp[3])}
          #print(laData)
          self.laData.update(laData)
          laTmp = self.loSql.fetch(R1)
       # Pagos 2021 hacia atras en 2023
       self.laData['2021<'] = 0.00
       lcSql = f"""SELECT A.cProyec, 
                   SUM(CASE WHEN LEFT(A.cConcep, 6) = 'TE9001' THEN A.nAbono - A.nDebito ELSE 0.00 END) AS nPago,
                   SUM(CASE WHEN LEFT(A.cConcep, 6) = 'TE9003' THEN A.nAbono - A.nDebito ELSE 0.00 END) AS nExtorn,
                   SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9'    THEN A.nAbono - A.nDebito ELSE 0.00 END) AS nTotpag
                   FROM C10DCCT A
                   WHERE LEFT(A.cDocume, 2) = '01' AND A.cEstado = 'A' AND A.dEmisio >= '2023-01-01' AND A.dEmisio <= '{self.paData['DFECHA']}'
                   AND LEFT(A.cProyec, 4) <= '2021'
                   GROUP BY A.cProyec"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp != None:
          self.laData['2021<'] = 0.00 if laTmp[2] == None else float(laTmp[2])
       # Deudas y pagos de maestria y doctorado
       lnEmiMae = lnPagMae = lnVenMae = lnEmiDoc = lnPagDoc = lnVenDoc = 0.00 
       lcSql = f"""SELECT R.cNivel, R.cDesniv, R.cProyec,
                   SUM(R.nCuota) AS nCuota, SUM(R.nMonPag) AS nMonPag,
                   SUM(CASE WHEN R.nPorVen - R.nMonPag <= 0.00 THEN 0.00 ELSE R.nPorVen - R.nMonPag END) AS nVencid,
                   SUM(CASE WHEN R.nPorVen + R.nVencim - R.nMonPag <= 0.00 THEN 0.00
                            WHEN R.nPorVen - R.nMonPag <= 0.00 THEN R.nPorVen + R.nVencim - R.nMonPag
                   ELSE R.nVencim END) AS nPorVen FROM
                   (SELECT A.cProyec, A.cCodalu, B.cUniaca, B.cNomuni, B.cNivel, B.cDesniv,
                    SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nCuota,
                    SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim < CURRENT_DATE)  THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nPorVen,
                    SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE0' AND (A.dVencim >= CURRENT_DATE) THEN (A.nDebito - A.nAbono) ELSE 0.00 END) AS nVencim,
                    SUM(CASE WHEN LEFT(A.cConcep, 3) = 'TE9' THEN (A.nAbono - A.nDebito) ELSE 0.00 END) AS nMonPag
                   FROM C10DCCT A
                   LEFT JOIN V_A01MALU B ON B.cCodalu = A.cCodalu
                    WHERE LEFT(A.cDocume, 2) = '02' AND A.cEstado = 'A' AND A.dEmisio <= '{self.paData['DFECHA']}'
                    AND LEFT(A.cConcep, 2) = 'TE'
                    GROUP BY A.cProyec, A.cCodalu, B.cUniaca, B.cNomuni, B.cNivel, B.cDesniv) AS R
                     WHERE LEFT(R.cProyec, 4) >=  '2010'
                     GROUP BY R.cNivel, R.cDesniv, R.cProyec
                     ORDER BY R.cNivel, R.cDesniv, R.cProyec"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp is not None:
          if laTmp[0] == '03':
             lnEmiMae += float(laTmp[3])
             lnPagMae += float(laTmp[4])
             lnVenMae += float(laTmp[5])
          else:
             lnEmiDoc += float(laTmp[3])
             lnPagDoc += float(laTmp[4])
             lnVenDoc += float(laTmp[5])
          laTmp = self.loSql.fetch(R1)
       self.laData['MAEST-E'] = lnEmiMae
       self.laData['MAEST-P'] = lnPagMae
       self.laData['MAEST-V'] = lnVenMae
       self.laData['DOCT-E']  = lnEmiDoc
       self.laData['DOCT-P']  = lnPagDoc
       self.laData['DOCT-V']  = lnVenDoc
       # Mora pagada
       lcSql = f"""SELECT a.codcta, SUM(a.habsol- a.debsol) AS valint FROM D10AASI AS a
                   LEFT JOIN D10DASI AS d ON d.idasid = a.idasid
                   LEFT JOIN D10MASI AS m ON m.idasie = d.idasie
                   WHERE m.anocom >= '2023'
                   AND (m.mescom >= '01' AND m.mescom <= '12')
                   AND m.tipcom = 'VE'
                   AND m.feccom <= '{self.paData['DFECHA']}'
                   AND a.codcta = '77221'
                   GROUP BY a.codcta"""
       #print(lcSql)
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp == None or laTmp[1] == None:
          self.laData['MORA'] = 0.00
       else:   
          self.laData['MORA'] = float(laTmp[1])
       # Cursos por jurado
       loSql = CSql()
       llOk = loSql.omConnect(2)
       if not llOk:
          self.pcError = loSql.pcError
          return False
       lcSql = f"""SELECT COUNT(A.*), SUM(B.nMonto) FROM B05MCPJ A
                   INNER JOIN B03MDEU B ON B.cIdDeud = A.cIdDeud
                   WHERE B.cEstado IN ('C') AND B.dFecha BETWEEN '2023-01-01' AND '{self.paData['DFECHA']}'"""
       R1 = loSql.omExecRS(lcSql)
       laTmp = loSql.fetch(R1)
       self.laData['2023J1'] = 0 if laTmp[0] == None else laTmp[0] 
       self.laData['2023J2'] = 0.00 if laTmp[1] == None else float(laTmp[1])
       # Bachillerato online
       lcSql = f"SELECT COUNT(*), SUM(nMonto) FROM B03MDEU WHERE cPaquet = 'B' AND cEstado = 'C' AND dFecha BETWEEN '2023-01-01' AND '{self.paData['DFECHA']}'"; 
       R1 = loSql.omExecRS(lcSql)   
       laTmp = loSql.fetch(R1)
       self.laData['2023B1'] = 0 if laTmp[0] == None else laTmp[0]
       self.laData['2023B2'] = 0.00 if laTmp[1] == None else float(laTmp[1])
       # Titulacion pregrado online
       lcSql = f"SELECT COUNT(*), SUM(nMonto) FROM B03MDEU WHERE cPaquet = 'T' AND cEstado = 'C' AND dFecha BETWEEN '2023-01-01' AND '{self.paData['DFECHA']}'"; 
       R1 = loSql.omExecRS(lcSql)
       laTmp = loSql.fetch(R1)
       self.laData['2023T1'] = 0 if laTmp[0] == None else laTmp[0]
       self.laData['2023T2'] = 0.00 if laTmp[1] == None else float(laTmp[1])
       # Titulacion pregrado online
       lcSql = f"SELECT COUNT(*), SUM(nMonto) FROM B03MDEU WHERE cPaquet = 'S' AND cEstado = 'C' AND dFecha BETWEEN '2023-01-01' AND '{self.paData['DFECHA']}'"; 
       #print(lcSql)
       R1 = loSql.omExecRS(lcSql)
       laTmp = loSql.fetch(R1)
       #print(laTmp)
       self.laData['202321'] = 0 if laTmp[0] == None else laTmp[0]
       self.laData['202322'] = 0.00 if laTmp[1] == None else float(laTmp[1])
       # Maestrias online
       lcSql = f"SELECT COUNT(*), SUM(nMonto) FROM B03MDEU WHERE cPaquet = 'M' AND cEstado = 'C' AND dFecha BETWEEN '2023-01-01' AND '{self.paData['DFECHA']}'"; 
       R1 = loSql.omExecRS(lcSql)
       laTmp = loSql.fetch(R1)
       self.laData['2023M1'] = 0 if laTmp[0] == None else laTmp[0]
       self.laData['2023M2'] = 0.00 if laTmp[1] == None else float(laTmp[1])
       # Doctorados online
       lcSql = f"SELECT COUNT(*), SUM(nMonto) FROM B03MDEU WHERE cPaquet = 'D' AND cEstado = 'C' AND dFecha BETWEEN '2023-01-01' AND '{self.paData['DFECHA']}'"; 
       R1 = loSql.omExecRS(lcSql)
       laTmp = loSql.fetch(R1)
       self.laData['2023D1'] = 0 if laTmp[0] == None else laTmp[0]
       self.laData['2023D2'] = 0.00 if laTmp[1] == None else float(laTmp[1])
       loSql.omDisconnect()
       self.paData = self.laData
       return True

   def mxPrintData2023(self):
       print('CFECHA;20230C;20230P;20230V;20231E;20231P;20231C;20231V;2023B8C;2023B8M;20220E;20220P;20220V;20221E;20221P;20221V;20222E;20222P;20222V;2023J1;2023J2;2023B1;2023B2;2023T1;2023T2;202321;202322;2023M1;2023M2;2023D1;2023D2;2021<;MAEST-E;MAEST-P;MAEST-V;DOCT-E;DOCT-P;DOCT-V;MORA;20231E-V;20231P-V;20231P-V')
       print(self.paData['CFECHA'] + ';' + str(self.paData['20230C']) + ';' + str(self.paData['20230P']) + ';' + str(self.paData['20230V']) + ';' + \
       str(self.paData['20231E']) + ';' + str(self.paData['20231P']) + ';' + str(self.paData['20231C']) + ';' + str(self.paData['20231V']) + ';' + 
       str(self.paData['2023B8C']) + ';' + str(self.paData['2023B8M']) + ';' + \
       str(self.paData['20220E']) + ';' + str(self.paData['20220P']) + ';' + str(self.paData['20220V']) + ';' + \
       str(self.paData['20221E']) + ';' + str(self.paData['20221P']) + ';' + str(self.paData['20221V']) + ';' + \
       str(self.paData['20222E']) + ';' + str(self.paData['20222P']) + ';' + str(self.paData['20222V']) + ';' + \
       str(self.paData['2023J1']) + ';' + str(self.paData['2023J2']) + ';' + str(self.paData['2023B1']) + ';' + str(self.paData['2023B2']) + ';' + \
       str(self.paData['2023T1']) + ';' + str(self.paData['2023T2']) + ';' + str(self.paData['202321']) + ';' + str(self.paData['202322']) + ';' + \
       str(self.paData['2023M1']) + ';' + str(self.paData['2023M2']) + ';' + str(self.paData['2023D1']) + ';' + str(self.paData['2023D2']) + ';' + \
       str(self.paData['2021<']) + ';' + str(self.paData['MAEST-E']) + ';' + str(self.paData['MAEST-P']) + ';' + str(self.paData['MAEST-V']) + ';' + \
       str(self.paData['DOCT-E']) + ';' + str(self.paData['DOCT-P']) + ';' + str(self.paData['DOCT-V']) + ';' + str(self.paData['MORA']) + ';' + \
       str(self.paData['20231E-V']) + ';' + str(self.paData['20231P-V']) + ';' + str(self.paData['20231C-V']) + ';' + str(self.paData['20231V-V'])) 
       return True
       #str(self.paData['20231E']) + ';' + str(self.paData['20231P']) + ';' + str(self.paData['20231C']) + ';' + str(self.paData['20231V']) + ';' + 

   # ----------------------------------------------------------------
   # Consulta centros de salud
   # 2023-01-20 FPM Creacion
   # ----------------------------------------------------------------
   def omCentrosSalud(self):
       llOk = self.loSql.omConnect(7)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCentrosSalud()
       self.loSql.omDisconnect()
       return llOk

   def mxCentrosSalud(self):
       if self.paData['CDESCRI'] == '*':
          lcSql = "SELECT cIdCent, cEstado, cDescri FROM A21TCSA ORDER BY cDescri"
       else:
          lcDescri = '%' + self.paData['CDESCRI'].strip() + '%'
          lcSql = f"SELECT cIdCent, cEstado, cDescri FROM A21TCSA WHERE cDescri LIKE '{lcDescri}' ORDER BY cDescri"
       #print(lcSql)
       print('ID  EST DESCRIPCION')
       print('----------------------------------------------------------------')
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          print(laTmp[0] + ' ' + laTmp[1] + '   ' + laTmp[2])
          laTmp = self.loSql.fetch(R1)
       print('----------------------------------------------------------------')
       self.paData = []   
       return True   

   # ----------------------------------------------------------------
   # Consulta cursos medicina clinica
   # 2023-01-20 FPM Creacion
   # ----------------------------------------------------------------
   def omCursosClinica(self):
       llOk = self.loSql.omConnect(7)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxCursosClinica()
       self.loSql.omDisconnect()
       return llOk

   def mxCursosClinica(self):
       if self.paData['CDESCRI'] == '*':
          lcSql = f"""SELECT A.cCodCur, A.cEstado, B.cDescri FROM A21MCUR A
                      INNER JOIN A02MCUR B ON B.cCodCur = A.cCodCur
                      ORDER BY cDescri"""
       else:
          lcDescri = '%' + self.paData['CDESCRI'].strip() + '%'
          lcSql = f"""SELECT A.cCodCur, A.cEstado, B.cDescri FROM A21MCUR A
                      INNER JOIN A02MCUR B ON B.cCodCur = A.cCodCur
                      WHERE B.cDescri LIKE '{lcDescri}' ORDER BY cDescri"""
       #print(lcSql)
       print('CODIGO  EST DESCRIPCION')
       print('----------------------------------------------------------------')
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          print(laTmp[0] + ' ' + laTmp[1] + '   ' + laTmp[2])
          laTmp = self.loSql.fetch(R1)
       print('----------------------------------------------------------------')
       self.paData = []   
       return True   

   # ----------------------------------------------------------------
   # Actualizar asistencia cursos clinica
   # 2023-01-20 FPM Creacion
   # ----------------------------------------------------------------
   def omActualizarCursosClinica(self):
       llOk = self.mxValParamActualizarCursosClinica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect(7)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxValidarActualizarCursosClinica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxActualizarCursosClinica()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamActualizarCursosClinica(self):
       if not 'CNRODNI' in self.paData:
          self.pcError = 'DNI NO DEFINIDO'
          return False
       elif not 'CCODUSU' in self.paData:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO'
          return False
       elif not 'ACURSOS' in self.paData:
          self.pcError = 'ARREGLO DE CURSOS NO DEFINIDO'
          return False
       return True

   def mxValidarDocenteClinica(self):
       lcSql = f"SELECT cEstado, mDatos FROM A21MMED WHERE cNroDni = '{self.paData['CNRODNI']}'"
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp == None or len(laTmp) == 0 or laTmp[0] == None:
          self.pcError = f"DNI {self.paData['CNRODNI']} NO EXISTE COMO DOCENTE"
          return False
       elif laTmp[0] != 'A':
          self.pcError = f"DNI {self.paData['CNRODNI']} NO ESTA ACTIVO"
          return False
       try:   
          self.laData = json.loads(laTmp[1])
       except:
          self.laData = {'ACURSOS': '', 'ACENSAL': ''}
       return True   

   def mxValidarActualizarCursosClinica(self):
       llOk = self.mxValidarDocenteClinica()
       if not llOk:
          return False
       for lcCodCur in self.paData['ACURSOS']:
           if lcCodCur[:2] != '70':
              self.pcError = f"ASIGNATURA {lcCodCur} NO ES DE MEDICINA"
              return False
           lcSql = f"SELECT cEstado FROM A21MCUR WHERE cCodCur = '{lcCodCur}'"
           #print(lcCodCur)
           #print(lcSql)
           R1 = self.loSql.omExecRS(lcSql)
           laTmp = self.loSql.fetch(R1)
           if laTmp == None or len(laTmp) == 0 or laTmp[0] == None:
              self.pcError = f"ASIGNATURA {lcCodCur} INVALIDO O NO EXISTE"
              return False
           elif laTmp[0] != 'A':
              self.pcError = f"ASIGNATURA [{lcCodCur}] NO ESTA ACTIVA"
              return False
       self.laData['ACURSOS'] = self.paData['ACURSOS']       
       return True

   def mxActualizarCursosClinica(self):
       lmDatos = json.dumps(self.laData)
       lcSql = f"UPDATE A21MMED SET mDatos = '{lmDatos}' WHERE cNroDni = '{self.paData['CNRODNI']}'"
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = "ERROR AL ACTUALIZAR CURSOS DE CLINICA"
          return False
       return True   

   # ----------------------------------------------------------------
   # Actualizar centros de salud de clinica
   # 2023-01-20 FPM Creacion
   # ----------------------------------------------------------------
   def omActualizarCentrosSalud(self):
       llOk = self.mxValParamActualizarCentrosSalud()
       if not llOk:
          return False
       llOk = self.loSql.omConnect(7)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxValidarActualizarCentrosSalud()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxActualizarCentrosSalud()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamActualizarCentrosSalud(self):
       if not 'CNRODNI' in self.paData:
          self.pcError = 'DNI NO DEFINIDO'
          return False
       elif not 'CCODUSU' in self.paData:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO'
          return False
       elif not 'ACENSAL' in self.paData:
          self.pcError = 'ARREGLO DE CENTROS DE SALUD NO DEFINIDO'
          return False
       return True

   def mxValidarActualizarCentrosSalud(self):
       llOk = self.mxValidarDocenteClinica()
       if not llOk:
          return False
       for lcIdCent in self.paData['ACENSAL']:
           lcSql = f"SELECT cEstado FROM A21TCSA WHERE cIdCent = '{lcIdCent}'"
           #print(lcCodCur)
           #print(lcSql)
           R1 = self.loSql.omExecRS(lcSql)
           laTmp = self.loSql.fetch(R1)
           if laTmp == None or len(laTmp) == 0 or laTmp[0] == None:
              self.pcError = f"CENTRO DE SALUD {lcIdCent} INVALIDO O NO EXISTE"
              return False
           elif laTmp[0] != 'A':
              self.pcError = f"CENTRO DE SALUD [{lcIdCent}] NO ESTA ACTIVO"
              return False
       self.laData['ACENSAL'] = self.paData['ACENSAL']       
       return True

   def mxActualizarCentrosSalud(self):
       lmDatos = json.dumps(self.laData)
       lcSql = f"UPDATE A21MMED SET mDatos = '{lmDatos}' WHERE cNroDni = '{self.paData['CNRODNI']}'"
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = "ERROR AL ACTUALIZAR CENTROS DE SALUD"
          return False
       return True   

   # ----------------------------------------------------------------
   # Deudores de postgrado
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omDeudoresPostgrado(self):
       llOk = self.mxValParamDeudoresPostgrado()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxDeudoresPostgrado()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamDeudoresPostgrado(self):
       return True

   def mxDeudoresPostgrado(self):
       loFile = open('./DeudoresPostgrado.csv', 'w')
       loFile.write('CCODALU;CNOMBRE;UNIDAD ACADEMICA;SALDO\n')
       lcSql = """SELECT R.cCodalu, A.cNombre, A.cUniaca, A.cNomuni, SUM(R.nSaldo) AS nDeuda FROM  (
                  SELECT cCodalu, cProyec, SUM(nDebito - nAbono) AS nSaldo
                  FROM C10DCCT
                  WHERE cEstado = 'A'
                  AND LEFT(cProyec, 4) >= '2010'
                  AND LEFT(cDocume, 2) = '02'
                  GROUP BY cCodalu, cProyec
                  HAVING SUM(nDebito - nAbono) > 0.0) R
                  LEFT JOIN V_A01MALU A ON A.cCodalu = R.cCodalu
                  GROUP BY R.cCodalu, A.cNombre, A.cUniaca, A.cNomuni
                  ORDER BY R.cCodalu"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          lcNomUni = laTmp[2] + ' - ' + laTmp[3].replace(';', ' ')
          lcLinea = laTmp[0] + ';' + laTmp[1] + ';' + lcNomUni + ';' + str(laTmp[4]) + '\n'
          loFile.write(lcLinea)
          laTmp = self.loSql.fetch(R1)
       loFile.close()
       self.paData = {'OK': 'OK'}     
       return True

   # ----------------------------------------------------------------
   # Ejecucion presupuestal
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omEjecucionPresupuestal(self):
       llOk = self.mxValParamEjecucionPresupuestal()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerEjecucionPresupuestal()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxEjecucionPresupuestal()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamEjecucionPresupuestal(self):
       if not 'CUNIACA' in self.paData:
          self.pcError = 'UNIDAD ACADEMICA NO DEFINIDA O INVALIDA'
          return False
       elif not 'CPERIOD' in self.paData:
          self.pcError = 'PERIODO NO DEFINIDO O INVALIDO'
          return False

   def mxVerEjecucionPresupuestal(self):
       lcSql = f"SELECT cNomUni FROM S01TUAC WHERE cUniAca = '{self.paData['CUNIACA']}'"
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       if laTmp == None or len(laTmp) == 0 or laTmp[0] == None:
          self.pcError = "UNIDAD ACADÉMICA [{self.paData['CUNIACA']}] NO EXISTE"
          return False
       return True   

   def mxEjecucionPresupuestal(self):
       llOk = self.mxPlaneamientoPresupuestal()
       if not llOk:
          return False
       llOk = self.mxIngresosCaja()
       if not llOk:
          return False
       llOk = self.mxEgresosCaja()
       return llOk

   def mxPlaneamientoPresupuestal(self):
       return True

   '''
   def mxIngresosCaja(self):
       # Pensiones
       lcSql = f""



       loFile = open('./DeudoresPostgrado.csv', 'w')
       loFile.write('CCODALU;CNOMBRE;UNIDAD ACADEMICA;SALDO\n')
       lcSql = """SELECT R.cCodalu, A.cNombre, A.cUniaca, A.cNomuni, SUM(R.nSaldo) AS nDeuda FROM  (
                  SELECT cCodalu, cProyec, SUM(nDebito - nAbono) AS nSaldo
                  FROM C10DCCT
                  WHERE cEstado = 'A'
                  AND LEFT(cProyec, 4) >= '2010'
                  AND LEFT(cDocume, 2) = '02'
                  GROUP BY cCodalu, cProyec
                  HAVING SUM(nDebito - nAbono) > 0.0) R
                  LEFT JOIN V_A01MALU A ON A.cCodalu = R.cCodalu
                  GROUP BY R.cCodalu, A.cNombre, A.cUniaca, A.cNomuni
                  ORDER BY R.cCodalu"""
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          lcNomUni = laTmp[2] + ' - ' + laTmp[3].replace(';', ' ')
          lcLinea = laTmp[0] + ';' + laTmp[1] + ';' + lcNomUni + ';' + str(laTmp[4]) + '\n'
          loFile.write(lcLinea)
          laTmp = self.loSql.fetch(R1)
       loFile.close()
       self.paData = {'OK': 'OK'}     
       return True
   '''

   # ----------------------------------------------------------------
   # Factores para distribuir ingresos de la ejecucion presupuestal
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omFactoresEjecucionPresupuestal(self):
       llOk = self.mxValParamFactoresEjecucionPresupuestal()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerFactoresEjecucionPresupuestal()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxFactoresEjecucionPresupuestal()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamFactoresEjecucionPresupuestal(self):
       if not 'CNRODNI' in self.paData or not re.match('^[0-9]{8}$', self.paData['CNRODNI']):
          self.pcError = 'DNI NO DEFINIDO O INVALIDO'
          return False
       return True

   def mxVerFactoresEjecucionPresupuestal(self):
       if not self.paData['CNRODNI'] in ['70766663', '76034593']:
          self.pcError = 'USUARIO NO TIENE PERMISOS'
          return False
       return True

   def mxFactoresEjecucionPresupuestal(self):
       laDatos = []
       laDatos.append({'CUNICA': '70', 'CNOMUNI': 'EPIS', 'NCANALU': 600, 'NCANTOT': 20000})
       laDatos.append({'CUNICA': '71', 'CNOMUNI': 'UNIDAD ACADEMICA 71', 'NCANALU': 1600, 'NCANTOT': 20000})
       laDatos.append({'CUNICA': '72', 'CNOMUNI': 'UNIDAD ACADEMICA 72', 'NCANALU': 6000, 'NCANTOT': 20000})
       laDatos.append({'CUNICA': '73', 'CNOMUNI': 'UNIDAD ACADEMICA 73', 'NCANALU': 6500, 'NCANTOT': 20000})
       laDatos.append({'CUNICA': '74', 'CNOMUNI': 'UNIDAD ACADEMICA 74', 'NCANALU': 7500, 'NCANTOT': 20000})
       print(laDatos)
       self.paDatos = laDatos
       return True

   # ----------------------------------------------------------------
   # Init para dashboard de auditoria academica
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omInitAuditoriaAcademica(self):
       llOk = self.mxValParamInitAuditoriaAcademica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxInitAuditoriaAcademica()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamInitAuditoriaAcademica(self):
       # OJOFPM FALTA VALIDAR
       return True

   def mxInitAuditoriaAcademica(self):
       laDatos = []
       laDatos.append({'CUNIACA': '*', 'CNOMUNI': '*** TODOS ***'})
       lcSql = "SELECT cUniAca, cNomUni FROM S01TUAC WHERE cNivel = '01' AND cEstado = 'A' ORDER BY cNomUni"
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          laDatos.append({'CUNIACA': laTmp[0], 'CNOMUNI': laTmp[1]})
          laTmp = self.loSql.fetch(R1)
       if len(laDatos) == 0:
          self.pcError = 'NO HAY UNIDADES ACADÉMICAS PARA MOSTRAR'
          return False
       self.paDatos = laDatos   
       return True

   # ----------------------------------------------------------------
   # Init para dashboard de auditoria academica
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omProyectoTesis(self):
       llOk = self.mxValParamProyectoTesis()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxProyectoTesis()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamProyectoTesis(self):
       # OJOFPM FALTA VALIDAR
       return True

   def mxProyectoTesis(self):
       loDate = CDate()
       ldToday = date.today()
       ldToday = ldToday.strftime("%Y-%m-%d")
       laData = {'F': 0, 'O': 0, 'P': 0}
       if self.paData['CUNIACA'] == '*':
          lcSql = """SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                     FROM T01MTES A
                     INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'A'
                     WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X'"""
       else:
          lcSql = f"""SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                      FROM T01MTES A
                      INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'A'
                      WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X' AND A.cUniAca = '{self.paData['CUNIACA']}'"""
       i = 0
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          i += 1
          if loDate.diff(ldToday, laTmp[0]) > 7 and laTmp[1] == 'P':
             laData['F'] += 1
          elif loDate.diff(ldToday, laTmp[0]) > 7 and laTmp[1] == 'O':
             laData['O'] += 1
          else:   
             laData['P'] += 1
          laTmp = self.loSql.fetch(R1)
       if i == 0:
          self.pcError = 'NO HAY DATOS PARA MOSTRAR'
          return False
       self.paDatos.append({'CINDICA': 'FALTANTES', 'NCANTID': laData['F']})   
       self.paDatos.append({'CINDICA': 'OBSERVADOS', 'NCANTID': laData['O']})   
       self.paDatos.append({'CINDICA': 'PROCESO', 'NCANTID': laData['P']})   
       return True

   # ----------------------------------------------------------------
   # Init para dashboard de auditoria academica
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omBorradorTesis(self):
       llOk = self.mxValParamBorradorTesis()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxBorradorTesis()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamBorradorTesis(self):
       # OJOFPM FALTA VALIDAR
       return True

   def mxBorradorTesis(self):
       loDate = CDate()
       ldToday = date.today()
       ldToday = ldToday.strftime("%Y-%m-%d")
       laData = {'F': 0, 'O': 0, 'P': 0}
       if self.paData['CUNIACA'] == '*':
          lcSql = """SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                     FROM T01MTES A
                     INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'C'
                     WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X'"""
       else:
          lcSql = f"""SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                      FROM T01MTES A
                      INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'C'
                      WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X' AND A.cUniAca = '{self.paData['CUNIACA']}'"""
       i = 0
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          i += 1
          if loDate.diff(ldToday, laTmp[0]) > 7 and laTmp[1] == 'P':
             laData['F'] += 1
          elif loDate.diff(ldToday, laTmp[0]) > 7 and laTmp[1] == 'O':
             laData['O'] += 1
          else:   
             laData['P'] += 1
          laTmp = self.loSql.fetch(R1)
       if i == 0:
          self.pcError = 'NO HAY DATOS PARA MOSTRAR'
          return False
       self.paDatos.append({'CINDICA': 'FALTANTES', 'NCANTID': laData['F']})   
       self.paDatos.append({'CINDICA': 'OBSERVADOS', 'NCANTID': laData['O']})   
       self.paDatos.append({'CINDICA': 'PROCESO', 'NCANTID': laData['P']})   
       return True

   # ----------------------------------------------------------------
   # Init para dashboard de auditoria academica
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omAsesorTesis(self):
       llOk = self.mxValParamAsesorTesis()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxAsesorTesis()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamAsesorTesis(self):
       # OJOFPM FALTA VALIDAR
       return True

   def mxAsesorTesis(self):
       loDate = CDate()
       ldToday = date.today()
       ldToday = ldToday.strftime("%Y-%m-%d")
       laData = {'F': 0, 'O': 0, 'P': 0}
       if self.paData['CUNIACA'] == '*':
          lcSql = """SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                     FROM T01MTES A
                     INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'B'
                     WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X'"""
       else:
          lcSql = f"""SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                      FROM T01MTES A
                      INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'B'
                      WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X' AND A.cUniAca = '{self.paData['CUNIACA']}'"""
       i = 0
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          i += 1
          if loDate.diff(ldToday, laTmp[0]) > 60 and laTmp[1] == 'P':
             laData['F'] += 1
          elif loDate.diff(ldToday, laTmp[0]) > 60 and laTmp[1] == 'O':
             laData['O'] += 1
          else:   
             laData['P'] += 1
          laTmp = self.loSql.fetch(R1)
       if i == 0:
          self.pcError = 'NO HAY DATOS PARA MOSTRAR'
          return False
       self.paDatos.append({'CINDICA': 'FALTANTES', 'NCANTID': laData['F']})   
       self.paDatos.append({'CINDICA': 'OBSERVADOS', 'NCANTID': laData['O']})   
       self.paDatos.append({'CINDICA': 'PROCESO', 'NCANTID': laData['P']})   
       return True

   # ----------------------------------------------------------------
   # Init para dashboard de auditoria academica
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omJuradosSustentacion(self):
       llOk = self.mxValParamJuradosSustentacion()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxJuradosSustentacion()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamJuradosSustentacion(self):
       # OJOFPM FALTA VALIDAR
       return True

   def mxJuradosSustentacion(self):
       loDate = CDate()
       ldToday = date.today()
       ldToday = ldToday.strftime("%Y-%m-%d")
       laData = {'F': 0, 'O': 0, 'P': 0}
       if self.paData['CUNIACA'] == '*':
          lcSql = """SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                     FROM T01MTES A
                     INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'D'
                     WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X'"""
       else:
          lcSql = f"""SELECT TO_CHAR(D.tDecret,'YYYY-MM-DD'), D.cResult
                      FROM T01MTES A
                      INNER JOIN T01DDOC D ON D.cIdTesi = A.cIdTesi AND D.cCatego = 'D'
                      WHERE D.tDictam ISNULL AND D.cResult IN ('P', 'O') AND A.cEstado != 'X' AND D.cEstado != 'X' AND A.cUniAca = '{self.paData['CUNIACA']}'"""
       i = 0
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          i += 1
          if loDate.diff(ldToday, laTmp[0]) > 7 and laTmp[1] == 'P':
             laData['F'] += 1
          elif loDate.diff(ldToday, laTmp[0]) > 7 and laTmp[1] == 'O':
             laData['O'] += 1
          else:   
             laData['P'] += 1
          laTmp = self.loSql.fetch(R1)
       if i == 0:
          self.pcError = 'NO HAY DATOS PARA MOSTRAR'
          return False
       self.paDatos.append({'CINDICA': 'FALTANTES', 'NCANTID': laData['F']})   
       self.paDatos.append({'CINDICA': 'OBSERVADOS', 'NCANTID': laData['O']})   
       self.paDatos.append({'CINDICA': 'PROCESO', 'NCANTID': laData['P']})   
       return True

   # ----------------------------------------------------------------
   # Init para dashboard de auditoria academica
   # 2023-03-20 FPM Creacion
   # ----------------------------------------------------------------
   def omConvalidaciones(self):
       llOk = self.mxValParamConvalidaciones()
       if not llOk:
          return False
       llOk = self.loSql.omConnect(2)
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxConvalidaciones()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamConvalidaciones(self):
       # OJOFPM FALTA VALIDAR
       return True

   def mxConvalidaciones(self):
       loDate = CDate()
       ldToday = date.today().strftime("%Y-%m-%d")
       #ldToday = ldToday.strftime("%Y-%m-%d")
       #ldToday = date.today()
       #ldToday = ldToday.strftime("%Y-%m-%d")
       laData = {'A': 0, 'F': 0, 'P': 0}
       if self.paData['CUNIACA'] == '*':
          lcSql = """SELECT TO_CHAR(A.tGenera,'YYYY-MM-DD'), TO_CHAR(C.tAsigna,'YYYY-MM-DD'),D.cUniAca FROM B06MCNV A
                     INNER JOIN B06DCNV C ON C.cIdConv=A.cIdConv 
                     INNER JOIN V_A01MALU D ON D.cCodAlu=A.cCodAlu
                     WHERE C.cEstado ='P'"""
       else:
          lcSql = f"""SELECT TO_CHAR(A.tGenera,'YYYY-MM-DD'), TO_CHAR(C.tAsigna,'YYYY-MM-DD'),D.cUniAca FROM B06MCNV A
                     INNER JOIN B06DCNV C ON C.cIdConv=A.cIdConv
                     INNER JOIN V_A01MALU D ON D.cCodAlu=A.cCodAlu
                     WHERE C.cEstado ='P' AND D.cUniAca='{self.paData['CUNIACA']}'"""
       #print(lcSql)
       i = 0
       R1 = self.loSql.omExecRS(lcSql)
       laTmp = self.loSql.fetch(R1)
       while laTmp != None:
          i += 1
          if laTmp[1] == None and loDate.diff(ldToday, laTmp[0]) > 7:
             laData['A'] += 1
          elif loDate.diff(ldToday, laTmp[1]) > 7:
             laData['F'] += 1
          else:   
             laData['P'] += 1
          laTmp = self.loSql.fetch(R1)
       if i == 0:
          self.pcError = 'NO HAY DATOS PARA MOSTRAR'
          return False
       self.paDatos.append({'CINDICA': 'FALTANTES', 'NCANTID': laData['F']})   
       self.paDatos.append({'CINDICA': 'POR ASIGNAR', 'NCANTID': laData['A']})   
       self.paDatos.append({'CINDICA': 'PROCESO', 'NCANTID': laData['P']})   
       return True

# ---------------------------------------------
# Funcion principal para ser llamado desde php
# ---------------------------------------------
def main(p_cParam):
    laData = json.loads(p_cParam)
    #print(laData)
    if 'ID' not in laData:
       print('{"ERROR": "NO HAY ID DE PROCESO"}')
       return
    elif laData['ID'] == 'ERP0001':
       lo = CConsultasVarias()
       llOk = lo.omEmailDeudaIdiomas()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0002':
       lo = CConsultasVarias()
       llOk = lo.omCompararS01MPER()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0003':
       lo = CConsultasVarias()
       llOk = lo.omActualizarS01MPER()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0004':
       lo = CConsultasVarias()
       llOk = lo.omValidarMora()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0005':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omLoginUsuarioUCSM()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0006':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omEliminarCodigo12()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1210':
       lo = CConsultasVarias()
       llOk = lo.omVerificarMatriculadosDNI()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1220':
       lo = CConsultasVarias()
       llOk = lo.omPagadosCentroIdiomas()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1230':
       lo = CConsultasVarias()
       llOk = lo.omActualizarCelular()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1240':
       lo = CConsultasVarias()
       llOk = lo.omVerificarUsuariosDNI()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1250':
       lo = CConsultasVarias()
       llOk = lo.omEstudiantesDNIError()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1260':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omConsultarDeuda()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP1270':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omEstudiantesDeuda()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0010':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omCobranzasCierre2022()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0011':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omCobranzas2023()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0012':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omCentrosSalud()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0013':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omCursosClinica()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0014':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omActualizarCursosClinica()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0015':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omActualizarCentrosSalud()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0016':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omDeudoresPostgrado()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'ERP0017':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omFactoresEjecucionPresupuestal()
       if llOk:
          #print(lo.paData)
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0018':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omConsultarProyectosInvestigacion()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0019':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omDetalleProyectosInvestigacion()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0020':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omInitAuditoriaAcademica()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0021':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omProyectoTesis()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0022':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omBorradorTesis()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0023':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omAsesorTesis()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0024':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omJuradosSustentacion()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    elif laData['ID'] == 'ERP0025':
       lo = CConsultasVarias()
       lo.paData = laData
       llOk = lo.omConvalidaciones()
       if llOk:
          print(json.dumps(lo.paDatos))
          return
    else:
       laData = {'ERROR': 'ID [%s] NO DEFINIDA'%(laData['ID'])}
       print(json.dumps(laData))
       return
    laData = {'ERROR': lo.pcError}
    print(json.dumps(laData))
    return

if __name__ == "__main__":
   main(sys.argv[1])
   
'''
python3 CConsultasVarias.py '{"ID": "ERP1210"}'
python3 CConsultasVarias.py '{"ID": "ERP1220"}'      
python3 CConsultasVarias.py '{"ID": "ERP1230"}'      
python3 CConsultasVarias.py '{"ID": "ERP1240"}'      
python3 CConsultasVarias.py '{"ID": "ERP1250"}'      
python3 CConsultasVarias.py '{"ID": "ERP1250", "CCODIGO": "29244573", "CFLAG": "N"}'      
python3 CConsultasVarias.py '{"ID": "ERP1270"}'      
python3 CConsultasVarias.py '{"ID": "ERP0001"}'      
python3 CConsultasVarias.py '{"ID": "ERP0002"}'      
python3 CConsultasVarias.py '{"ID": "ERP0003"}'
python3 CConsultasVarias.py '{"ID": "ERP0004"}'
python3 CConsultasVarias.py '{"ID": "ERP0005", "CNRODNI": "29244573", "CCLAVE": "a6e138182af91d480747a59a294bac5911aaa8549ba26098528ff99955b737b0102058db42dda5a5cefdacd4c27a207d8f10e3c44c7f0274a5d596dc2e0ad0cd"}'
python3 CConsultasVarias.py '{"ID": "ERP0006"}'
python3 CConsultasVarias.py '{"ID": "ERP0011"}'
python3 CConsultasVarias.py '{"ID": "ERP0012", "CDESCRI": "CAMANA"}'
python3 CConsultasVarias.py '{"ID": "ERP0013", "CDESCRI": "PEDIATRIA"}'
python3 CConsultasVarias.py '{"ID": "ERP0014", "CNRODNI": "09165385", "CCODUSU": "1221", "ACURSOS": ["7007134", "7007194", "700719W"]}'
python3 CConsultasVarias.py '{"ID": "ERP0015", "CNRODNI": "09165385", "CCODUSU": "1221", "ACENSAL": ["001", "003"]}'
python3 CConsultasVarias.py '{"ID": "ERP0016"}'
python3 CConsultasVarias.py '{"ID": "ERP0017", "CNRODNI": "09165385"}'
python3 CConsultasVarias.py '{"ID": "ERP0018", "CYEAR": "2022"}'
python3 CConsultasVarias.py '{"ID": "ERP0019", "CYEAR": "2022", "CCTACNT": "469910230"}' 
python3 CConsultasVarias.py '{"ID": "ERP0020"}' 
python3 CConsultasVarias.py '{"ID": "ERP0021", "CUNIACA": "*"}' 
python3 CConsultasVarias.py '{"ID": "ERP0021", "CUNIACA": "71"}' 
python3 CConsultasVarias.py '{"ID": "ERP0022", "CUNIACA": "71"}' 
python3 CConsultasVarias.py '{"ID": "ERP0023", "CUNIACA": "71"}' 
python3 CConsultasVarias.py '{"ID": "ERP0024", "CUNIACA": "71"}' 
python3 CConsultasVarias.py '{"ID": "ERP0025", "CUNIACA": "*"}' 


cd /var/www/html/WSERP$
// Consulta centros de salud
python3 CConsultasVarias.py '{"ID": "ERP0012", "CDESCRI": "CAMANA"}'
// Consulta asignaturas
python3 CConsultasVarias.py '{"ID": "ERP0013", "CDESCRI": "PEDIATRIA"}'
// Graba asignaturas
python3 CConsultasVarias.py '{"ID": "ERP0014", "CNRODNI": "09165385", "CCODUSU": "1221", "ACURSOS": ["7007134", "7007194", "700719W"]}'
// Graba centros de salud
python3 CConsultasVarias.py '{"ID": "ERP0015", "CNRODNI": "09165385", "CCODUSU": "1221", "ACENSAL": ["001", "003"]}'
'''
   
'''
python3 CConsultasVarias.py '{"ID": "ERP1210"}'
python3 CConsultasVarias.py '{"ID": "ERP1220"}'      
python3 CConsultasVarias.py '{"ID": "ERP1230"}'      
python3 CConsultasVarias.py '{"ID": "ERP1240"}'      
python3 CConsultasVarias.py '{"ID": "ERP1250"}'      
python3 CConsultasVarias.py '{"ID": "ERP1250", "CCODIGO": "29244573", "CFLAG": "N"}'      
python3 CConsultasVarias.py '{"ID": "ERP1270"}'      
python3 CConsultasVarias.py '{"ID": "ERP0001"}'      
python3 CConsultasVarias.py '{"ID": "ERP0002"}'      
python3 CConsultasVarias.py '{"ID": "ERP0003"}'
python3 CConsultasVarias.py '{"ID": "ERP0004"}'
python3 CConsultasVarias.py '{"ID": "ERP0005", "CNRODNI": "29244573", "CCLAVE": "a6e138182af91d480747a59a294bac5911aaa8549ba26098528ff99955b737b0102058db42dda5a5cefdacd4c27a207d8f10e3c44c7f0274a5d596dc2e0ad0cd"}'
python3 CConsultasVarias.py '{"ID": "ERP0006"}'
python3 CConsultasVarias.py '{"ID": "ERP0011"}'
python3 CConsultasVarias.py '{"ID": "ERP0012", "CDESCRI": "CAMANA"}'
python3 CConsultasVarias.py '{"ID": "ERP0013", "CDESCRI": "PEDIATRIA"}'
python3 CConsultasVarias.py '{"ID": "ERP0014", "CNRODNI": "09165385", "CCODUSU": "1221", "ACURSOS": ["7007134", "7007194", "700719W"]}'
python3 CConsultasVarias.py '{"ID": "ERP0015", "CNRODNI": "09165385", "CCODUSU": "1221", "ACENSAL": ["001", "003"]}'


'''

