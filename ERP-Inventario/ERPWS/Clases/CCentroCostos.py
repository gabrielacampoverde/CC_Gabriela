#!/usr/bin/env python
#-*- coding: utf-8 -*-
import sys
import json
import random
import psycopg2
from time import time
from datetime import datetime
from CSql import *
from CBase import *
# from CExcel import *
# from openpyxl.styles import PatternFill

# --------------------------------------------------
# Clase para el mantenimiento de un centro de costo
# 2020-12-18 BOL Creacion
# --------------------------------------------------
class CCentroCostos(CBase):
    
   def __init__(self):
       self.paData   = None
       self.paDatos  = []
       self.laData   = []
       self.laDatos  = []
       self.laDatEst = []
       self.loBook   = None
       self.loSheet  = None
       self.pcFilXls = ''
       # self.loXls    = CXls()
       self.loSql    = CSql()
       
   def mxValParam(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CCENCOS' in self.paData or len(self.paData['CCENCOS']) != 3:
          self.pcError = 'CENTRO DE COSTO NO DEFINIDO O INVALIDO'
          return False
       return True
 
   def mxValParamUsuario(self, p_cModulo = '00'):
       '''
       # Valida que el modulo corresponda 
       lcSql = """SELECT cEstado FROM S01PCCO WHERE cCencos = '%s' AND 
                  cCodUsu = '%s' AND cModulo = '%s'"""%(self.paData['CCENCOS'],self.paData['CUSUCOD'], p_cModulo)
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'USUARIO NO TIENE PERMISO PARA MODULO'
          return False
       elif R1[0][0] != 'A':
          self.pcError = 'USUARIO NO TIENE PERMISO ACTIVO PARA MODULO'
          return False
       '''
       return True
     
   # ------------------------------------------------------------------
   # Trae siguiente correlativo para clase de CC.
   # 2021-03-22 BOL Creacion
   # ------------------------------------------------------------------
   def omCorrelativoClase(self):
       llOk = self.mxValCorrelativoClase()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxCorrelativoClase()
       self.loSql.omDisconnect()
       return llOk
       
   def mxValCorrelativoClase(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CCLASE' in self.paData:
          self.pcError = 'CENTRO DE COSTO SUPERIOR NO DEFINIDO'
          return False
       return True
   
   def mxCorrelativoClase(self):
       lcClase = ''
       lnLongitud = 2
       if len(self.paData['CCLASE']) >= 6:
          lnLongitud = 3       
       lcSql = "SELECT TRIM(MAX(cClase)) FROM S01TCCO WHERE cClase LIKE '%s' AND LENGTH(TRIM(cClase)) = %s"%(self.paData['CCLASE']+'%', len(self.paData['CCLASE']) + lnLongitud)
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or R1[0][0] == None:
          # Siguiente clase seria el primero de la lista
          lcTmp = '001'
          self.paData['CSIGCLA'] = self.paData['CCLASE'] + lcTmp[-lnLongitud:]
          return True
       lcClaMax = R1[0][0]
       lcNivAct = lcClaMax[-lnLongitud:]
       lcNivAct = str(int(lcNivAct) + 1)
       if len(lcNivAct) > lnLongitud:
          self.pcError = 'CORRELATIVO PARA EL NIVEL INGRESADO ESTA COMPLETO, COMUNICARSE CON SISTEMAS'
          return False
       lcTmp = '000' + lcNivAct
       self.paData['CSIGCLA'] = self.paData['CCLASE'] + lcTmp[-lnLongitud:]
       return True
       
   # ------------------------------------------------------------------
   # Graba Centro de costos
   # 2020-12-18 BOL Creacion
   # ------------------------------------------------------------------
   def omGrabaMntoCenCos(self):
       llOk = self.mxValDatosMntoCenCos()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxValNivelCenCos()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxGrabaMntCenCos()
       if not llOk:
          return False
       self.loSql.omDisconnect()
       return llOk

   def mxValDatosMntoCenCos(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CDESCRI' in self.paData:
          self.pcError = 'DESCRIPCION DE CENTRO DE COSTOS NO DEFINIDO'
          return False
       elif not 'CUNIACA' in self.paData:
          self.pcError = 'UNIDAD ACADEMICA NO DEFINIDO'
          return False
       elif not 'CESTPRE' in self.paData:
          self.pcError = 'ESTRUCTURA PRESUPUESTAL NO DEFINIDO'
          return False
       elif not 'CTIPEST' in self.paData:
          self.pcError = 'TIPO DE ESTRUCTURA NO DEFINIDO'
          return False
       elif not 'CTIPO' in self.paData:
          self.pcError = 'TIPO NO DEFINIDO'
          return False
       elif not 'CAFECTA' in self.paData:
          self.pcError = 'CENTRO DE COSTOS AFECTACION IGV NO DEFINIDO'
          return False
       elif not 'CTIPDES' in self.paData:
          self.pcError = 'TIPO DE DISTRIBUCION NO DEFINIDO'
          return False
       elif not 'CESTADO' in self.paData:
          self.pcError = 'CENTRO DE COSTOS ESTADO NO DEFINIDO'
          return False
       return True
   
   def mxValNivelCenCos(self):
       lnlongitud = len(self.paData['CCLASE'])
       lcClase = self.paData['CCLASE']
       if self.paData['CCENCOS'] == '*':
          #valida que no se repita nivel
          lcSql = "SELECT cClase FROM S01TCCO WHERE cClase = '%s'"%(lcClase)
          R1 = self.loSql.omExecRS(lcSql)
          if len(R1) > 0:
             self.pcError = 'CLASE %s YA ESTA REGISTRADO'%(lcClase)
             return False
          #valida que exista nivel superior
          if lnlongitud > 1 :
             lcClase = self.mxNivelSuperior(lcClase)
             lcSql = "SELECT cClase FROM S01TCCO WHERE cClase = '%s'"%(lcClase)
             R1 = self.loSql.omExecRS(lcSql)
             if len(R1) == 0:
                self.pcError = 'DEBE REGISTRAR NIVEL SUPERIOR [%s] ACTIVO EN CENTRO DE COSTOS'%(lcClase)
                return False 
          #valida que el nivel sea el siguiente correlativo
          if lnlongitud > 1 :
             lcClase = self.paData['CCLASE']
             lnLongitud = len(lcClase)
             lcClase = self.mxNivelSuperior(lcClase)
             lcClase = lcClase + '%'
             lcSql = "SELECT MAX(cClase) FROM S01TCCO WHERE cClase LIKE '%s' AND LENGTH(TRIM(cClase)) = %s"%(lcClase,lnLongitud)
             R1 = self.loSql.omExecRS(lcSql)
             if R1[0][0] != None:
                lcUltNiv = R1[0][0]
                lnLngNiv = len(lcUltNiv) - (len(lcClase)-1)
                lcNivAct = lcUltNiv[-lnLngNiv:]
                lnNivAct = len(lcNivAct) 
                if lnNivAct > 1:
                   lcNivAct = str(int(lcNivAct) + 1)
                   if len(lcNivAct) > lnNivAct:
                      self.pcError = 'CORRELATIVO PARA EL NIVEL INGRESADO ESTA COMPLETO, COMUNICARSE CON SISTEMAS'
                      return False
                   else:
                      lcTemp = '000' + lcNivAct 
                      lcUltNiv = lcUltNiv[:-lnNivAct] + lcTemp[-lnNivAct:]
                else:   
                   lcNivAct = chr(ord(lcNivAct) + 1)
                   if ord(lcNivAct) > 90:
                      self.pcError = 'CORRELATIVO PARA EL NIVEL INGRESADO ESTA COMPLETO, COMUNICARSE CON SISTEMAS'
                      return False
                   else:
                      lcUltNiv = lcUltNiv[:-lnNivAct] + lcNivAct
                if lcUltNiv != self.paData['CCLASE']:
                   self.pcError = 'EL SIGUIENTE CORRELATIVO PARA NIVEL DEBE SER: %s'%(lcUltNiv)
                   return False
             else:
                #si no, seria primero de su nivel
                lcClase  = self.paData['CCLASE']
                lcUltDig = lcClase[-1:]
                if lcUltDig != '1':
                   if lcUltDig != 'A':
                      self.pcError = 'EL CORRELATIVO PARA NIVEL INGRESADO DEBE SER EL PRIMERO DE SU NIVEL [..01/..A]'
                      return False
          #valida que no tenga subniveles activos en caso de inactivar centro de costo
          if self.paData['CESTADO'] == 'I':
             lcClase = self.paData['CCLASE'] + '%'
             lcSql = "SELECT cClase FROM S01TCCO WHERE cClase like '%s' AND cEstado = 'A'"%(lcClase)  
             R1 = self.loSql.omExecRS(lcSql)
             if len(R1) > 1:
                self.pcError = 'PARA INACTIVAR CENTRO DE COSTOS DEBE INACTIVAR SUS SUBNIVELES CORRESPONDIENTES'
                return False
       return True
   
   def mxNivelSuperior(self, lcClase):
       lnlongitud = len(lcClase)
       if 65 <= ord(lcClase[-1:]) <= 90 :
          #si es letra el ultimo digito, retrocede 1 caracter para nivel superior
          lcClase = lcClase[:lnlongitud-1]
       else:
          if 10<= lnlongitud >= 9 :
             #si es ultimo nivel, retrocede 3 caracteres para nivel superior
             lcClase = lcClase[:lnlongitud-3]
          else:
             #retrocede 2 caracteres para nivel superior
             lcClase = lcClase[:lnlongitud-2]
       return lcClase
   
   def mxGrabaMntCenCos(self):
       if self.paData['CCENCOS'] != '*':
          #Actualiza centro de costo
          lcSql = """UPDATE S01TCCO SET cEstado = '%s', cUniAca = '%s',cDescri = '%s',cTipEst = '%s',
                   cEstPre = '%s',cAfecta = '%s',cTipo = '%s', cTipDes = '%s',cUsuCod = '%s', tModifi = NOW() 
                   WHERE cCencos = '%s'"""%(self.paData['CESTADO'],self.paData['CUNIACA'],self.paData['CDESCRI'],
                   self.paData['CTIPEST'],self.paData['CESTPRE'],self.paData['CAFECTA'],self.paData['CTIPO'],
                   self.paData['CTIPDES'],self.paData['CUSUCOD'],self.paData['CCENCOS'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL ACTUALIZAR CENTRO DE COSTOS'
             return False
       else:
          #Calcula codigo de centro de costo
          lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos != 'UNI' ORDER BY cCenCos DESC LIMIT 1";
          R1 = self.loSql.omExecRS(lcSql)
          if len(R1) == 0 :
             self.pcError = 'ERROR CON CORRELATIVO DE CENTRO DE COSTO'
             return False
          lcCodigo = R1[0][0]   
          lcCodigo = self.omCentroCosto(lcCodigo, '0')
          #Registra nuevo centro de costo
          lcSql = """INSERT INTO S01TCCO (cCencos, cDescri, cEstado, cUniAca, cClase, cNivel, cTipEst, cEstPre, cAfecta, cTipo, cCodAnt, cTipDes, cUsuCod,tModifi) 
                   VALUES('%s','%s','A','%s','%s','','%s','%s','%s','%s','','%s','%s',NOW())"""%(lcCodigo,self.paData['CDESCRI'],self.paData['CUNIACA'],
                   self.paData['CCLASE'],self.paData['CTIPEST'],self.paData['CESTPRE'],self.paData['CAFECTA'],self.paData['CTIPO'],self.paData['CTIPDES'],self.paData['CUSUCOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL INGRESAR CENTRO DE COSTOS'
             return False
          self.paData['CCENCOS'] = lcCodigo
       self.loSql.omCommit()
       return True

   # ------------------------------------------------------------------
   # Busca siguiente correlativo de Centro de Costo
   # 2020-12-17 FPM Creacion
   # ------------------------------------------------------------------
   def omCentroCosto(self, p_cCenCos, p_cFlag):
       lcCenCos = p_cCenCos
       for i in range (0, 3):
           if i == 0:
              lcDigito = lcCenCos[2:]
              lcDigito = self.mxCorrelativoCentroCosto(lcDigito)
              lcCenCos = lcCenCos[:2] + lcDigito
           elif i == 1:
              lcDigito = lcCenCos[1:-1]
              lcDigito = self.mxCorrelativoCentroCosto(lcDigito)
              lcCenCos = lcCenCos[:1] + lcDigito + lcCenCos[2:]
           else:
              lcDigito = lcCenCos[:1]
              lcDigito = self.mxCorrelativoCentroCosto(lcDigito)
              lcCenCos = lcDigito + lcCenCos[-2:]
           if lcDigito != '0':
              break
           if p_cFlag == '0':
              lcCenCos = 'UNJ' if lcCenCos == 'UNI' else lcCenCos
       return lcCenCos

   def mxCorrelativoCentroCosto(self, p_cDigito):
       if p_cDigito == '9':
          lcDigito = 'A'
       elif p_cDigito < '9':
          lcDigito = str(int(p_cDigito) + 1)
       elif p_cDigito < 'Z':
          lcDigito = chr(ord(p_cDigito) + 1)
       elif p_cDigito == 'Z':
          lcDigito = '0'
       return lcDigito
   
   # ------------------------------------------------------------------
   # Graba Centro de responsabilidad
   # 2020-01-18 BOL Creacion
   # ------------------------------------------------------------------
   def omGrabaMntoCenRes(self):
       llOk = self.mxValDatosMntoCenRes()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxGrabaMntCenRes()
       self.loSql.omDisconnect()
       return llOk

   def mxValDatosMntoCenRes(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CDESCRI' in self.paData:
          self.pcError = 'DESCRIPCION DE CENTRO DE COSTOS NO DEFINIDO'
          return False
       elif not 'CCENCOS' in self.paData:
          self.pcError = 'CENTRO DE COSTOS NO DEFINIDO'
          return False
       elif not 'CESTADO' in self.paData:
          self.pcError = 'ESTADO CENTRO DE RESPONSABILIDAD NO DEFINIDO'
          return False

       return True
   
   def mxGrabaMntCenRes(self):
       if self.paData['CCENRES'] != '*':
          #Actualiza centro de responsabilidad
          lcSql = """UPDATE S01TRES SET cEstado = '%s',cDescri = '%s',
                   cCenCos='%s', cResOld = '',cUsuCod = '%s', tModifi = NOW() 
                   WHERE cCenRes = '%s'"""%(self.paData['CESTADO'],
                   self.paData['CDESCRI'],self.paData['CCENCOS'],
                   self.paData['CUSUCOD'],self.paData['CCENRES'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL ACTUALIZAR CENTRO DE RESPONSABILIDAD'
             return False
       else:
          #Calcula codigo para centro de responsabilidad
          lcSql = "SELECT MAX(cCenRes) FROM S01TRES";
          R1 = self.loSql.omExecRS(lcSql)
          if len(R1) == 0 :
             self.pcError = 'ERROR CON CORRELATIVO DE CENTRO DE RESPONSABILIDAD'
             return False
          lcCodigo = R1[0][0]   
          lcCodigo = str(int(lcCodigo) + 1)
          lcCodigo = '00000' + lcCodigo 
          lcCodigo = lcCodigo [-5:]
          #Registra nuevo centro de responsabilidad
          lcSql = """INSERT INTO S01TRES (cCenRes,cCencos,cDescri,cEstado,cResOld,cUsuCod,tModifi) 
                   VALUES('%s','%s','%s','A','','%s',NOW())"""%(lcCodigo,self.paData['CCENCOS'],self.paData['CDESCRI'],
                   self.paData['CUSUCOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL INGRESAR CENTRO DE RESPONSABILIDAD'
             return False
          self.paData['CCENRES'] = lcCodigo
       self.loSql.omCommit()
       return True
    
   # ------------------------------------------------------------------
   # Habilita periodo para Distribucion de Centros de costo
   # 2020-01-25 BOL Creacion
   # ------------------------------------------------------------------
   def omHabilitarPeriodo(self):
       llOk = self.mxValHabilitaPeriodo()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxHabilitaPeriodo()
       self.loSql.omDisconnect()
       return llOk

   def mxValHabilitaPeriodo(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       return True
   
   def mxHabilitaPeriodo(self):
       # Habilita periodo en el S01MPRY
       llOk = self.mxHabilitaNuevoPeriodo()
       if not llOk:
          return False
       # Graba configuracion anterior
       llOk = self.mxCopiaConfiguracionAnterior()
       if not llOk:
          return False
       self.loSql.omCommit()
       return True
   
   def mxValidaPeriodo_OLD(self):
       if self.paData['CREINICIO'] == 'S':
          # Valida si periodo esta habilitado para reiniciar
          lcSql = "SELECT cEstCos FROM S01MPRY WHERE cProyec = '%s'"%(self.paData['CPERIOD'])
          R1 = self.loSql.omExecRS(lcSql)
          if R1[0][0] != 'A':
             self.pcError = 'PERIODO %s ESTA CERRADO. NO SE PUEDE REALIZAR NINGUN CAMBIO'%(self.paData['CPERIOD'])
             return False 
          # Reinicia periodo
          lcSql = "DELETE FROM D02DFCT WHERE cPeriod = '%s'"%(self.paData['CPERIOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL REINICIAR TABLA DE DISTRIBUCION'
             return False
          lcSql = "DELETE FROM D02DCOS WHERE cCodigo IN (SELECT cCodigo FROM D02MINE WHERE cPeriod = '%s')"%(self.paData['CPERIOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL REINICIAR TABLA DE CONFIGURACION DE CUENTAS'
             return False
          lcSql = "DELETE FROM D02MINE WHERE cPeriod = '%s'"%(self.paData['CPERIOD'])
          llOk = self.loSql.omExec(lcSql)

          if not llOk:
             self.pcError = 'ERROR AL REINICIAR LOG DE DISTRIBUCION'
             return False
       else:
          # Valida si periodo ya esta habilitado
          lcSql = "SELECT cEstCos FROM S01MPRY WHERE cProyec = '%s' AND cEstCos = 'A'"%(self.paData['CPERIOD'])
          R1 = self.loSql.omExecRS(lcSql)
          if len(R1) > 0:
             self.pcError = 'PERIODO YA ESTA HABILITADO'
             return False
       return True
       
   def mxHabilitaNuevoPeriodo(self):
       lcSql = "SELECT cProyec FROM S01MPRY ORDER BY cProyec DESC LIMIT 1"
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'ERROR NO HAY UN INICIO DE PERIODO'
          return False
       lcPeriod = self.mxPeriodoSig(R1[0][0])
       lcDescri = self.mxMesDescripcion(int(lcPeriod[-2:])) + ' ' +lcPeriod[:4]
       lcSql = """INSERT INTO S01MPRY (cProyec, cDescri, cEstCos, cUsuCod) VALUES 
                  ('%s', '%s', '%s', '%s')"""%(lcPeriod, lcDescri, 'A', self.paData['CUSUCOD'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL HABILITAR NUEVO PERIODO'
          return False
       self.paData['CPERIOD'] = lcPeriod
       return True
       
   def mxCopiaConfiguracionAnterior(self):
       lcPerAnt = self.mxPeriodoAnt(self.paData['CPERIOD'])
       # Busca siguiente correlativo
       lcSql = "SELECT MAX(cCodigo) FROM D02MINE"
       R1 = self.loSql.omExecRS(lcSql)
       if R1[0][0] != None:
           lcCodigo = R1[0][0]
           lcCodigo = self.mxSigCorrelativo(lcCodigo)
       # Graba configuracion de cuentas y distribucion de un periodo anterior
       lcSql = """SELECT cIdInEg, cUnidad, cCatego, cCtaCnt, cCodigo, cEstado
                  FROM D02MINE WHERE cPeriod='%s' order by cIdInEg"""%(lcPerAnt)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = """INSERT INTO D02MINE (cCodigo, cIdInEg, cPeriod, cUnidad, cCatego, cCtaCnt, cEstado, tActual, cUsuCod) 
                      VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', '1990-01-01', '%s')"""%(lcCodigo, r[0], self.paData['CPERIOD'], r[1], r[2], r[3], r[5], self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL ACTUALIZAR PERIODO PARA DISTRIBUCION DE CUENTAS TIPO C'
              return False
           # Graba configuracion para distribucion de categoria C
           if r[2] == 'C':
              self.mxConfiguracionFactores(lcPerAnt, r[0])
           lcCodigo = self.mxSigCorrelativo(lcCodigo)
       return True
       
   def mxPeriodoAnt(self, lcPeriod):
       if lcPeriod[-2:] == '01':
          lcAnio = str(int(lcPeriod[:4]) - 1)
          lcPerAnt = lcAnio + '12'
       else:
          lcPerAnt = str(int(lcPeriod) - 1)
       return lcPerAnt
       
   def mxPeriodoSig(self, lcPeriod):
       if lcPeriod[-2:] == '12':
          lcAnio = str(int(lcPeriod[:4]) + 1)
          lcPerSig = lcAnio + '01'
       else:
          lcPerSig = str(int(lcPeriod) + 1)
       return lcPerSig
   
   def mxSigCorrelativo(self, lcCodigo):
       lcCodigo = str(int(lcCodigo) + 1)
       lcCodigo = '000000' + lcCodigo 
       lcCodigo = lcCodigo [-6:]
       return lcCodigo
       
   def mxMesDescripcion(self, p_cMes):
       switcher = {
          1: 'ENERO',
          2: 'FEBRERO',
          3: 'MARZO',
          4: 'ABRIL',
          5: 'MAYO',
          6: 'JUNIO',
          7: 'JULIO',
          8: 'AGOSTO',
          9: 'SETIEMBRE',
          10: 'OCTUBRE',
          11: 'NOVIEMBRE',
          12: 'DICIEMBRE'
       }
       return switcher.get(p_cMes, '* ERROR')
       
   def mxPeriodoSemestre(self, p_cPeriodo):
       lcMes = p_cPeriodo[-2:]
       if lcMes in ['01', '02', '03', '04', '05', '06']:
          lcSemestre = p_cPeriodo[:4] + '-1'
       else:
          lcSemestre = p_cPeriodo[:4] + '-2'
       return lcSemestre
       
   def mxConfiguracionFactores(self, p_cPerAnt, p_cIdInEg):
       # Graba cantidad de estudiantes mas trabajadores solo para id:0302 que corresponde servicios de agua/luz e internet
       if p_cIdInEg == '0302':
          llOk = self.mxEstudianteTrabajador(p_cPerAnt, p_cIdInEg)
          if not llOk:
              return False
          return True
       # Graba factores para los demas casos
       lcSql = """SELECT cCenCos, cCosCen, cUnidad, nElemen, cElemen FROM D02DFCT
                         WHERE cEstado = 'A' AND cPeriod = '%s' AND cIdInEg = '%s'"""%(p_cPerAnt, p_cIdInEg)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = """INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) 
                      VALUES('%s','%s','%s','%s','%s',%s,'%s','%s','%s')"""%(p_cIdInEg, self.paData['CPERIOD'], r[0], r[1], r[2], r[3], r[4], 'A', self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR FACTORES PARA DISTRIBUCION'
              return False
       return True
   
   def mxEstudianteTrabajador(self, p_cPeriod, p_cIdInEg):
       # Borra datos actuales de distribucion
       lcSql = "DELETE FROM D02DFCT WHERE cIdInEg = '%s' AND cPeriod = '%s'"%(p_cIdInEg, self.paData['CPERIOD'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL REINICIAR FACTORES [0302] PARA DISTRIBUCION'
          return False
       laDatos = []
       laTemp  = []
       # Cantidad de trabajadores de un periodo anterior
       lcSql = "SELECT cCosCen, nElemen FROM D02DFCT WHERE cEstado = 'A' AND cPeriod = '%s' AND cIdInEg = '0303'"%(p_cPeriod)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = {'CCENCOS': r[0], 'NELEMEN': float(r[1])}
           laDatos.append(laTmp)
       # Cantidad de estudiantes Pregrado y Postgrado
       lcSemestre = self.mxPeriodoSemestre(self.paData['CPERIOD'])
       lcSql = """SELECT COUNT(A.*), B.cCenCos FROM F_A10MMAT_2('{"CNIVEL" : "01", "CPERIOD" : "%s", "CCUOTA" : "01"}') A
                  INNER JOIN S01TCCO B ON B.cUniAca = A.cUniAca GROUP BY B.cCenCos UNION
                  SELECT COUNT(A.*), B.cCenCos FROM F_A10MMAT_2('{"CNIVEL" : "02", "CPERIOD" : "%s", "CCUOTA" : "01"}') A
                  INNER JOIN S01TCCO B ON B.cUniAca = A.cUniAca GROUP BY B.cCenCos"""%(lcSemestre, lcSemestre)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lnElemen = round(float(r[0]), 2)
           laTmp = {"NCANEST": round(float(r[0]), 2), "NCANTRA": 0.00}
           lcElemen = json.dumps(laTmp)
           # Suma la cantidad de estudiantes y trabajadores si tuviera
           for r2 in laDatos:
               if r2['CCENCOS'] == r[1]:
                  lnElemen = round(float(r[0]) + r2['NELEMEN'], 2)
                  laTmp = {"NCANEST": round(float(r[0]), 2), "NCANTRA": r2['NELEMEN']}
                  lcElemen = json.dumps(laTmp)
           # Graba factores para el id:0302
           lcSql = """INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) 
                      VALUES('%s','%s','%s','%s','%s',%s,'%s','%s','%s')"""%(p_cIdInEg, self.paData['CPERIOD'], '000', r[1], 'ET', lnElemen, lcElemen, 'A', self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR CANTIDAD DE ESTUDIANTES PREGRADO PARA DISTRIBUCION'
              return False
           laTemp.append(r[1])
       # Graba trabajadores en centro de costo que no tienen cant. estudiantes
       lcElemen = ''
       lnElemen = 0.00
       for r in laDatos:
           if r['CCENCOS'] not in laTemp:
              laTmp = {"NCANEST": float(0.00), "NCANTRA": r['NELEMEN']}
              lcElemen = json.dumps(laTmp)
              lnElemen = r['NELEMEN']
              lcSql = """INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) 
                      VALUES('%s','%s','%s','%s','%s',%s,'%s','%s','%s')"""%(p_cIdInEg, self.paData['CPERIOD'], '000', r['CCENCOS'], 'ET', lnElemen, lcElemen, 'A', self.paData['CUSUCOD'])
              llOk = self.loSql.omExec(lcSql)
              if not llOk:
                 self.pcError = 'ERROR AL INGRESAR CANTIDAD DE ESTUDIANTES PREGRADO PARA DISTRIBUCION'
                 return False
       return True
       
   # ---------------------------------------------------------------------------
   # Actualiza Concepto Servicios agua, luz e internet: estudiantes+trabajadores
   # 2021-04-06 BOL Creacion
   # ---------------------------------------------------------------------------
   def omEstudianteTrabajador(self):
       llOk = self.mxValEstudianteTrabajador()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxEstudianteTrabajador(self.paData['CPERIOD'],'0302')
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk
       
   def mxValEstudianteTrabajador(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CPERIOD' in self.paData or len(self.paData['CPERIOD']) != 6:
          self.pcError = 'PERIODO NO DEFINIDO'
          return False
       return True
   
   # ------------------------------------------------------------
   # Carga tablas para ingresos y egresos de centros de costo
   # 2021-01-21 FPM
   # Modificado por BOL 2021-01-29
   # ------------------------------------------------------------
   def omIngresosEgresos(self):
       llOk = self.mxValParamIngresosEgresos()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxValParamUsuario()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxIngresosEgresos()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk
       
   def mxValParamIngresosEgresos(self):
       if not self.mxValParam():
          return False
       elif not 'CPERIOD' in self.paData or len(self.paData['CPERIOD']) != 6:
          self.pcError = 'PERIODO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CREINICIO' in self.paData:
          self.pcError = 'INDICADOR NO DEFINIDO'
          return False
       self.paData['CPERINI'] = self.paData['CPERIOD'][:4] + '00'
       return True
       
   # Funcion que reinicia tabla d02dcos para el calculo nuevo
   def mxInitTablas(self, p_cCatego):
       if p_cCatego == '*':
          lcSql = "DELETE FROM D02DCOS WHERE cCodigo IN (SELECT cCodigo FROM D02MINE WHERE cPeriod = '%s')"%(self.paData['CPERIOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'NO SE PUDO ELIMINAR DETALLE DEL PERIODO'
             return False   
       else:
          lcSql = """DELETE FROM D02DCOS WHERE cCodigo IN (SELECT cCodigo FROM D02MINE
                     WHERE cPeriod = '%s' AND cCatego = '%s')"""%(self.paData['CPERIOD'], p_cCatego)
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'NO SE PUDO ELIMINAR DETALLE DEL PERIODO'
             return False
       lcSql = "SELECT SETVAL(PG_GET_SERIAL_SEQUENCE('D02DCOS', 'nserial'), COALESCE(MAX(nSerial), 0) + 1, FALSE) FROM D02DCOS"
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO RE-SERIALIZAR TABLA D02DCOS'
          return False
       return True
   
   def mxIngresosEgresos(self):
       llOk = self.mxValReinicioIngresosEgresos()
       if not llOk:
          return False
       llOk = self.mxInitTablas('*')
       if not llOk:
          return False
       # Carga arreglo de cantidad de estudiantes por centro de costo que se utiliza en varias distribuciones   
       llOk = self.mxCantEstudiantesCentroCosto()
       if not llOk:
          return False
       llOk = self.mxIngresosEgresosA()
       if not llOk:
          return False
       llOk = self.mxIngresosEgresosPlanillas()
       if not llOk:
          return False
       llOk = self.mxIngresosEgresosC()
       if not llOk:
          return False
       llOk = self.mxIngresosEgresosTasasServicio()
       if not llOk:
          return False
       llOk = self.mxIngresosEgresosTasasEduc()
       if not llOk:
          return False
       llOk = self.mxDistribucionIngresos()
       if not llOk:
          return False
       llOk = self.mxIngresosCursosJurado()
       if not llOk:
          return False 
       llOk = self.mxAjustaRedondeoConcepto()
       if not llOk:
          return False
       llOk = self.mxIngresosEgresosD()
       return llOk
       
   def mxValReinicioIngresosEgresos(self):
       # Valida si periodo esta habilitado para ejecutar proceso
       lcSql = "SELECT cEstCos FROM S01MPRY WHERE cProyec = '%s'"%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       if not R1:
          self.pcError = 'PERIODO %s NO ESTA HABILITADO'%(self.paData['CPERIOD'])
          return False
       if R1[0][0] != 'A':
          self.pcError = 'PERIODO %s ESTA CERRADO'%(self.paData['CPERIOD'])
          return False
       if self.paData['CREINICIO'] != 'S':
          # Si no reinicia periodo, valida que no hayan registros
          lcSql = "SELECT COUNT(*) FROM D02DCOS A INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo WHERE B.cPeriod = '%s' AND B.cEstado = 'A'"%(self.paData['CPERIOD'])
          R1 = self.loSql.omExecRS(lcSql)
          if R1[0][0] > 0:
             self.pcError = 'PROCESO DISTRIBUCION DE INGRESOS Y EGRESOS YA FUE EJECUTADO'
             return False
       return True
       
   def mxIngresosEgresosA(self):
       lcSql = "SELECT cIdInEg FROM D02TINE WHERE cEstado = 'A' AND cCatego = 'A' ORDER BY cIdInEg"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           # Agrupa por conceptos
           llOk = self.mxAgrupaIngEgrA(r[0])
           if not llOk:
              return False
       # Agrupa por centro de costo
       llOk = self.mxAgrupaCenCosA()
       if not llOk:
          return False
       # Inserta el saldo contable
       llOk = self.mxSaldoContableA()
       if not llOk:
          return False
       # Primera iteracion: Ingresa a D02DCOS para distribucion del tipo 03:Otros costos indirectos
       llOk = self.mxDistribOtrosIndirectos('A')
       if not llOk:
              return False 
       # Segunda iteracion: Distribucion del Tipo 02: administracion central
       llOk = self.mxDistribCCAdministracion('A')
       if not llOk:
              return False 
       # Tercera iteracion: Distribucion del Tipo 04:centros de servicio
       llOk = self.mxDistribCCServicios('A')
       # Cuarta iteracion: Distribucion del Tipo 10: Direcciones con ingresos
       llOk = self.mxDistribDireccionesIngresos('A')
       return llOk

   def mxAgrupaIngEgrA(self, p_cIdInEg):
       lcSql = """SELECT cCodigo, cCtaCnt FROM D02MINE WHERE cCatego = 'A' AND
                  cIdInEg = '%s' AND cPeriod = '%s'"""%(p_cIdInEg, self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = []
           laFila = r[1].split(",")
           for lcTmp in laFila:
               if lcTmp != '':
                  laCtaCnt.append(lcTmp)
           if len(laCtaCnt) == 0:
               continue
           for lcCtaCnt in laCtaCnt:
               R2 = self.mxSaldosContables(lcCtaCnt, '1') # 1:agrupa Centro de costo
               for r2 in R2:   
                   lcCenCos = self.mxCentroCostoReasignado(r2[1])                          
                   # Inserta data temporal en el d02dcos para luego ser agrupada
                   if lcCenCos == '000' and p_cIdInEg == '0101':# Excepcion para "Materiales" - Almacen
                       #lcCenCos = 'UNI'
                       lcCenCos = '0BU'
                   lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                              ('%s', '%s', %s, 'A', '%s')"""%(r[0], lcCenCos, float(r2[0]), self.paData['CUSUCOD'])
                   llOk = self.loSql.omExec(lcSql)
                   if not llOk:
                       self.pcError = 'ERROR AL INGRESAR MONTOS INGRESOS/EGRESOS POR CENTRO DE COSTO (A)'
                       return False
       return True
   
   def mxAgrupaCenCosA(self):
       laDatos = []
       # Agrupa monto por centro de costo y concepto
       lcSql = """SELECT SUM(A.nMonto), A.cCenCos, A.cCodigo, C.cTipDes FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN S01TCCO C ON C.cCenCos = A.cCenCos
                  WHERE B.cPeriod = '%s' AND B.cCatego = 'A' GROUP BY A.cCenCos, A.cCodigo, C.cTipDes 
                  ORDER BY A.cCodigo, A.cCenCos"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           if float(r[0]) != 0.00: 
              laTmp = {'NMONTO': float(r[0]), 'CCENCOS': r[1], 'CCODIGO': r[2], 'CTIPDES':r[3]}
              laDatos.append(laTmp)
       self.mxInitTablas('A')
       # Ingresa datos agrupados
       for r in laDatos:
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES 
                      ('%s', '%s', %s, '%s', '%s')"""%(r['CCODIGO'], r['CCENCOS'], r['NMONTO'], r['CTIPDES'], self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
               self.pcError = 'ERROR AL INGRESAR INGRESOS/EGRESOS POR CENTRO DE COSTO (A)'
               return False
       return True
       
   def mxSaldoContableA(self):
       lnMonto = 0.00
       lcSql = """SELECT A.cIdInEg, B.cCodigo, B.cCtaCnt FROM D02TINE A 
                  INNER JOIN D02MINE B ON A.cIdInEg = B.cIdInEg WHERE A.cEstado = 'A' AND B.cEstado = 'A' AND 
                  A.cCatego = 'A' AND B.cPeriod = '%s' ORDER BY A.cIdInEg"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = r[2].split(",")
           for r2 in laCtaCnt:
               if r2 == '':
                  continue
               lcCtaCnt = r2
               R3 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
               if len(R3) == 0:
                  continue
               lnMonto = lnMonto + float(R3[0])
           # Inserta data temporal en el D02DCOS
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, 'SC', '%s')"""%(r[1], '000', lnMonto, self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR SALDOS CONTABLES POR CONCEPTO TIPO A'
              return False
           lnMonto = 0.00
       return True
   
   def mxSaldosContables(self, p_cCtaCnt, p_cFlag):
       llFlag = True if p_cCtaCnt[:1] == '-' else False
       lcCtaCnt = p_cCtaCnt.replace('-','') + '%'
       '''
       if p_cFlag == '1':
          if lcCtaCnt[:1] in ['1', '2', '3'] :
             # Consulta el saldo correspondiente de su distribucion segun centro de costo
             lcSql = """SELECT SUM(nDebMN - nHabMN), cCenCos FROM V_D10DASI
                        WHERE cPeriod = '%s' AND cCtaCnt LIKE '%s'
                        GROUP BY cCenCos"""%(self.paData['CPERIOD'], lcCtaCnt)
          else:
             lcSql = """SELECT SUM(nHabMN - nDebMN), cCenCos FROM V_D10DASI
                        WHERE cPeriod = '%s' AND cCtaCnt LIKE '%s'
                        GROUP BY cCenCos"""%(self.paData['CPERIOD'], lcCtaCnt)
       '''
       if p_cFlag == '1':
          if lcCtaCnt[:1] in ['1', '2', '3'] :
             # Consulta el saldo correspondiente de su distribucion segun centro de costo
             lcSql = """SELECT SUM(A.nDebMN - A.nHabMN), (CASE WHEN B.cCenCos = '000' THEN A.cCenCos ELSE B.cCenCos END) AS Z FROM V_D10DASI A
                        INNER JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                        WHERE A.cPeriod = '%s' AND A.cCtaCnt LIKE '%s'
                        GROUP BY Z"""%(self.paData['CPERIOD'], lcCtaCnt)
          else:
             lcSql = """SELECT SUM(A.nHabMN - A.nDebMN), (CASE WHEN B.cCenCos = '000' THEN A.cCenCos ELSE B.cCenCos END) AS Z FROM V_D10DASI A
                        LEFT JOIN D01MCTA B ON B.cCtaCnt = A.cCtaCnt
                        WHERE A.cPeriod = '%s' AND A.cCtaCnt LIKE '%s'
                        GROUP BY Z"""%(self.paData['CPERIOD'], lcCtaCnt)
       else:
          if lcCtaCnt[:1] in ['1', '2', '3'] :
             # Consulta el saldo correspondiente de su distribucion
             lcSql = """SELECT SUM(nDebMN - nHabMN) FROM V_D10DASI
                        WHERE cPeriod = '%s' AND cCtaCnt LIKE '%s'"""%(self.paData['CPERIOD'], lcCtaCnt)
          else:
             lcSql = """SELECT SUM(nHabMN - nDebMN) FROM V_D10DASI
                        WHERE cPeriod = '%s' AND cCtaCnt LIKE '%s'"""%(self.paData['CPERIOD'], lcCtaCnt)
       R1 = self.loSql.omExecRS(lcSql)
       laResult = []
       # Cambia el signo del monto para casos de cuentas contables que no se deben considerar
       if llFlag:
          for r in R1:
              if r[0] == None:
                 continue
              if p_cFlag == '1':
                 laTmp = (float(r[0])*(-1), r[1])
                 laResult.append(laTmp)   
              else:
                 laResult.append(float(r[0])*(-1))
       else:
          for r in R1:
              if r[0] == None:
                 continue
              if p_cFlag == '1':
                 laTmp = (float(r[0]), r[1])
                 laResult.append(laTmp)   
              else:
                 laResult.append(float(r[0]))
       return laResult
       
   # Trae nuevo centro de costo para agrupacion, ej: educacion primaria -> educacion
   def mxCentroCostoReasignado(self, p_cCenCos):
       lcCenCos = p_cCenCos
       # Trae el centro de costo equivalente a agrupacion si tuviera
       lcSql = """SELECT C.cCenCos FROM S01TCCO A INNER JOIN S02DUAC B ON B.cAcaUni = A.cUniAca
                  INNER JOIN S01TCCO C ON C.cUniAca = B.cUniAca WHERE A.cCenCos = '%s'"""%(p_cCenCos)
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) != 0:
          lcCenCos = R1[0][0]
       return lcCenCos
       
   # Ingresa al D02DCOS para distribucion de los ingresos/egresos del tipo 02:administracion
   def mxDistribCCAdministracion(self, p_cCatego):
       lnMonto = 0.00
       lnMontoT2 = 0.00
       # Cantidad total de estudiantes Pregrado y Postgrado
       if self.paData['NCANEST'] == 0:
          return True
       # Distribucion de los centros de administracion central
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  WHERE B.cPeriod = '%s' AND A.cTipo = '02' AND B.cCatego = '%s' GROUP BY A.cCodigo"""%(self.paData['CPERIOD'], p_cCatego)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lnMontoT2 = float(r[1])
           # Iteracion: calculo del monto segun cantidad de estudiantes pregrado y postgrado
           for r2 in self.laDatEst:
               lnMonto = round(float(r2['NCANEST']) * lnMontoT2 / self.paData['NCANEST'], 2) 
               if lnMonto == 0:
                  continue
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, '05', '%s')"""%(r[0], r2['CCENCOS'], lnMonto, self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL DISTRIBUIR EGRESOS TIPO 02: CENTROS ADMINISTRACION CENTRAL'
                  return False       
       return True

   # Ingresa al D02DCOS para distribucion de los ingresos/egresos del tipo 04:servicios central
   def mxDistribCCServicios(self, p_cCatego):
       lnMonto = 0.00
       lnMontoT2 = 0.00
       # Cantidad total de estudiantes Pregrado y Postgrado
       if self.paData['NCANEST'] == 0:
          return True
       # Distribucion de los centros de administracion central
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  WHERE B.cPeriod = '%s' AND A.cTipo = '04' AND B.cCatego = '%s' GROUP BY A.cCodigo"""%(self.paData['CPERIOD'], p_cCatego)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lnMontoT2 = float(R1[0][1])
           # Iteracion: calculo del monto segun cantidad de estudiantes pregrado y postgrado
           for r2 in self.laDatEst:
               lnMonto = round(float(r2['NCANEST']) * lnMontoT2 / self.paData['NCANEST'], 2) 
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, '06', '%s')"""%(r[0], r2['CCENCOS'], lnMonto, self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL DISTRIBUIR EGRESOS TIPO 04: CENTROS DE SERVICIO'
                  return False
       return True

   # Ingresa al D02DCOS para distribucion del tipo 03: otros indirectos
   def mxDistribOtrosIndirectos(self, p_cCatego):
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  WHERE B.cPeriod = '%s' AND A.cTipo = '03' AND B.cCatego = '%s' GROUP BY A.cCodigo"""%(self.paData['CPERIOD'], p_cCatego)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           # Calcula el monto total de los centros de costo tipo 01:Fijo
           lcSql = """SELECT SUM(A.nMonto) FROM D02DCOS A
                      INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                      WHERE B.cCatego = '%s' AND B.cPeriod = '%s' AND 
                      A.cTipo = '01' AND A.cCodigo = '%s'"""%(p_cCatego, self.paData['CPERIOD'], r[0])
           R2 = self.loSql.omExecRS(lcSql)
           if R2[0][0] == None or R2[0][0] == 0:
              continue
           lnMontoT1 = float(R2[0][0])
           # Inserta en el D02DCOS Monto repartido al tipo 01:Fijo
           lcSql = """SELECT A.cCenCos, C.cTipDes, A.nMonto FROM D02DCOS A
                      INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                      INNER JOIN S01TCCO C ON C.cCenCos = A.cCenCos
                      WHERE B.cCatego = '%s' AND B.cPeriod = '%s' AND 
                      A.cTipo = '01' AND A.cCodigo = '%s'"""%(p_cCatego, self.paData['CPERIOD'], r[0])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               lnMonto  = round(float(r2[2]) * float(r[1]) / lnMontoT1, 2) 
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, '07', '%s')"""%(r[0], r2[0], lnMonto, self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL DISTRIBUIR EGRESOS TIPO 03:OTROS CC. INDIRECTOS'
                  return False
       return True
       
   # Ingresa al D02DCOS para distribucion del tipo 10: Direcciones con ingresos
   def mxDistribDireccionesIngresos(self, p_cCatego):
       # Distribuye solamente si es un egreso
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN D02TINE C ON C.cIdInEg = B.cIdInEg
                  WHERE B.cPeriod = '%s' AND A.cTipo = '10' AND B.cCatego = '%s' AND C.cTipo = 'E' GROUP BY A.cCodigo"""%(self.paData['CPERIOD'], p_cCatego)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           # Calcula el monto total de los centros de costo tipo 01:Fijo
           lcSql = """SELECT SUM(A.nMonto) FROM D02DCOS A
                      INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                      WHERE B.cCatego = '%s' AND B.cPeriod = '%s' AND 
                      A.cTipo = '01' AND A.cCodigo = '%s'"""%(p_cCatego, self.paData['CPERIOD'], r[0])
           R2 = self.loSql.omExecRS(lcSql)
           if R2[0][0] == None or R2[0][0] == 0:
              continue
           lnMontoT1 = float(R2[0][0])
           # Inserta en el D02DCOS Monto repartido al tipo 01:Fijo
           lcSql = """SELECT A.cCenCos, C.cTipDes, A.nMonto FROM D02DCOS A
                      INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                      INNER JOIN S01TCCO C ON C.cCenCos = A.cCenCos
                      WHERE B.cCatego = '%s' AND B.cPeriod = '%s' AND 
                      A.cTipo = '01' AND A.cCodigo = '%s'"""%(p_cCatego, self.paData['CPERIOD'], r[0])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               # Monto = MontoTipo01 * MontoTipo10 / MontoTotalTipo01  
               lnMonto = round(float(r2[2]) * float(r[1]) / lnMontoT1, 2) 
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, '11', '%s')"""%(r[0], r2[0], lnMonto, self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL DISTRIBUIR EGRESOS TIPO 10:DIRECCIONES CON INGRESOS'
                  return False
       return True
        
   # Calculo de los gastos por remuneraciones directamente de Planillas
   def mxIngresosEgresosPlanillas(self):
       self.mxInitTablas('B')
       lcPerIni = self.paData['CPERIOD'][:4] + '00'
       lnRemunT = 0.00
       lcSql = "SELECT cCodigo FROM D02MINE WHERE cCatego = 'B' AND cPeriod = '%s' AND cEstado = 'A'"%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
           self.pcError = 'ERROR CONCEPTO POR REMUNERACIONES NO ESTA HABILITADO PARA EL PERIODO %s'%(self.paData['CPERIOD'])
           return False
       lcCodigo = R1[0][0]
       lcPlanil = self.paData['CPERIOD'][:4] + '/' + self.paData['CPERIOD'][-2:] + 'M' 
       lcSql = """SELECT A.cCenCos, SUM(A.nMonto), B.cTipDes FROM P10DCCO A
                  INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos
                  WHERE A.cPlanil = '%s' GROUP BY A.cCenCos, B.cTipDes ORDER BY A.cCenCos"""%(lcPlanil)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcCenCos = self.mxCentroCostoReasignado(r[0])          
           # Inserta D02DCOS
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, '%s', '%s')"""%(lcCodigo, lcCenCos, float(r[1]), r[2], self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR INGRESOS/EGRESOS (B) POR CENTRO DE COSTO'
              return False
           lnRemunT = lnRemunT + float(r[1])
       # Primera iteracion: Ingresa a D02DCOS para distribucion del tipo 03:Otros costos indirectos
       llOk = self.mxDistribOtrosIndirectos('B')
       if not llOk:
              return False 
       # Segunda iteracion: Distribucion del Tipo 02: administracion central
       llOk = self.mxDistribCCAdministracion('B')
       if not llOk:
              return False 
       # Tercera iteracion: Distribucion del Tipo 04:centros de servicio
       llOk = self.mxDistribCCServicios('B')
       # Cuarta iteracion: Distribucion del Tipo 10: Direcciones con ingresos
       llOk = self.mxDistribDireccionesIngresos('B')
       # Consulta saldo de la Cta 62: Remuneraciones para la comparacion, trae saldo sin signo ya que los egresos por remuneraciones son sin signo
       lcCtaCnt = '62'
       R1 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
       lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                  ('%s', '%s', %s, '%s', '%s')"""%(lcCodigo, '000', abs(float(R1[0])), 'SC', self.paData['CUSUCOD'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL INGRESAR SALDO CONTABLE REMUNERACIONES (B)'
          return False
       return True   

   # Calculo de los ingresos/egresos distribuidos por un factor (M2,HR,UN,...)      
   def mxIngresosEgresosC(self):
       # Ingresa saldos contables
       llOk = self.mxIngresaSaldosCategoriaC()
       if not llOk:
          return False
       # Agrupa saldos por concepto
       llOk = self.mxAgrupaSaldosConcepto()
       if not llOk:
          return False
       # Distribucion segun el factor de cada Centro Costo
       llOk = self.mxDistribucionFactor()
       if not llOk:
          return False
       # Primera iteracion: Distribucion del tipo 03:otros indirectos
       llOk = self.mxDistribOtrosIndirectos('C')
       if not llOk:
          return False
       # Segunda iteracion: Distribucion del Tipo 02: administracion central
       llOk = self.mxDistribCCAdministracion('C')
       if not llOk:
              return False 
       # Tercera iteracion: Distribucion del Tipo 04:centros de servicio
       llOk = self.mxDistribCCServicios('C')
       # Cuarta iteracion: Distribucion del Tipo 10: Direcciones con ingresos
       llOk = self.mxDistribDireccionesIngresos('C')
       return llOk
   
   def mxIngresaSaldosCategoriaC(self):
       lcSql = """SELECT A.cIdInEg, B.cCodigo, B.cCtaCnt FROM D02TINE A 
                  INNER JOIN D02MINE B ON A.cIdInEg = B.cIdInEg WHERE A.cEstado = 'A' AND B.cEstado = 'A' AND 
                  A.cCatego = 'C' AND B.cPeriod = '%s' ORDER BY A.cIdInEg"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcCtaCnt = r[2]
           laCtaCnt = lcCtaCnt.split(",")
           for r2 in laCtaCnt:
               if r2 == '':
                  continue
               lcCtaCnt = r2
               R3 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
               if len(R3) == 0:
                  continue
               # Inserta data temporal en el D02DCOS
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, 'C', '%s')"""%(r[1], '000', float(R3[0]), self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL INGRESAR INGRESOS/EGRESOS POR CONCEPTO TIPO C'
                  return False
       return True           
   
   def mxAgrupaSaldosConcepto(self):
       laDatos = []
       # Agrupa monto por conceptos
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  WHERE B.cPeriod = '%s' AND B.cCatego = 'C' GROUP BY A.cCodigo ORDER BY A.cCodigo"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = {'CCODIGO': r[0], 'NMONTO': float(r[1])}
           laDatos.append(laTmp)
       self.mxInitTablas('C')
       for r in laDatos:
           # Inserta en el D02DCOS el monto agrupado para su distribucion
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, 'SC', '%s')"""%(r['CCODIGO'], '000', float(r['NMONTO']), self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR INGRESOS/EGRESOS TIPO C'
              return False
       return True
       
   def mxDistribucionFactor(self):
       lcSql = """SELECT A.cCodigo, A.nMonto, C.cIdInEg FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN D02TINE C ON C.cIdInEG = B.cIdInEG
                  WHERE B.cPeriod = '%s' AND A.cTipo = 'SC' AND B.cCatego = 'C' AND A.cCenCos = '000'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)  
       for r in R1:
           laTmp = []
           lnElemeT = 0
           lcIdInEg = r[2]
           # Si pertenece a los siguentes ID:0301,0501,0601,0701, entonces utiliza la misma distribucion del id: 0302
           laDatos = ['0301','0501','0601','0701']
           if r[2] in laDatos:
              lcIdInEg = '0302'
           lcSql = """SELECT (CASE WHEN D.cCenCos IS NULL THEN A.cCosCen ELSE D.cCenCos END) AS Z, SUM(A.nElemen), B.cTipDes FROM D02DFCT A
                      INNER JOIN S01TCCO B ON A.cCosCen = B.cCenCos 
                      LEFT JOIN S02DUAC C ON B.cUniAca = C.cAcaUni
                      LEFT JOIN S01TCCO D ON D.cUniAca = C.cUniAca
                      WHERE A.cIdInEg = '%s' AND
                      A.cPeriod = '%s' AND A.nElemen > 0 AND A.cEstado = 'A'
                      GROUP BY Z, B.cTipDes"""%(lcIdInEg, self.paData['CPERIOD'])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               laFila = {'CCENCOS': r2[0], 'NMONTO': float(r2[1]), 'CTIPO': r2[2]}
               laTmp.append(laFila)
               lnElemeT = lnElemeT + 1
           for r2 in laTmp:
               lnMonto = round(float(r[1]) * float(r2['NMONTO']) / lnElemeT, 2)
               # Inserta en el D02DCOS el monto calculado segun el factor de sus elementos
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod, tModifi) VALUES
                          ('%s', '%s', %s, '%s', '%s', NOW())"""%(r[0], r2['CCENCOS'], lnMonto, r2['CTIPO'], self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                 self.pcError = 'ERROR AL INGRESAR INGRESOS/EGRESOS (C) DISTRIBUIDOS POR CENTRO COSTO'
                 return False
           # Ajuste al saldo contable
           llOk = self.mxAjusteDistribucion(r[0], float(r[1]))
           if not llOk:
              return False
       return True
   
   def mxDistribucion03CategoriaC_old(self):
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  WHERE B.cPeriod = '%s' AND B.cCatego = 'C' AND A.cTipo = '03' GROUP BY A.cCodigo"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           #Calcula el total de los elementos del Tipo 01:Fijo
           lcSql = """SELECT SUM(C.nElemen) FROM D02MINE A
                      INNER JOIN D02TINE B ON A.cIdInEG = B.cIdInEG
                      INNER JOIN D02DFCT C ON C.cCenCos = B.cCenCos
                      INNER JOIN S01TCCO D ON D.cCenCos = C.cCosCen
                      WHERE C.cPeriod = '%s' AND A.cCodigo = '%s' AND D.cTipDes = '01' AND C.cEstado = 'A'"""%(self.paData['CPERIOD'],r[0])
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) == 0 or R2[0][0] == 0:
               continue
           lnElemT1 = float(R2[0][0])
           lcSql = """SELECT C.cCosCen, C.nElemen, D.cTipDes FROM D02MINE A
                      INNER JOIN D02TINE B ON A.cIdInEG = B.cIdInEG
                      INNER JOIN D02DFCT C ON C.cCenCos = B.cCenCos
                      INNER JOIN S01TCCO D ON D.cCenCos = C.cCosCen
                      WHERE C.cPeriod = '%s' AND A.cCodigo = '%s' AND D.cTipDes = '01' AND C.cEstado = 'A'"""%(self.paData['CPERIOD'],r[0])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               lnMonto = round( float(r2[1]) * float(r[1]) / lnElemT1 , 2)
               #Inserta en el D02DCOS el calculo del monto segun los factores
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, '%s', '%s')"""%(r[0], r2[0], lnMonto, '07', self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL DISTRIBUIR OTROS EGRESOS INDIRECTOS (C)'
                  return False
       return True
   
   # Excepcion de distribucion, 1201:Ingresos varios y 1401:otros ingresos de gestion se redistribuyen segun ingresos de tasas educativas    
   def mxDistribucionIngresos(self):
       # Actualiza el tipo de distribucion a 08:Varios, de los centros de costo
       llOk = self.mxActualizaTipoVarios()
       if not llOk:
          return False
       # Elimina si hubiera alguna distribucion por el tipo anterior, para redistribuir a nuevo factor
       llOk = self.mxInitTipoVarios()
       if not llOk:
          return False
       # Genera nueva distribucion
       llOk = self.mxDistribuyeIngresoVarios()
       return llOk
       
   def mxActualizaTipoVarios(self):
       lcSql = """SELECT A.cCenCos, B.cCodigo, A.cTipo FROM D02DCOS A
                  INNER JOIN D02MINE B ON B.cCodigo = A.cCodigo
                  WHERE B.cIdInEg IN ('1201', '1401') AND B.cEstado = 'A' AND B.cPeriod = '%s' AND A.cTipo IN ('01','02','03','04')"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = "UPDATE D02DCOS SET cTipo = '08' WHERE cCencos = '%s' AND cCodigo = '%s' AND cTipo = '%s'"%(r[0], r[1], r[2])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL ACTUALIZA TIPO DE CENTRO'
              return False
       return True
       
   def mxInitTipoVarios(self):
       # Elimina si hubiera alguna distribucion por el tipo anterior, para redistribuir a nuevo factor
       lcSql = """SELECT A.cCenCos, B.cCodigo FROM D02DCOS A
                  INNER JOIN D02MINE B ON B.cCodigo = A.cCodigo
                  WHERE B.cIdInEg IN ('1201', '1401') AND B.cEstado = 'A' AND B.cPeriod = '%s' AND A.cTipo IN ('05','06','07')"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcSql = "DELETE FROM D02DCOS WHERE cCenCos = '%s' AND cCodigo = '%s'"%(r[0], r[1])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL ELIMINAR DISTRIBUCION ANTERIOR'
              return False
       return True
       
   def mxDistribuyeIngresoVarios(self): #OJOOO COBRANZA DUDOSA SE DEBE DEFINIR PROCESO, ESTE ES PROCESO TEMPORAL 0803
       # OJO 0803 TEMPORAL
       lnMonto = 0.00
       lcPerIni = self.paData['CPERIOD'][:4] + '00'
       lcSql = """SELECT A.cIdInEg, B.cCodigo, B.cCtaCnt FROM D02TINE A 
                  INNER JOIN D02MINE B ON B.cIdInEg = A.cIdInEg
                  WHERE A.cEstado = 'A' AND A.cIdInEg = '0803' AND B.cPeriod = '%s'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = []
           laFila = r[2].split(",")
           for lcTmp in laFila:
               if lcTmp != '':
                  laCtaCnt.append(lcTmp)
           if len(laCtaCnt) == 0:
              continue         
           for lcCtaCnt in laCtaCnt:
               # Consulta el saldo Total correspondiente de su concepto
               R2 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
               if len(R2) == 0:
                  continue
               lnMonto = lnMonto + float(R2[0])
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, 'SC', '%s')"""%(r[1], '000', lnMonto, self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR SALDO CONTABLE (D)'
              return False
       # FIN 0803 TEMPORAL. AJUSTAR ABAJO
       lnMonto = 0.00
       lnMontoT2 = 0.00
       # Cantidad total de estudiantes Pregrado y Postgrado
       if self.paData['NCANEST'] == 0:
          return True
       # Distribucion de los ingresos varios
       lcSql = """SELECT A.cCodigo, A.nMonto, B.cIdInEg FROM D02DCOS A
                  INNER JOIN D02MINE B ON B.cCodigo = A.cCodigo
                  WHERE B.cIdInEg IN ('1201', '1401', '0803') AND B.cEstado = 'A' AND B.cPeriod = '%s' AND A.cTipo = 'SC'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lnMontoT2 = float(r[1])
           for r2 in self.laDatEst:
               lnMonto = round(float(r2['NCANEST']) * lnMontoT2 / self.paData['NCANEST'], 2) 
               if lnMonto == 0:
                  continue
               if r[2] == '0803':
                  lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                             ('%s', '%s', %s, '01', '%s')"""%(r[0], r2['CCENCOS'], lnMonto, self.paData['CUSUCOD'])
                  llOk = self.loSql.omExec(lcSql)
                  if not llOk:
                     self.pcError = 'ERROR AL DISTRIBUIR EGRESOS TIPO 02: CENTROS ADMINISTRACION CENTRAL'
                     return False
               else:
                  lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                             ('%s', '%s', %s, '09', '%s')"""%(r[0], r2['CCENCOS'], lnMonto, self.paData['CUSUCOD'])
                  llOk = self.loSql.omExec(lcSql)
                  if not llOk:
                     self.pcError = 'ERROR AL DISTRIBUIR EGRESOS TIPO 02: CENTROS ADMINISTRACION CENTRAL'
                     return False
       return True
   
   def mxIngresosEgresosD(self):
       lnMonto = 0.00
       lcPerIni = self.paData['CPERIOD'][:4] + '00'
       lcSql = """SELECT A.cIdInEg, B.cCodigo, B.cCtaCnt FROM D02TINE A 
                  INNER JOIN D02MINE B ON B.cIdInEg = A.cIdInEg
                  WHERE A.cEstado = 'A' AND A.cCatego = 'D' AND B.cPeriod = '%s' ORDER BY A.cIdInEg"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = []
           laFila = r[2].split(",")
           for lcTmp in laFila:
               if lcTmp != '':
                  laCtaCnt.append(lcTmp)
           if len(laCtaCnt) == 0:
              continue         
           for lcCtaCnt in laCtaCnt:
               # Consulta el saldo Total correspondiente de su concepto
               R2 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
               if len(R2) == 0:
                  continue
               lnMonto = lnMonto + float(R2[0])
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, 'SC', '%s')"""%(r[1], '000', lnMonto, self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR SALDO CONTABLE (D)'
              return False
           # Trae el monto ingresado para su distribucion
           lcSql = """SELECT A.cCosCen, A.nElemen, B.cTipDes FROM D02DFCT A INNER JOIN S01TCCO B ON B.cCenCos = A.cCosCen
                      WHERE A.cIdInEg = '%s' AND A.cEstado = 'A' AND A.cPeriod = '%s'"""%(r[0], self.paData['CPERIOD'])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               # Inserta en el D02DCOS los montos ingresados por Descuentos - Reporte TH
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES 
                          ('%s', '%s', %s, '%s', '%s')"""%(r[1], r2[0], float(r2[1]), r2[2], self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL INGRESAR MONTOS DE INGRESOS/EGRESOS POR CONCEPTO [%s]'%(r[0])
       return True
       
   def mxAjusteDistribucion(self, p_cCodigo, p_nSalCnt):
       laDatos = []
       lcSql = "SELECT cCenCos, nMonto, cTipo FROM D02DCOS WHERE cCodigo = '%s' AND cTipo IN ('01','02','03','04','10')"%(p_cCodigo)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = {"CCENCOS": r[0], "NMONTO": float(r[1]), "CTIPO": r[2]}
           laDatos.append(laTmp)
       # Recalcula
       laDatos = self.mxAjustaSaldoContable(laDatos, p_nSalCnt)
       # Inicializa tabla
       lcSql = "DELETE FROM D02DCOS WHERE cCodigo = '%s' AND cTipo != 'SC'"%(p_cCodigo)
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL REINICIAR TABLA DE DISTRIBUCION [D02DCOS]'
          return False
       # Graba distribucion recalculada
       for r in laDatos:
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES 
                      ('%s', '%s', %s, '%s', '%s')"""%(p_cCodigo, r['CCENCOS'], r['NMONTO'], r['CTIPO'], self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
               self.pcError = 'ERROR AL INGRESAR RECALCULO POR CENTRO DE COSTO'
               return False
       return True
       
   def mxAjustaRedondeoConcepto(self):
       lcSql = """SELECT A.cCodigo, A.nMonto FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN D02TINE C ON C.cIdInEG = B.cIdInEG
                  WHERE B.cPeriod = '%s' AND A.cTipo = 'SC' AND A.cCenCos = '000'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           if float(r[1]) == 0.00:
              continue
           llOk = self.mxAjustarRedondeo(r[0], float(r[1]))
           if not llOk:
              return False
       return True
           
   # Calculo de los ingresos/egresos Finalizacion de la actividad academica (Tasas de Servicio)
   # Calculo de los ingresos/egresos Finalizacion de la actividad academica (Tasas de Servicio)
   def mxIngresosEgresosTasasServicio(self):
       # Inserta el saldo contable
       llOk = self.mxSaldoContableTasasServicio()
       if not llOk:
          return False
       # Trae los ingresos por tasas de servicio
       llOk = self.mxFactorTasasServicio()
       if not llOk:
          return False
       # Calcula ingresos segun factor
       llOk = self.mxCalculoTasasServicio()
       if not llOk:
          return False
       return True
       
   def mxSaldoContableTasasServicio(self):
       lnMonto = 0.00
       lcSql = """SELECT A.cIdInEg, B.cCodigo, B.cCtaCnt FROM D02TINE A 
                  INNER JOIN D02MINE B ON A.cIdInEg = B.cIdInEg WHERE A.cEstado = 'A' AND B.cEstado = 'A' AND 
                  A.cCatego = 'E' AND B.cPeriod = '%s' ORDER BY A.cIdInEg"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = r[2].split(",")
           for r2 in laCtaCnt:
               if r2 == '':
                  continue
               lcCtaCnt = r2
               R3 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
               if len(R3) == 0:
                  continue
               lnMonto = lnMonto + float(R3[0])
           # Inserta saldo contable
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, 'SC', '%s')"""%(r[1], '000', lnMonto, self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR SALDO CONTABLE POR FINALIZACION DE ACTIVIDAD ACADEMICA'
              return False
       return True

   def mxFactorTasasServicio(self):
       laDatos = []
       lcPeriod = self.paData['CPERIOD'][:4] + '-' + self.paData['CPERIOD'][-2:]
       lcSql = """SELECT C.cCenCos, SUM(B.nSubTot) FROM D13MPAG A
                     INNER JOIN D13DPAG B ON A.cIdPago = B.cIdPago 
                     INNER JOIN D13TCON C ON C.cConcep = B.cConcep
                     WHERE TO_CHAR(A.TFECPAG, 'YYYYMM') ='%s' AND A.cTipDoc = '03'
                     GROUP BY C.cCenCos"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = {"CCENCOS": r[0], "NMONTO": round(float(r[1]), 2)}
           laDatos.append(laTmp)
       # Graba en la tabla de factores
       for laTmp in laDatos:
           laDatos = {"NMONTO": laTmp['NMONTO']}
           lcElemen = json.dumps(laDatos)
           lcSql = """INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) 
                      VALUES('%s','%s','%s','%s','%s',%s,'%s','%s','%s')"""%('1101', self.paData['CPERIOD'], '000', laTmp['CCENCOS'], 'MO', laTmp['NMONTO'], lcElemen, 'A', self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR FACTORES PARA DISTRIBUCION FINALIZACION DE ACTIVIDAD ACADEMICA'
              return False 
       return True
       
   def mxCalculoTasasServicio(self):
       # Trae el saldo segun el periodo
       lcSql = """SELECT A.cCodigo, A.nMonto, C.cIdInEg FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN D02TINE C ON C.cIdInEG = B.cIdInEG
                  WHERE B.cPeriod = '%s' AND A.cTipo = 'SC' AND B.cCatego = 'E' AND A.cCenCos = '000'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)  
       for r in R1:
           laTmp = []
           lnElemeT = 0
           lcSql = """SELECT A.cCosCen, SUM(A.nElemen), B.cTipDes FROM D02DFCT A
                      INNER JOIN S01TCCO B ON A.cCosCen = B.cCenCos
                      WHERE A.cIdInEg = '%s' AND A.cPeriod = '%s' AND A.nElemen > 0 AND A.cEstado = 'A'
                      GROUP BY A.cCosCen, B.cTipDes"""%(r[2], self.paData['CPERIOD'])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               laFila = {'CCENCOS': r2[0], 'NMONTO': float(r2[1]), 'CTIPO': r2[2]}
               laTmp.append(laFila)
               lnElemeT = lnElemeT + float(r2[1])
           for r2 in laTmp:
               lnMonto = round(float(r[1]) * float(r2['NMONTO']) / lnElemeT, 2)
               # Inserta en el D02DCOS el monto calculado segun el factor de sus elementos
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod, tModifi) VALUES
                          ('%s', '%s', %s, '%s', '%s', NOW())"""%(r[0], r2['CCENCOS'], lnMonto, r2['CTIPO'], self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                 self.pcError = 'ERROR AL INGRESAR INGRESOS POR FINALIZACION DE ACTIVIDAD ACADEMICA'
                 return False
       # Primera iteracion: Distribucion del tipo 00: no estan definidos, se reparte a las escuelas por la cantidad de estudiantes
       llOk = self.mxDistribSinDefinirTasasServicios()
       if not llOk:
          return False
       # Segunda iteracion: Distribucion del Tipo 02: administracion central
       llOk = self.mxDistribCCAdministracion('E')
       if not llOk:
              return False 
       # Tercera iteracion: Distribucion del Tipo 04:centros de servicio
       llOk = self.mxDistribCCServicios('E')
       # Cuarta iteracion: Distribucion del Tipo 10: Direcciones con ingresos
       llOk = self.mxDistribDireccionesIngresos('E')
       return True
       
   def mxDistribSinDefinirTasasServicios(self):
       lnMonto = 0.00
       lnMontoT2 = 0.00
       # Cantidad total de estudiantes Pregrado y Postgrado
       if self.paData['NCANEST'] == 0:
          return True
       # Distribucion de los conceptos sin definir
       lcSql = """SELECT A.cCodigo, SUM(A.nMonto) FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  WHERE B.cPeriod = '%s' AND A.cTipo = '00' AND A.cCenCos = '000' AND B.cIdInEg = '1101' GROUP BY A.cCodigo"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lnMontoT2 = float(r[1])
           # Iteracion: calculo del monto segun cantidad de estudiantes pregrado y postgrado
           for r2 in self.laDatEst:
               lnMonto = round(float(r2['NCANEST']) * lnMontoT2 / self.paData['NCANEST'], 2) 
               if lnMonto == 0:
                  continue
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                          ('%s', '%s', %s, '05', '%s')"""%(r[0], r2['CCENCOS'], lnMonto, self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                  self.pcError = 'ERROR AL DISTRIBUIR INGRESOS - CONCEPTOS NO DEFINIDOS PARA FINALIZACION DE ACTIVIDAD ACADEMICA'
                  return False
       return True

   def mxIngresosEgresosTasasServicio_OLD(self): # CASO ANTERIOR SE HALLABA INGRESOS DE LOS CENTROS DE COSTO -> CODIGO ALUMNO -> DNI
       # Trae los ingresos Postgrado
       llOk = self.mxTasasServicioIngresos('0')
       if not llOk:
          return False
       # Trae los centros de costo respectivos por cada ingreso
       llOk = self.mxCodigoEstTasasServicio()
       if not llOk:
          return False
       # Inserta ingresos por tasas de servicio
       llOk = self.mxIngresoTasasServicioCC()
       if not llOk:
          return False
       # Agrupa los ingresos por CC.
       llOk = self.mxAgrupaTasasServicioCC()
       if not llOk:
          return False
       # Inserta el saldo contable
       lcCodigo = self.laData['CCODIGO']
       llOK = self.mxSaldoContableConcepto(self.laData['CCTACNT'], lcCodigo, 'E')
       if not llOK:
          return False
       # AJusta distribucion al saldo contable
       llOK = self.mxAjusteDistribucion(lcCodigo, self.laData['NSALCNT'])
       return llOK   
   
   def mxIngresoTasasServicioCC(self):
       # Trae codigo del concepto correspondiente al Postgrado
       lcSql = "SELECT cCodigo, cCtaCnt FROM D02MINE WHERE cCatego = 'E' AND cPeriod = '%s' AND cIdInEg = '1101'"%(self.paData['CPERIOD'])
       RS = self.loSql.omExecRS(lcSql)
       if len(RS) == 0 or RS[0][0] == 0:
          self.pcError = 'ACTIVIDAD POR FINALIZACION ACADEMICA [1101] NO ESTA HABILITADO PARA PERIODO'
          return False
       self.laData = {'CCODIGO':RS[0][0], 'CCTACNT': RS[0][1]}
       # Inserta data temporal en el D02DCOS para luego ser agrupado 
       for laData in self.laDatos:
          lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                     ('%s', '%s', %s, 'E', '%s')"""%(self.laData['CCODIGO'], laData['CCENCOS'], float(laData['NMONTO']), self.paData['CUSUCOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL INGRESAR INGRESOS POSTGRADO TIPO E'
             return False
       return True
       
   def mxAgrupaTasasServicioCC(self):
       laDatos = []
       # Trae centro de costo de Admision Postgrado 
       lcSql = "SELECT cCenCos, cTipDes FROM S01TCCO WHERE cClase = '503' AND cEstado = 'A'"
       RS = self.loSql.omExecRS(lcSql)
       if len(RS) == 0 or RS[0][0] == 0:
          self.pcError = 'CENTRO DE COSTO DE ADMISION POSTGRADO NO ESTA DEFINIDO'
          return False
       lcCenAdm = RS[0][0]
       lcTipAdm = RS[0][1]
       # Agrupa monto por centro de costo
       lcSql = """SELECT A.cCenCos, SUM(A.nMonto), C.cTipDes FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN S01TCCO C ON C.cCenCos = A.cCenCos
                  WHERE B.cPeriod = '%s' AND B.cCatego = 'E' GROUP BY A.cCenCos, C.cTipDes ORDER BY A.cCenCos"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcCenCos = r[0]
           lcTipo = r[2]
           # Si Centro de Costo es '000' se almacena en clase 503 Admision Postgrado
           if r[0] == '000':
              lcCenCos = lcCenAdm
              lcTipo = lcTipAdm
           laTmp = {'CCENCOS': lcCenCos, 'NMONTO': float(r[1]), 'CTIPO': lcTipo}
           laDatos.append(laTmp)
       self.mxInitTablas('E')
       for r in laDatos:
           # Inserta en el D02DCOS el monto agrupado por centro de costo Postgrado
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, '%s', '%s')"""%(self.laData['CCODIGO'], r['CCENCOS'], float(r['NMONTO']), r['CTIPO'], self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR INGRESOS POSTGRADO DISTRIBUIDOS TIPO E'
              return False
       return True        
       
   def mxIngresosEgresosTasasEduc(self):
       lcSql = """SELECT A.cIdInEg, B.cCtaCnt, B.cCodigo FROM D02TINE A 
                  INNER JOIN D02MINE B ON B.cIdInEg = A.cIdInEg
                  WHERE A.cEstado = 'A' AND A.cCatego = 'G' AND B.cPeriod = '%s' ORDER BY cIdInEg"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           if r[1] == '':
              continue
           # Reinicia tabla de factores
           llOK = self.mxInitFactores(r[0])
           if not llOK:
              return False
           # Inserta el saldo Contable
           llOK = self.mxSaldoContableConcepto(r[1], r[2], 'G')
           if not llOK:
              return False
           elif self.laData['NSALCNT'] == 0: 
              # Saldo contable = 0 no se hace nada
              continue
           # Inserta factores para el calculo de la distribucion
           llOK = self.mxDistribucionTasasEducativas(r[0])
           if not llOK:
              return False
           # AJusta e inserta el saldo por centros de costo
           llOK = self.mxAjustaSaldosTasasEducativas(r[0], r[2])
           if not llOK:
              return False 
           # AJusta distribucion al saldo contable
           llOK = self.mxAjusteDistribucion(r[2], self.laData['NSALCNT'])
       return llOK
       
   def mxConceptoIdIngresoEgreso(self, p_cIdInEg):
       lcConCep = ''
       if p_cIdInEg == '1004':
          lcConCep = 'T1'# tasa educativa pregrado
       elif p_cIdInEg == '1005':
          lcConCep = 'T2'# tasa educativa postgrado
       elif p_cIdInEg == '1006':
          lcConCep = 'T3'# tasa educativa especialidades
       elif p_cIdInEg == '1007':
          lcConCep = 'T4'# tasa educativa diplomados
       elif p_cIdInEg == '1008':
          lcConCep = 'T5'# tasa educativa actualizacion titulo profesional
       elif p_cIdInEg == '1301':
          lcConCep = 'DV'
       elif p_cIdInEg == '1302':
          lcConCep = 'DE'
       return lcConCep
   
   # Calculo de agrupacion de meses para tasas educativas
   def mxPeriodoInicioTasaEducativa(self, p_cPeriod):
       lcPeriod = '00'+str(int(p_cPeriod[-2:])-2)
       if p_cPeriod[-2:] == '01':
          lcPeriod = str(int(p_cPeriod[:4])-1)+'11'
       elif p_cPeriod[-2:] == '02':
          lcPeriod = str(int(p_cPeriod[:4])-1)+'12'
       else:
          lcPeriod = p_cPeriod[:4]+lcPeriod[-2:]
       return lcPeriod
   
   # Inserta en el d02dfct los montos solo para Tasas educativas categoria G
   def mxDistribucionTasasEducativas(self, p_cIdInEg):
       laCenCos = []
       lcConcep = self.mxConceptoIdIngresoEgreso(p_cIdInEg)
       laTmp = {"CPERINI":self.mxPeriodoInicioTasaEducativa(self.paData['CPERIOD']), "CPERIOD": self.paData['CPERIOD'], "CFLAG": lcConcep}
       lcData = json.dumps(laTmp)
       lcSql = """SELECT cCenCos, nMonto FROM F_D10DCCT_3('%s')"""%(lcData)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcCenCos = self.mxCentroCostoReasignado(r[0])
           if r[0] == None: # OJO no debe ocurrir
              lcCenCos = '000'
           lbFlag = False
           for i in range(0, len(laCenCos)):
               if laCenCos[i]['CCENCOS'] == lcCenCos:
                  laCenCos[i]['NMONTO'] += float(r[1])
                  lbFlag = True
                  break
           if not lbFlag:
              laTmp = {'CCENCOS': lcCenCos, 'NMONTO': round(float(r[1]), 2)}
              laCenCos.append(laTmp)
       for laFila in laCenCos:
           laTmp = {"NMONTO": laFila['NMONTO']}
           lcElemen = json.dumps(laTmp)
           lcSql = """INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) VALUES 
                      ('%s', '%s', '%s', '%s', '%s', %s, '%s', '%s', '%s')"""%(p_cIdInEg, self.paData['CPERIOD'], '000', laFila['CCENCOS'], 'MO', laFila['NMONTO'], lcElemen, 'A', self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR FACTORES DEL CONCEPTO %s'%(p_cIdInEg)
              return False
       return True
   
   def mxSaldoContableConcepto(self, p_cCtaCnt, p_cCodigo, p_cCatego):
       lnSaldo = 0.00
       laCtaCnt = []
       laCtaCnt = p_cCtaCnt.split(",")
       for lcCtaCnt in laCtaCnt:
           if lcCtaCnt == '':
              continue
           R1 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
           if len(R1) != 0:
              lnSaldo = lnSaldo + round(R1[0], 2)
       # Inserta saldo contable al D02DCOS
       lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                  ('%s', '%s', %s, '%s', '%s')"""%(p_cCodigo, '000', lnSaldo, 'SC', self.paData['CUSUCOD'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL INGRESAR SALDO CONTABLE DE LA CUENTA %s'%(lcCtaCnt)
          return False
       self.laData = {"NSALCNT": lnSaldo}
       return True
       
   def mxAjustaSaldosTasasEducativas(self, p_cIdInEg, p_cCodigo):
       # Trae el monto total para el calculo
       lcSql = "SELECT SUM(nElemen) FROM D02DFCT WHERE cIdInEg = '%s' AND cPeriod = '%s' AND cEstado = 'A' GROUP BY cIdInEg"%(p_cIdInEg, self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or R1[0][0] == 0:
          return True
       lnElemeT = float(R1[0][0])
       # Trae los montos de los centros de costo
       lcSql = """SELECT A.cCosCen, A.nElemen, B.cTipDes FROM D02DFCT A 
                  LEFT JOIN S01TCCO B ON A.cCosCen = B.cCenCos WHERE A.cIdInEg = '%s' AND cPeriod = '%s' AND
                  A.nElemen > 0 AND A.cEstado = 'A'"""%(p_cIdInEg, self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1: 
           lnMonto = round(float(r[1]) * self.laData['NSALCNT'] / lnElemeT, 2)
           # Inserta en el D02DCOS el monto calculado segun el factor de sus elementos
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod, tModifi) VALUES
                      ('%s', '%s', %s, '%s', '%s', NOW())"""%(p_cCodigo, r[0], lnMonto, r[2], self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR DESCUENTOS SEGUN CONCEPTO [%s]'%(p_cIdInEg)
              return False
       return True
   
   # Reinicia el d02fct: tabla factor segun el id del concepto
   def mxInitFactores(self, pc_IdInEg):
       lcSql = "DELETE FROM D02DFCT WHERE cIdInEg = '%s' AND cPeriod = '%s'"%(pc_IdInEg, self.paData['CPERIOD'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL REINICIAR TABLA DE FACTORES'
          return False
       return True
       
   def mxAjustarRedondeo(self, p_cCodigo, p_nSalCnt):
       lcSql = "SELECT SUM(nMonto) FROM D02DCOS WHERE cTipo IN ('01','05','06','07','09','11') AND cCodigo = '%s'"%(p_cCodigo)
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or R1[0][0] == 0 or R1[0][0] == None:
          return True
       lnMonto = round(float(R1[0][0]), 2)
       # Compara montos para validar si se necesita redondear
       if lnMonto == p_nSalCnt:
          return True
       # Calcula la diferencia entre los montos
       lnDifere = lnMonto - p_nSalCnt
       laData = {'CCENCOS': '', 'NMONTO': 0.00}
       # Busca el centro de costo con el mayor monto para aumentarle la diferencia
       lcSql = "SELECT nSerial, nMonto FROM D02DCOS WHERE cCodigo = '%s' AND cTipo IN ('01','09') ORDER BY nMonto DESC LIMIT 1"%(p_cCodigo)
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or float(R1[0][1]) == 0.00:
          return True
       laData = {'NSERIAL': R1[0][0], 'NMONTO': float(R1[0][1]) - lnDifere}
       # Graba monto actualizado
       lcSql = "UPDATE D02DCOS SET nMonto = %s WHERE cCodigo = '%s' AND nSerial = %s"%(laData['NMONTO'], p_cCodigo, laData['NSERIAL'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'ERROR AL ACTUALIZAR CALCULO DE RENDONDEO CODIGO[%s]'%(p_cCodigo)
          return False
       return True
   
   def mxIngresosCursosJurado(self):
       # Guarda los factores del UCSMINS para su distribucion
       loSql = CSql()
       llOk = loSql.omConnect(2)
       if not llOk:
          self.pcError = loSql.pcError
          return False
       llOk = self.mxFactorCursoJurado(loSql)
       if not llOk:
          return False
       else:
          loSql.omCommit()
       loSql.omDisconnect() 
       # Inserta el saldo contable
       llOk = self.mxSaldoContableCursosJurado()
       if not llOk:
          return False
       # Distribucion segun factor   
       llOk = self.mxDistribucionCursosJurado()
       if not llOk:
          return False
       return True
       
   def mxFactorCursoJurado(self, p_oSql):
       laDatos = []
       lcPeriod = self.paData['CPERIOD'][:4] + '-' + self.paData['CPERIOD'][-2:]
       lcSql = """SELECT DISTINCT C.cUniaca, SUM(B.nMonto) FROM B05MCPJ A
                  INNER JOIN B03MDEU B ON B.cIdDeud = A.cIdDeud
                  INNER JOIN V_A01MALU C ON C.cCodAlu = A.cCodAlu
                  WHERE B.cEstado = 'C' AND TO_CHAR(B.dRecepc, 'YYYY-mm') = '%s'
                  GROUP BY c.cUniaca"""%(lcPeriod)
       R1 = p_oSql.omExecRS(lcSql)
       for r in R1:
           lcUniAca = r[0]
           # Traer unidad academica equivalente caso de programas agrupadas en escuelas
           lcSql = "SELECT cUniAca FROM S02DUAC WHERE cAcaUni = '%s' AND cEstado = 'A'"%(lcUniAca)
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) != 0:
              lcUniAca = R2[0][0]
           # Trae su centro de costo correspondiente
           lcCenCos = '000'
           lcSql = "SELECT cCenCos FROM S01TCCO WHERE cUniAca = '%s' AND cEstado = 'A'"%(lcUniAca)
           R2 = self.loSql.omExecRS(lcSql)
           if R2[0][0] != None:
              lcCenCos = R2[0][0]
           laTmp = {"CCENCOS": lcCenCos, "NMONTO": round(float(r[1]), 2)}
           laDatos.append(laTmp)
       # Graba en la tabla de factores
       for laTmp in laDatos:
           laDatos = {"NMONTO": laTmp['NMONTO']}
           lcElemen = json.dumps(laDatos)
           lcSql = """INSERT INTO D02DFCT (cIdInEg, cPeriod, cCenCos, cCosCen, cUnidad, nElemen, cElemen, cEstado, cUsuCod) 
                      VALUES('%s','%s','%s','%s','%s',%s,'%s','%s','%s')"""%('1103', self.paData['CPERIOD'], '000', laTmp['CCENCOS'], 'MO', laTmp['NMONTO'], lcElemen, 'A', self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR FACTORES PARA DISTRIBUCION CURSOS POR JURADO'
              return False 
       return True
    
   def mxSaldoContableCursosJurado(self):
       lnMonto = 0.00
       lcSql = """SELECT A.cIdInEg, B.cCodigo, B.cCtaCnt FROM D02TINE A 
                  INNER JOIN D02MINE B ON A.cIdInEg = B.cIdInEg WHERE A.cEstado = 'A' AND B.cEstado = 'A' AND 
                  A.cCatego = 'I' AND B.cPeriod = '%s' ORDER BY A.cIdInEg"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = r[2].split(",")
           for r2 in laCtaCnt:
               if r2 == '':
                  continue
               lcCtaCnt = r2
               R3 = self.mxSaldosContables(lcCtaCnt, '0') # 0: no agrupa por Centro de costo
               if len(R3) == 0:
                  continue
               lnMonto = lnMonto + float(R3[0])
           # Inserta saldo contable
           lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod) VALUES
                      ('%s', '%s', %s, 'SC', '%s')"""%(r[1], '000', lnMonto, self.paData['CUSUCOD'])
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INGRESAR SALDOS CONTABLES POR CONCEPTO CURSOS POR JURADO'
              return False
           lnMonto = 0.00
       return True
       
   def mxDistribucionCursosJurado(self):
       # Trae el saldo de los cursos por jurado segun el periodo
       lcSql = """SELECT A.cCodigo, A.nMonto, C.cIdInEg FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN D02TINE C ON C.cIdInEG = B.cIdInEG
                  WHERE B.cPeriod = '%s' AND A.cTipo = 'SC' AND B.cCatego = 'I' AND A.cCenCos = '000'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)  
       for r in R1:
           laTmp = []
           lnElemeT = 0
           lcSql = """SELECT A.cCosCen, SUM(A.nElemen), B.cTipDes FROM D02DFCT A
                      INNER JOIN S01TCCO B ON A.cCosCen = B.cCenCos
                      WHERE A.cIdInEg = '%s' AND A.cPeriod = '%s' AND A.nElemen > 0 AND A.cEstado = 'A'
                      GROUP BY A.cCosCen, B.cTipDes"""%(r[2], self.paData['CPERIOD'])
           R2 = self.loSql.omExecRS(lcSql)
           for r2 in R2:
               laFila = {'CCENCOS': r2[0], 'NMONTO': float(r2[1]), 'CTIPO': r2[2]}
               laTmp.append(laFila)
               lnElemeT = lnElemeT + float(r2[1])
           for r2 in laTmp:
               lnMonto = round(float(r[1]) * float(r2['NMONTO']) / lnElemeT, 2)
               # Inserta en el D02DCOS el monto calculado segun el factor de sus elementos
               lcSql = """INSERT INTO D02DCOS (cCodigo, cCenCos, nMonto, cTipo, cUsuCod, tModifi) VALUES
                          ('%s', '%s', %s, '%s', '%s', NOW())"""%(r[0], r2['CCENCOS'], lnMonto, r2['CTIPO'], self.paData['CUSUCOD'])
               llOk = self.loSql.omExec(lcSql)
               if not llOk:
                 self.pcError = 'ERROR AL INGRESAR INGRESOS POR CURSOS POR JURADO'
                 return False
       # Primera iteracion: Distribucion del tipo 03:otros indirectos
       llOk = self.mxDistribOtrosIndirectos('I')
       if not llOk:
          return False
       # Segunda iteracion: Distribucion del Tipo 02: administracion central
       llOk = self.mxDistribCCAdministracion('I')
       if not llOk:
              return False 
       # Tercera iteracion: Distribucion del Tipo 04:centros de servicio
       llOk = self.mxDistribCCServicios('I')
       # Cuarta iteracion: Distribucion del Tipo 10: Direcciones con ingresos
       llOk = self.mxDistribDireccionesIngresos('I')
       return True
       
   # Ajusta el monto total por distribucion al saldo contable
   def mxAjustaSaldoContable(self, p_aDatos, p_nSalCnt):
       laDatos = []
       lnMonto = 0.00
       for r in p_aDatos:
           lnMonto = lnMonto + r['NMONTO']
       # Compara monto con saldo contable para ajustar al saldo contable
       if lnMonto == p_nSalCnt or lnMonto == 0.00:
          return p_aDatos
       lnDifere = p_nSalCnt / lnMonto
       # Recalcula distribucion
       for r in p_aDatos:
           lnMonto = round(lnDifere * r['NMONTO'], 2)
           laTmp = {"CCENCOS": r['CCENCOS'], "NMONTO": lnMonto, "CTIPO": r['CTIPO']}
           laDatos.append(laTmp)
       return laDatos
   
   def mxCantEstudiantesCentroCosto(self):
       self.laDatEst = []
       lnCanEst = 0.00
       lcUniAca = '00'
       lcCenCos = '000'
       # Trae la cantidad de estudiantes Pregrado y Postgrado
       lcSemestre = self.mxPeriodoSemestre(self.paData['CPERIOD'])
       lcSql = """SELECT COUNT(A.cIdMatr), B.cUniAca FROM A10MMAT A
                  INNER JOIN V_A01MALU B ON B.cCodAlu = A.cCodAlu
                  WHERE A.cPeriod = '%s' AND A.cEstado = 'A' AND A.nCredit > 0 AND B.cNivel IN ('01','02','03','04', '05')
                  GROUP BY B.cUniAca"""%(lcSemestre)
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcUniAca = r[1]
           # Trae su unidad academica equivalente caso tenga agrupacion
           lcSql = "SELECT cUniAca FROM S02DUAC WHERE cAcaUni = '%s' AND cEstado = 'A'"%(lcUniAca)
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) != 0:
              lcUniAca = R2[0][0]
           # Trae su centro de costo correspondiente
           lcSql = "SELECT cCenCos FROM S01TCCO WHERE cUniAca = '%s' AND cEstado = 'A'"%(lcUniAca)
           R2 = self.loSql.omExecRS(lcSql)
           if len(R2) != 0:
              lcCenCos = R2[0][0]
           laTmp = {"NCANEST": round(float(r[0]), 2), "CCENCOS": lcCenCos}
           self.laDatEst.append(laTmp)
           lnCanEst = round(lnCanEst + float(r[0]), 2)
       # Graba en un arreglo la cantidad de estudiantes Pregrado y Postgrado
       self.paData['NCANEST'] = round(lnCanEst, 2)
       return True
       
   # ------------------------------------------------------------------
   # Auditoria Ingresos/Egresos de centros de costos
   # 2021-02-08 BOL Creacion
   # ------------------------------------------------------------------
   def omAuditoriaXls(self):
       llOk = self.mxValParamAuditoria()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxDatosAuditoria()
       self.loSql.omDisconnect()
       if not llOk:
          return False
       llOk = self.mxAuditoriaXls()
       return llOk
   
   def mxValParamAuditoria(self):
       if not 'CCODUSU' in self.paData or len(self.paData['CCODUSU']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CPERIOD' in self.paData or len(self.paData['CPERIOD']) != 6:
          self.pcError = 'PERIODO NO DEFINIDO'
          return False
       self.paData['CPERINI'] = self.paData['CPERIOD'][:4] + '00'
       return True
       
   def mxDatosAuditoria(self):
       self.laDatos = []
       # Valida si existe la cuenta contable de los conceptos ingresos/egresos
       lcSql = """SELECT B.cDescri, A.cCtaCnt, B.cIdInEg FROM D02MINE A 
                  INNER JOIN D02TINE B ON A.cIdInEg = B.cIdInEg
                  WHERE A.cEstado = 'A' AND A.cPeriod = '%s' AND A.cIdInEg != '0000'"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laCtaCnt = []
           laFila = r[1].split(",")
           for lcTmp in laFila:
               if lcTmp != '':
                  laCtaCnt.append(lcTmp)
               else:
                  laTmp = {'CIDINEG':r[2], 'CCONCEP':r[0], 'CCTACNT':'[b]', 'CDESCRI':'*** ERROR ***', 'NSALDO':0.00, 'COBSERV':'CUENTA CONTABLE NO DEFINIDA'}
                  self.laDatos.append(laTmp)
           if len(laCtaCnt) == 0:
               #laTmp = {'CIDINEG':r[2], 'CCONCEP':r[0], 'CCTACNT':'** ERROR **', 'CDESCRI':'', 'NSALDO':0.00, 'COBSERV':'FALTA CONFIGURACION DE CUENTA'}
               #self.laDatos.append(laTmp)
               continue
           for lcCtaCnt in laCtaCnt:
               if lcCtaCnt[:1] == '-':
                  lcCtaCnt = lcCtaCnt[1:]
               lcSql = "SELECT Descri FROM D10MCTA WHERE CodCta = '%s' ORDER BY AnoPro DESC LIMIT 1"%(lcCtaCnt)
               R2 = self.loSql.omExecRS(lcSql)
               if len(R2) == 0:
                  laTmp = {'CIDINEG':r[2], 'CCONCEP':r[0], 'CCTACNT':lcCtaCnt, 'CDESCRI':'*** ERROR ***', 'NSALDO':0.00, 'COBSERV':'CUENTA CONTABLE NO EXISTE'}
                  self.laDatos.append(laTmp)
                  continue 
               lcDescri = R2[0][0]
               R2 = self.mxSaldosContables(lcCtaCnt, '0')   
               if len(R2) == 0:
                  lnSaldo  = 0.00
                  lcObserv = 'SIN ASIENTOS'
               else:
                  lcObserv = ''
                  lnSaldo  = float(R2[0])
               laTmp = {'CIDINEG':r[2], 'CCONCEP':r[0], 'CCTACNT':lcCtaCnt, 'CDESCRI':lcDescri, 'NSALDO':lnSaldo, 'COBSERV':lcObserv}
               self.laDatos.append(laTmp)
       return True
   
   def mxAuditoriaXls(self):
       self.loXls.openXls('Cnt5290.xlsx')
       self.loXls.active(0)
       self.loXls.setValue(1, 6, 'FECHA: %s '%(datetime.now().date()))
       self.loXls.setValue(2, 6, 'PERIODO: %s-%s'%(self.paData['CPERIOD'][:4],self.paData['CPERIOD'][-2:]))
       lnFila = 4
       for laData in self.laDatos:
           self.loXls.setValue(lnFila, 1, laData['CIDINEG'])
           self.loXls.setValue(lnFila, 2, laData['CCONCEP'])
           self.loXls.setValue(lnFila, 3, laData['CCTACNT'])
           self.loXls.setValue(lnFila, 4, laData['CDESCRI'])
           self.loXls.setValue(lnFila, 5, laData['NSALDO'])
           self.loXls.setValue(lnFila, 6, laData['COBSERV'])
           lnFila = lnFila + 1
       self.loXls.save()
       self.paData['CFILXLS'] = self.loXls.pcFilXls
       return True
       
   # ------------------------------------------------------------------
   # Consulta Ingresos/Egresos de Finalizacion de actividad academica
   # 2021-02-26 BOL Creacion
   # ------------------------------------------------------------------
   def omIngEgTasasServicio(self):
       llOk = self.mxValParamTasasServicio()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxDatosTasasServicio()
       self.loSql.omDisconnect()
       if not llOk:
          return False
       llOk = self.mxTasasServicioXls()
       return llOk
       
   def mxValParamTasasServicio(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CPERIOD' in self.paData or len(self.paData['CPERIOD']) != 6:
          self.pcError = 'PERIODO NO DEFINIDO'
          return False
       return True
   
   def mxDatosTasasServicio(self):
       llOk = self.mxTasasServicioIngresos('1')
       if not llOk:
          return False
       llOk = self.mxCodigoEstTasasServicio()
       if not llOk:
          return False
       #llOk = self.mxComparaAsientos() *OJO METODO YA NO VA PORQUE FUNCION ALMACENADA NO EXISTE
       #self.paData = self.laDatos
       return llOk
       
   # Trae ingresos por tasas de servicio, considera todos
   def mxTasasServicioIngresos(self, p_cFlag):
       if p_cFlag == '1':
          # Trae datos para el reporte
          lcSql = """SELECT A.cIdPago, TO_CHAR(A.TFECPAG, 'YYYY-MM-DD'), (CASE WHEN LENGTH(TRIM(E.cCodigo)) != 0 THEN E.cCodigo ELSE E.cCodOld END) AS cCodigo, E.cDescri, B.nSubTot, D.cIdCate, D.cDescri FROM D13MPAG A 
                     INNER JOIN D13DPAG B ON A.cIdPago = B.cIdPago 
                     INNER JOIN D13TCON C ON C.cConCep = B.cConCep
                     INNER JOIN B03TDOC D ON D.cIdCate = C.cCodAnt
                     INNER JOIN D02MCCT E ON E.cCtaCte = A.cCtaCte
                     WHERE TO_CHAR(A.TFECPAG, 'YYYYMM') ='%s' AND A.cTipDoc = '03'
                     ORDER BY A.TFECPAG, E.cCodigo"""%(self.paData['CPERIOD'])
       else:
          # Trae datos para el reporte
          lcSql = """SELECT '', '', (CASE WHEN LENGTH(TRIM(C.cCodigo)) != 0 THEN C.cCodigo ELSE C.cCodOld END) AS cCodigo, '', SUM(B.nSubTot), '', '' FROM D13MPAG A
                     INNER JOIN D13DPAG B ON A.cIdPago = B.cIdPago 
                     INNER JOIN D02MCCT C ON C.cCtaCte = A.cCtaCte
                     WHERE TO_CHAR(A.TFECPAG, 'YYYYMM') ='%s' AND A.cTipDoc = '03'
                     GROUP BY C.cCodigo, C.cCodOld"""%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
          lcNombre = r[3].replace("/", " ")
          laTmp = {'CIDDEU': r[0], 'DFECHA': r[1], 'CNRODNI': r[2], 'CCODALU': '', 'CNOMBRE': lcNombre,  'NMONTO': float(r[4]), 'CUNIACA': '', 'CNOMUNI': '', 'CIDCATE': r[5],'CDESCRI': r[6]}
          self.laDatos.append(laTmp)    
       return True
   
   # Trae el codigo de alumno relacionado a tasas de servicio si lo tuviera
   def mxCodigoEstTasasServicio(self):
       laDatos = []
       laTmp = []
       for laData in self.laDatos:
           if len(laData['CNRODNI']) > 8:
              # Si longitud del campo es mayor que 8 entonces no es dni pero el codigo del alumno ingresado
              lcSql = """SELECT A.cCodAlu, A.cUniAca, B.cNomUni FROM A01MALU A
                         INNER JOIN S01TUAC B ON A.cUniAca = B.cUniAca WHERE A.cCodAlu = '%s'"""%(laData['CNRODNI'])
           else:
              # Busca codigo de alumno y unidad academica a partir del dni
              lcSql = """SELECT A.cCodAlu, A.cUniAca, B.cNomUni FROM A01MALU A
                         INNER JOIN S01TUAC B ON A.cUniAca = B.cUniAca WHERE A.cNroDni = '%s'
                         ORDER BY A.cCodAlu DESC"""%(laData['CNRODNI'])
           R1 = self.loSql.omExecRS(lcSql)
           for r in R1:
               if r[1] != '00':
                  lcSql = "SELECT cCenCos, cClase, cDescri FROM S01TCCO WHERE cUniAca = '%s' AND cEstado = 'A'"%(r[1])
                  R2 = self.loSql.omExecRS(lcSql)
                  if len(R2) > 0:
                     laTmp = {'CIDDEU': laData['CIDDEU'], 'DFECHA': laData['DFECHA'], 'CNRODNI': laData['CNRODNI'], 'CCODALU': r[0], 'CNOMBRE': laData['CNOMBRE'],  'NMONTO': laData['NMONTO'], 'CUNIACA': r[1], 'CNOMUNI': r[2], 'CIDCATE': laData['CIDCATE'],'CDESCRI': laData['CDESCRI'], 'CCENCOS': R2[0][0], 'CCENDES': R2[0][2], 'CCLASE': R2[0][1]}
                     break
               laTmp = {'CIDDEU': laData['CIDDEU'], 'DFECHA': laData['DFECHA'], 'CNRODNI': laData['CNRODNI'], 'CCODALU': 'S/C', 'CNOMBRE': laData['CNOMBRE'],  'NMONTO': laData['NMONTO'], 'CUNIACA': 'S/U', 'CNOMUNI': '', 'CIDCATE': laData['CIDCATE'],'CDESCRI': laData['CDESCRI'], 'CCENCOS': '000', 'CCENDES':'* N/D', 'CCLASE': ''}
           laDatos.append(laTmp)
       self.laDatos = []
       self.laDatos = laDatos
       return True
      
   ''' METODO YA NO VA, PORQUE FUNCION NO VA EXISTIR
   def mxComparaAsientos(self):
       laDatos = []
       laDatos2 = []
       # Trae mov D12DASI para su comparacion
       lcSql = "SELECT cNroDni, TO_CHAR(dFecha, 'YYYY-MM-DD'), cCtaCnt, nMonto FROM F_D12DASI_1('%s','7032102')"%(self.paData['CPERIOD'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           laTmp = {'CNRODNI': r[0], 'DFECHA': r[1], 'CCTACNT': r[2], 'NMONTO': float(r[3])}
           laDatos.append(laTmp)
       # Compara data con el D12DASI
       for laTemp in laDatos:
           lbFound = False
           for laData in self.laDatos:
               if laData['CNRODNI'] == laTemp['CNRODNI'] and laData['NMONTO'] == laTemp['NMONTO'] and laData['DFECHA'] == laTemp['DFECHA']:
                  lbFound = True
                  laTmp = {'CIDDEU': laData['CIDDEU'], 'DFECHA': laData['DFECHA'], 'CNRODNI': laData['CNRODNI'], 'CCODALU': laData['CCODALU'], 'CNOMBRE': laData['CNOMBRE'],  'NMONTO': laData['NMONTO'], 'CESTADO': laData['CESTADO'], 'CUNIACA': laData['CUNIACA'], 'CNOMUNI': laData['CNOMUNI'], 'CIDCATE': laData['CIDCATE'],'CDESCRI': laData['CDESCRI'], 'CFLAG': 'OK', 'CCENCOS': laData['CCENCOS'], 'CCENDES': laData['CCENDES'], 'CCLASE': laData['CCLASE']}
                  laDatos2.append(laTmp)
                  break
               elif laData['CNRODNI'] == laTemp['CNRODNI'] and laData['NMONTO'] != laTemp['NMONTO']:   
                  lbFound = True
                  laTmp = {'CIDDEU': laData['CIDDEU'], 'DFECHA': laData['DFECHA'], 'CNRODNI': laData['CNRODNI'], 'CCODALU': laData['CCODALU'], 'CNOMBRE': laData['CNOMBRE'],  'NMONTO': laData['NMONTO'], 'CESTADO': laData['CESTADO'], 'CUNIACA': laData['CUNIACA'], 'CNOMUNI': laData['CNOMUNI'], 'CIDCATE': laData['CIDCATE'],'CDESCRI': laData['CDESCRI'], 'CFLAG': 'ERR2', 'CCENCOS': laData['CCENCOS'], 'CCENDES': laData['CCENDES'], 'CCLASE': laData['CCLASE']}
                  laDatos2.append(laTmp)
                  break
               elif laData['CNRODNI'] == laTemp['CNRODNI'] and laData['DFECHA'] != laTemp['DFECHA']:   
                  lbFound = True
                  laTmp = {'CIDDEU': laData['CIDDEU'], 'DFECHA': laData['DFECHA'], 'CNRODNI': laData['CNRODNI'], 'CCODALU': laData['CCODALU'], 'CNOMBRE': laData['CNOMBRE'],  'NMONTO': laData['NMONTO'], 'CESTADO': laData['CESTADO'], 'CUNIACA': laData['CUNIACA'], 'CNOMUNI': laData['CNOMUNI'], 'CIDCATE': laData['CIDCATE'],'CDESCRI': laData['CDESCRI'], 'CFLAG': 'ERR3', 'CCENCOS': laData['CCENCOS'], 'CCENDES': laData['CCENDES'], 'CCLASE': laData['CCLASE']}
                  laDatos2.append(laTmp)
                  break
               laTmp = {'CIDDEU': laData['CIDDEU'], 'DFECHA': laData['DFECHA'], 'CNRODNI': laData['CNRODNI'], 'CCODALU': laData['CCODALU'], 'CNOMBRE': laData['CNOMBRE'],  'NMONTO': laData['NMONTO'], 'CESTADO': laData['CESTADO'], 'CUNIACA': laData['CUNIACA'], 'CNOMUNI': laData['CNOMUNI'], 'CIDCATE': laData['CIDCATE'],'CDESCRI': laData['CDESCRI'], 'CFLAG': laData['CFLAG'], 'CCENCOS': laData['CCENCOS'], 'CCENDES': laData['CCENDES'], 'CCLASE': laData['CCLASE']}
               laDatos2.append(laTmp)
           if not lbFound:
               laTmp = {'CIDDEU': '**', 'DFECHA': laTemp['DFECHA'], 'CNRODNI': laTemp['CNRODNI'], 'CCODALU': '', 'CNOMBRE': '**',  'NMONTO': laTemp['NMONTO'], 'CESTADO': '', 'CUNIACA': '', 'CNOMUNI': '', 'CIDCATE': '','CDESCRI': '', 'CFLAG': 'ERR4', 'CCENCOS': '', 'CCENDES': '', 'CCLASE': ''}
               laDatos2.append(laTmp)
       self.laDatos = []
       self.laDatos = laDatos2
       return True
   '''
      
   def mxTasasServicioXls(self):
       self.loXls.openXls('Cnt5180.xlsx')
       # Distribucion
       # Tramites
       self.loXls.active(0)
       #self.loXls.setValue(1, 12, 'FECHA: %s'%(self.paData['CPERIOD']))
       self.loXls.setValue(2, 12, 'PERIODO: %s'%(self.paData['CPERIOD']))
       lnFila = 4
       for laData in self.laDatos:
           self.loXls.setValue(lnFila, 1, laData['CIDDEU'])
           self.loXls.setValue(lnFila, 2, laData['DFECHA'])
           self.loXls.setValue(lnFila, 3, laData['CIDCATE'])
           self.loXls.setValue(lnFila, 4, laData['CDESCRI'])
           self.loXls.setValue(lnFila, 5, laData['CNRODNI'])
           self.loXls.setValue(lnFila, 6, laData['CNOMBRE'])
           self.loXls.setValue(lnFila, 7, laData['CCODALU'])
           self.loXls.setValue(lnFila, 8, laData['CUNIACA'])
           self.loXls.setValue(lnFila, 9, laData['CNOMUNI'])
           self.loXls.setValue(lnFila, 10,laData['CCENCOS'])
           self.loXls.setValue(lnFila, 11,laData['CCENDES'])
           self.loXls.setValue(lnFila, 12, laData['NMONTO'])
           lnFila = lnFila + 1
       self.loXls.save()
       self.paData['CFILXLS'] = self.loXls.pcFilXls
       return True

   # ------------------------------------------------------------------   
   # Trae Ingresos y Egresos por centro de costo
   # 2021-03-17 BOL Creacion
   # ------------------------------------------------------------------    
   def omIngEgCencos(self):
       llOk = self.mxValParamIngEgCenCos()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxDatosIngEgCenCos()
       self.loSql.omDisconnect()
       return llOk
       
   def mxValParamIngEgCenCos(self):
       if not 'CUSUCOD' in self.paData or len(self.paData['CUSUCOD']) != 4:
          self.pcError = 'CODIGO DE USUARIO NO DEFINIDO O INVALIDO'
          return False
       elif not 'CPERIOD' in self.paData or len(self.paData['CPERIOD']) != 6:
          self.pcError = 'PERIODO NO DEFINIDO'
          return False
       elif not 'CCENCOS' in self.paData or len(self.paData['CCENCOS']) != 3:
          self.pcError = 'CENTRO DE COSTO NO DEFINIDO'
          return False
       return True 
       
   def mxDatosIngEgCenCos(self):
       lnTotIng = 0.00
       lnTotEgr = 0.00
       lcFlag = '0'
       lbFirst = True
       laDatos = []
       self.laDatos = []
       # Trae los ingresos y egresos del centro de costo
       lcSql = """SELECT d.cDescri, C.cIdInEg, C.cDescri, ABS(SUM(A.nMonto)), (CASE WHEN A.cTipo in ('05','06','07') THEN 'IND' ELSE 'DIR' END) AS r, C.cTipo FROM D02DCOS A
                  INNER JOIN D02MINE B ON A.cCodigo = B.cCodigo
                  INNER JOIN D02TINE C ON B.cIdInEg = C.cIdInEg
                  INNER JOIN S01TCCO D ON D.cCenCos = A.cCenCos
                  WHERE B.cPeriod = '%s' AND B.cEstado = 'A' AND A.cCenCos = '%s' AND A.cTipo in ('01','05','06', '07','09')
                  GROUP BY C.cIdInEg, C.cDescri, C.cTipo, d.cDescri, r
                  ORDER BY C.cTipo DESC, C.cIdInEg, r"""%(self.paData['CPERIOD'], self.paData['CCENCOS'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           if r[5] == 'E':
              # Egreso
              lcFlag = '5'
              lnTotEgr = lnTotEgr + float(r[3])
           else:
              lcFlag = '2'
              lnTotIng = lnTotIng + float(r[3])
           if lbFirst:
              laTmp = {'CDESCEN': r[0], 'CCONCEP': '', 'NMONTO': '', 'CTIPDES': '', 'CFLAG': '0'}
              laDatos.append(laTmp)
              lbFirst = False
           laTmp = {'CDESCEN': '', 'CCONCEP': r[1]+' '+r[2], 'NMONTO': float(r[3]), 'CTIPDES': r[4], 'CFLAG': lcFlag}
           laDatos.append(laTmp)
       laTmp = {'CDESCEN': '', 'CCONCEP': 'INGRESOS', 'NMONTO': round(lnTotIng, 2), 'CTIPDES': '', 'CFLAG': '1'}
       laDatos.append(laTmp)
       laTmp = {'CDESCEN': '', 'CCONCEP': 'EGRESOS', 'NMONTO': round(lnTotEgr, 2), 'CTIPDES': '', 'CFLAG': '4'}
       laDatos.append(laTmp)
       laTmp = {'CDESCEN': '', 'CCONCEP': '* RESULTADOS', 'NMONTO': round(lnTotIng - lnTotEgr, 2), 'CTIPDES': '', 'CFLAG': '6'}
       laDatos.append(laTmp)
       self.laDatos = sorted(laDatos, key = lambda k: k['CFLAG'])
       #print(self.laDatos)
       self.paData = []
       self.paData = self.laDatos
       return True
       
   # ------------------------------------------------------------------
   # Graba registro Control Modulo
   # 2021-06-01 BOL Creacion
   # ------------------------------------------------------------------

   def omGrabaControlModulo(self):
       llOk = self.mxValDatosControlModulo()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          return False
       llOk = self.mxGrabaControlModulo()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       self.loSql.omDisconnect()
       return llOk

   def mxValDatosControlModulo(self):
       if not self.mxValParam():
          return False
       elif not 'CMODULO' in self.paData:
          self.pcError = 'MODULO NO DEFINIDO'
          return False
       elif not 'CDESCRI' in self.paData:
          self.pcError = 'DESCRIPCION NO DEFINIDO'
          return False
       elif not 'CESTADO' in self.paData:
          self.pcError = 'ESTADO NO DEFINIDO'
          return False
       return True
   
   def mxGrabaControlModulo(self):
       if self.paData['CMODULO'] != '*':
          #Actualiza control
          lcSql = "UPDATE S01TMOD SET cDescri = '%s', cEstado = '%s', cUsuCod = '%s', tmodifi = NOW() WHERE cModulo = '%s'"%(self.paData['CDESCRI'],self.paData['CESTADO'],self.paData['CUSUCOD'],self.paData['CMODULO'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL ACTUALIZAR REGISTRO'
             return False
       else:
          #Calcula codigo de control de opciones
          lcSql = "SELECT cModulo FROM S01TMOD ORDER BY cModulo DESC LIMIT 1";
          R1 = self.loSql.omExecRS(lcSql)
          if len(R1) == 0 :
             self.pcError = 'ERROR CON CORRELATIVO DE NUEVO REGISTRO'
             return False
          lcModulo = R1[0][0]   
          lcModulo = self.omCentroCosto(lcModulo, '1')
          #Registra nuevo control de opciones
          lcSql = "INSERT INTO S01TMOD (cModulo, cDescri, cEstado, cUsuCod) VALUES ('%s', '%s', 'A', '%s')"%(lcModulo, self.paData['CDESCRI'], self.paData['CUSUCOD'])
          llOk = self.loSql.omExec(lcSql)
          if not llOk:
             self.pcError = 'ERROR AL INGRESAR NUEVO REGISTRO'
             return False
       self.loSql.omCommit()
       return True 
       
         
# ------------------------------------------------------------------
# Actualiza codificacion de centros de costos en su totalidad
# 2020-12-17 FPM Creacion
# ------------------------------------------------------------------
def fxActualizarCentroCosto():
       lo = CCentroCostos()
       laDatos = []
       lcCenCos = '000'
       loSql = CSql()
       llOk = loSql.omConnect()
       lcSql = "SELECT cCenCos FROM S01TCCO WHERE not cCenCos IN ('000', 'UNI') ORDER BY cCenCos"
       R1 = loSql.omExecRS(lcSql)
       for r in R1:
           lcCenCos = lo.omCentroCosto(lcCenCos, '0')
           laTmp = {'CCENCOS': r[0], 'CCOSCEN': lcCenCos, 'CFLAG': ' '}
           #print(laTmp['CCENCOS'], laTmp['CCOSCEN'])
           laDatos.append(laTmp)
       print('TOTAL:', len(laDatos))
       j = 0
       for i in range(0, len(laDatos)):
           if laDatos[i]['CCENCOS'] == laDatos[i]['CCOSCEN']:
              #print('1)', laDatos[i]['CCENCOS'], laDatos[i]['CCOSCEN'])
              laDatos[i]['CFLAG'] = '*'
              j += 1
              continue
           lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '%s'"%(laDatos[i]['CCOSCEN'])
           R1 = loSql.omExecRS(lcSql)
           #print(lcSql)
           #print(len(R1))
           if len(R1) == 0:
              #print('2)', laDatos[i]['CCENCOS'], laDatos[i]['CCOSCEN'])
              lcSql = "UPDATE S01TCCO SET cCenCos = '%s' WHERE cCenCos = '%s'"%(laDatos[i]['CCOSCEN'], laDatos[i]['CCENCOS'])
              llOk = loSql.omExec(lcSql)
              if not llOk:
                 print('ERROR', lcSql)
                 continue
              laDatos[i]['CFLAG'] = '*'
              j += 1
       print('ACTUALIZADOS (1):', j)
       '''
       j = 0
       while True:
          for i in range(0, len(laDatos)):
              if laDatos[i]['CFLAG'] == '*':
                 continue

              lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '%s'"%(laTmp['CCOSCEN'])
              R1 = loSql.omExecRS(lcSql)
              if len(R1) == 0:
                 lcSql = "UPDATE S01TCCO SET cCenCos = '%s' WHERE cCenCos = '%s'"%(laTmp['CCOSCEN'], laTmp['CCENCOS'])
                 llOk = loSql.omExec(lcSql)
                 if not llOk:
                    pass
                 laDatos[i]['CFLAG'] = '*'
              else:
       '''
       print('FIN')
       loSql.omCommit()
       loSql.omDisconnect()
       return

# ---------------------------------------------
# Funcion principal para ser llamado desde php
# ---------------------------------------------
def main(p_cParam):
    laData = json.loads(p_cParam)
    if 'ID' not in laData:
       print('{"ERROR": "NO HAY ID DE PROCESO"}')
       return
    elif laData['ID'] == 'CNT5110G':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omGrabaMntoCenCos()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5110V':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omCorrelativoClase()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5120G':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omGrabaMntoCenRes()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5170G':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omHabilitarPeriodo()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5130A':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omEstudianteTrabajador()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5260P':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omIngresosEgresos()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5290':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omAuditoriaXls()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5180':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omIngEgTasasServicio()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5150A':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omIngEgCencos()
       if llOk:
          print(json.dumps(lo.paData))
          return
    elif laData['ID'] == 'CNT5380G':
       lo = CCentroCostos()
       lo.paData = laData
       llOk = lo.omGrabaControlModulo()
       if llOk:
          print(json.dumps(lo.paData))
          return
    laData = {'ERROR': lo.pcError}
    print(json.dumps(laData))
    return
if __name__ == "__main__":
   main(sys.argv[1])
