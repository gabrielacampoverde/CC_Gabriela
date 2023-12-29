#!/usr/bin/env python
# -*- coding: utf-8 -*-
import sys
import json
import time
import re
from CBase import *
from CSql import *

class CCajaChica(CBase):

   def __init__(self):
       self.paData  = []
       self.paDatos = []
       self.laData  = []
       self.laDatos = []
       self.loSql   = CSql()

   # -------------------------------------------------------------------------
   # Grabar detalle (comprobante) de caja chica
   # 2022-08-10 FPM Creacion
   # -------------------------------------------------------------------------
   def omGrabarDetalleCajaChica(self):
       llOk = self.mxValParamGrabarDetalleCajaChica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxGrabarDetalleCajaChica()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamGrabarDetalleCajaChica(self):
       loDate = CDate()
       if not 'CUSUCOD' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CNROCCH' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CNROCCH']):
          self.pcError = 'NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CCODOPE' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CCODOPE']):
          self.pcError = 'CÓDIGO DE OPERACIÓN NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CTIPDOC' in self.paData or not re.match('^[0-9PM]{2}$', self.paData['CTIPDOC']):
          self.pcError = 'TIPO DE DOCUMENTO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'DCOMPRO' in self.paData or not loDate.mxValDate(self.paData['DCOMPRO']):
          self.pcError = 'FECHA DE COMPROBANTE NO DEFINIDA O INVÁLIDA'
          return False
       elif not 'CGLOSA' in self.paData or len(self.paData['CGLOSA']) < 3 or len(self.paData['CGLOSA']) > 250:
          self.pcError = 'GLOSA NO DEFINIDA O INVÁLIDA'
          return False
       elif not 'CSERIAL' in self.paData or not (self.paData['CSERIAL'] == '*' or re.match('^[A-Z0-9]{5}$', self.paData['CSERIAL'])):
          self.pcError = 'ID-SERIAL DEL DETALLE DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       elif not self.mxValMonto('NMONTO') or self.paData['NMONTO'] <= 0.00:
          self.pcError = 'MONTO TOTAL NO DEFINIDO O INVÁLIDO'
          return False
       elif self.paData['CTIPDOC'] in ['01', '02', '03']  and not re.match('^[A-Z]{1}[A-Z0-9]{3}-[0-9]{8}$', self.paData['CNROCOM']):
          self.pcError = 'FORMATO DE COMPROBANTE INCORRECTO'
          return False
       elif self.paData['CTIPDOC'] == 'PM'  and not re.match('^[0-9]{8}$', self.paData['CNROCOM']):
          self.pcError = 'FORMATO DE NÚMERO DE PLANILLA DE MOVILIDAD INCORRECTO'
          return False
       elif self.paData['CTIPDOC'] in ['00', '12']  and not re.match('^[A-Z0-9\-]{5,20}$', self.paData['CNROCOM']):
          self.pcError = 'FORMATO DE COMPROBANTE OTROS INCORRECTO'
          return False
       # Validaciones por tipo de documento
       if self.paData['CTIPDOC'] == '00':   # Otros
          #if not 'CCODEMP' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CCODEMP']):
          #   self.pcError = 'CÓDIGO DE EMPLEADO NO DEFINIDO O INVÁLIDO'
          #   return False
          self.paData['NMONBAS'] = 0.00
          self.paData['NMONEXO'] = 0.00
          self.paData['NMONIGV'] = 0.00
          self.paData['NMONTIR'] = 0.00
       elif self.paData['CTIPDOC'] == '01':   # Factura
          if not self.mxValMonto('NMONIGV') or self.paData['NMONIGV'] < 0.00:
             self.pcError = 'IGV NO DEFINIDO O INVÁLIDO'
             return False
          elif not self.mxValMonto('NMONBAS') or self.paData['NMONBAS'] < 0.00:
             self.pcError = 'MONTO BASE (GRAVADO) NO DEFINIDO O INVÁLIDO'
             return False
          elif not self.mxValMonto('NMONEXO'):
             self.pcError = 'MONTO EXONERADO NO DEFINIDO O INVÁLIDO'
             return False
          elif not 'CNRORUC' in self.paData or not re.match('^[0-9]{11}$', self.paData['CNRORUC']):
             self.pcError = 'RUC NO DEFINIDO O INVÁLIDO'
             return False
          self.paData['NMONTIR'] = 0.00
       elif self.paData['CTIPDOC'] == '02':   # RHH
          if not self.mxValMonto('NMONTIR') or self.paData['NMONTIR'] < 0.00:
             self.pcError = 'IMPUESTO A LA RENTA NO DEFINIDO O INVÁLIDO'
             return False
          if not self.mxValMonto('NMONBAS') or self.paData['NMONBAS'] < 0.00:
             self.pcError = 'MONTO TOTAL HONORARIO NO DEFINIDO O INVÁLIDO'
             return False
          elif not 'CNRORUC' in self.paData or not re.match('^[0-9]{11}$', self.paData['CNRORUC']):
             self.pcError = 'RUC NO DEFINIDO O INVÁLIDO'
             return False
          self.paData['NMONEXO'] = 0.00
          self.paData['NMONIGV'] = 0.00
       elif self.paData['CTIPDOC'] == '03':   # Boleta
          if not 'CNRORUC' in self.paData or not re.match('^[0-9]{11}$', self.paData['CNRORUC']):
             self.pcError = 'RUC NO DEFINIDO O INVÁLIDO'
             return False
          self.paData['NMONBAS'] = 0.00
          self.paData['NMONEXO'] = 0.00
          self.paData['NMONIGV'] = 0.00
          self.paData['NMONTIR'] = 0.00
       elif self.paData['CTIPDOC'] == '12':   # Ticket
          if not 'CNRORUC' in self.paData or not re.match('^[0-9]{11}$', self.paData['CNRORUC']):
             self.pcError = 'RUC NO DEFINIDO O INVÁLIDO'
             return False
          self.paData['NMONBAS'] = 0.00
          self.paData['NMONEXO'] = 0.00
          self.paData['NMONIGV'] = 0.00
          self.paData['NMONTIR'] = 0.00
       elif self.paData['CTIPDOC'] == 'PM':
          if not 'CCODEMP' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CCODEMP']):
             self.pcError = 'CÓDIGO DE EMPLEADO NO DEFINIDO O INVÁLIDO'
             return False
          self.paData['NMONBAS'] = 0.00
          self.paData['NMONEXO'] = 0.00
          self.paData['NMONIGV'] = 0.00
          self.paData['NMONTIR'] = 0.00
       else:
          self.pcError = 'TIPO DE DOCUMENTO [%s] NO CORRESPONDE'%(self.paData['CTIPDOC'])
          return False
       return True

   def mxGrabarDetalleCajaChica(self):
       # Valida caja chica OJOFPM falta!!!
       lcSql = "SELECT cEstado FROM E03MCCH WHERE cNroCch = '%s'"%(self.paData['CNROCCH'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or not R1[0][0]:
          self.pcError = 'CAJA CHICA [%s] NO EXISTE'%(self.paData['CNROCCH'])
          return False
       elif R1[0][0] == 'X':
          self.pcError = 'CAJA CHICA [%s] ESTÁ ANULADA'%(self.paData['CNROCCH'])
          return False
       # Valida RUC y codigo de usuario
       if self.paData['CTIPDOC'] in ['PM']:
          self.paData['CNRORUC'] = '00000000000'
          llOk = self.mxValCodigoUsuario()
       else:
          self.paData['CCODEMP'] = '0000'
          llOk = self.mxValRuc()
       if not llOk:
          return False
       # Asigna mDatos
       laData = {'CCODEMP': self.paData['CCODEMP'], 'NMONBAS': self.paData['NMONBAS'], 'NMONEXO': self.paData['NMONEXO'], \
                 'NMONIGV': self.paData['NMONIGV'], 'NMONTIR': self.paData['NMONTIR']}
       self.paData['MDATOS'] = json.dumps(laData)
       if self.paData['CSERIAL'] == '*':
          llOk = self.mxInsertarDetalle()
       else:
          llOk = self.mxActualizarDetalle()
       laData = {'CSERIAL': self.paData['CSERIAL']}   # OJOFPM que pasa si falla
       self.paData = laData
       return llOk

   def mxInsertarDetalle(self):
       lcSql = "SELECT cEstado FROM E03DCCH WHERE cNroRuc = '%s' AND cTipDoc = '%s' AND cNroCom = '%s'"%(self.paData['CNRORUC'], self.paData['CTIPDOC'], self.paData['CNROCOM'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) > 0:
          self.pcError = 'COMPROBANTE YA HA SIDO REGISTRADO'
          return False
       lcSql = "SELECT MAX(cSerial) FROM E03DCCH"
       R1 = self.loSql.omExecRS(lcSql)
       lcSerial = R1[0][0]
       lcSerial = '00000' if lcSerial == None else R1[0][0]
       lcSerial = fxCorrelativo(lcSerial)
       lcSql = """INSERT INTO E03DCCH (cSerial, cNroCch, cEstado, cCodOpe, cNroRuc, cTipDoc, cNroCom, dCompro, cMoneda, cGlosa,
                  nMonto, mDatos, cUsuCod) VALUES ('%s', '%s', 'A', '%s', '%s', '%s', '%s', '%s',
                  '1', '%s', %s, '%s', %s)"""%(lcSerial, self.paData['CNROCCH'], self.paData['CCODOPE'], \
                  self.paData['CNRORUC'], self.paData['CTIPDOC'], self.paData['CNROCOM'], self.paData['DCOMPRO'], \
                  self.paData['CGLOSA'], self.paData['NMONTO'], self.paData['MDATOS'], self.paData['CUSUCOD'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO INSERTAR DETALLE DE CAJA CHICA'
       self.paData['CSERIAL'] = lcSerial
       return llOk

   def mxActualizarDetalle(self):
       lcSql = "SELECT cEstado FROM E03DCCH WHERE cNroRuc = '%s' AND cTipDoc = '%s' AND cNroCom = '%s' AND cSerial != '%s'"% \
                (self.paData['CNRORUC'], self.paData['CTIPDOC'], self.paData['CNROCOM'], self.paData['CSERIAL'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) > 0:
          self.pcError = 'COMPROBANTE YA HA SIDO REGISTRADO'
          return False
       lcSql = "SELECT cNroCch FROM E03DCCH WHERE cSerial = '%s'"%(self.paData['CSERIAL'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or not R1[0][0]:
          self.pcError = 'DETALLE DE CAJA CHICA [%s] NO EXISTE'%(self.paData['CSERIAL'])
          return False
       elif R1[0][0] != self.paData['CNROCCH']:
          self.pcError = 'CAJA CHICA [%s] NO CORRESPONDE'%(self.paData['CNROCCH'])
          return False
       lcSql = """UPDATE E03DCCH SET cCodOpe = '%s', cNroRuc = '%s', cTipDoc = '%s', cNroCom = '%s', dCompro = '%s', 
                  cGlosa = '%s', nMonto = %s, mDatos = '%s', cUsuCod = '%s'
                  WHERE cSerial = '%s'""" % (self.paData['CCODOPE'], self.paData['CNRORUC'], self.paData['CTIPDOC'],
                  self.paData['CNROCOM'], self.paData['DCOMPRO'], self.paData['CGLOSA'], self.paData['NMONTO'],
                  self.paData['MDATOS'], self.paData['CUSUCOD'], self.paData['CSERIAL'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO ACTUALIZAR DETALLE DE CAJA CHICA'
       return llOk

   def mxValCodigoUsuario(self):
       lcSql = "SELECT cEstado FROM S01TUSU WHERE cCodUsu = '%s'"%(self.paData['CCODEMP'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or not R1[0][0]:
          self.pcError = 'CÓDIGO DE USUARIO [%s] NO EXISTE'%(self.paData['CCODEMP'])
          return False
       elif R1[0][0] != 'A':
          self.pcError = 'CÓDIGO DE USUARIO [%s] NO ESTÁ ACTIVO'%(self.paData['CCODEMP'])
          return False
       return True

   def mxValRuc(self):
       lcSql = "SELECT cEstado FROM S01MPRV WHERE cNroRuc = '%s'"%(self.paData['CNRORUC'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0 or not R1[0][0]:
          self.pcError = 'RUC [%s] NO EXISTE'%(self.paData['CNRORUC'])
          return False
       elif R1[0][0] != 'A':
          self.pcError = 'RUC [%s] NO ESTÁ ACTIVO'%(self.paData['CNRORUC'])
          return False
       return True

   def mxValMonto(self, p_cField):
       if not p_cField in self.paData:
          return False
       llOk = True
       try:
          self.paData[p_cField] = float(self.paData[p_cField])
       except Exception:
          llOk = False
       return llOk

   # -------------------------------------------------------------------------
   # Aprobar comprobante de caja chica por Auditoria Interna
   # 2022-08-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omAprobarDetalleCajaChica(self):
       llOk = self.mxValParamAprobarDetalleCajaChica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerificarCajaChica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxAprobarDetalleCajaChica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxActualizarEstadoCajaChica()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamAprobarDetalleCajaChica(self):
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CSERIAL' in self.paData or not re.match('^[A-Z0-9]{5}$', self.paData['CSERIAL']):
          self.pcError = 'ID-SERIAL DEL DETALLE DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CNROCCH' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CNROCCH']):
          self.pcError = 'NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       return True

   def mxAprobarDetalleCajaChica(self):
       lcSql = "SELECT cEstado, mDatos, cNroCch FROM E03DCCH WHERE cSerial = '%s'"%(self.paData['CSERIAL'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'ID-SERIAL NO EXISTE'
          return False
       elif R1[0][0] == 'X':
          self.pcError = 'COMPROBANTE ESTÁ ANULADO'
          return False
       elif R1[0][0] == 'B':
          self.pcError = 'COMPROBANTE YA ESTÁ CONFORME'
          return False
       elif R1[0][2] != self.paData['CNROCCH']:
          self.pcError = 'NÚMERO DE CAJA CHICA NO CORRESPONDE'
          return False
       try:   
          laDatos = json.loads(R1[0][1])
       except Exception:
          self.pcError = 'ERROR EN DATOS JSON - AVISE AL ERP'
          return False
       laAudito = [] 
       if 'AAUDITO' in laDatos:
          laAudito = laDatos['AAUDITO'] 
       laAudito.append({'CUSUAUD': self.paData['CUSUCOD'], 'TMODIFI': time.strftime('%Y-%m-%d %H:%M:%S'), 'CESTADO': 'B', 'COBSERV': ''})
       laDatos['AAUDITO'] = laAudito
       lmDatos = json.dumps(laDatos)
       lcSql = "UPDATE E03DCCH SET cEstado = 'B', mDatos = '%s' WHERE cSerial = '%s'" % (lmDatos, self.paData['CSERIAL'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO APROBAR COMPROBANTE DE CAJA CHICA'
       return llOk

   def mxVerificarCajaChica(self):
       lcSql = "SELECT cEstado FROM E03MCCH WHERE cNroCch = '%s'"%(self.paData['CNROCCH'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'ID-SERIAL NO EXISTE'
          return False
       elif not R1[0][0] in ['B', 'O']:
          self.pcError = 'ESTADO [%s] DE CABECERA DE CAJA CHICA NO PERMITE ACTUALIZAR'%(R1[0][0])
          return False
       return True

   def mxActualizarEstadoCajaChica(self):
       lcEstado = 'B'
       lcSql = "SELECT cEstado FROM E03DCCH WHERE cNroCch = '%s' ORDER BY cSerial"%(self.paData['CNROCCH'])
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           if r[0] in ['O', 'E']:
              lcEstado = 'O'
              break
       lcSql = "UPDATE E03MCCH SET cEstado = '%s' WHERE cNroCch = '%s'"%(lcEstado, self.paData['CNROCCH'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO ACTUALIZAR ESTADO DE CABECERA DE CAJA CHICA'
       return llOk

   # -------------------------------------------------------------------------
   # Observar comprobante de caja chica por Auditoria Interna
   # 2022-08-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omObservarDetalleCajaChica(self):
       llOk = self.mxValParamObservarDetalleCajaChica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxVerificarCajaChica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxObservarDetalleCajaChica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       llOk = self.mxActualizarEstadoCajaChica()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamObservarDetalleCajaChica(self):
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CSERIAL' in self.paData or not re.match('^[A-Z0-9]{5}$', self.paData['CSERIAL']):
          self.pcError = 'ID-SERIAL DEL DETALLE DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'COBSERV' in self.paData or len(self.paData['COBSERV']) < 5 or len(self.paData['COBSERV']) > 200:
          self.pcError = 'OBSERVACIÓN NO DEFINIDA O INVÁLIDA'
          return False
       elif not 'CNROCCH' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CNROCCH']):
          self.pcError = 'NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       return True

   def mxObservarDetalleCajaChica(self):
       lcSql = "SELECT cEstado, mDatos FROM E03DCCH WHERE cSerial = '%s'"%(self.paData['CSERIAL'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'ID-SERIAL NO EXISTE'
          return False
       elif R1[0][0] == 'X':
          self.pcError = 'COMPROBANTE ESTÁ ANULADO'
          return False
       elif R1[0][0] == 'B':
          self.pcError = 'COMPROBANTE YA ESTÁ CONFORME'
          return False
       try:   
          laDatos = json.loads(R1[0][1])
       except Exception:
          self.pcError = 'ERROR EN DATOS JSON - AVISE AL ERP'
          return False
       laAudito = [] 
       if 'AAUDITO' in laDatos:
          laAudito = laDatos['AAUDITO'] 
       laAudito.append({'CUSUAUD': self.paData['CUSUCOD'], 'TMODIFI': time.strftime('%Y-%m-%d %H:%M:%S'), 'CESTADO': 'O', 'COBSERV': self.paData['COBSERV']})
       laDatos['AAUDITO'] = laAudito
       lmDatos = json.dumps(laDatos)
       lcSql = "UPDATE E03DCCH SET cEstado = 'O', mDatos = '%s' WHERE cSerial = '%s'" % (lmDatos, self.paData['CSERIAL'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO APROBAR COMPROBANTE DE CAJA CHICA'
       return llOk

   # -------------------------------------------------------------------------
   # Rechazar comprobante de caja chica por Contabilidad
   # 2022-08-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omRechazarDetalleCajaChica(self):
       print('111')
       llOk = self.mxValParamObservarDetalleCajaChica()
       if not llOk:
          return False
       print('222')
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       print('333')
       llOk = self.mxVerificarCajaChica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       print('444')
       llOk = self.mxObservarDetalleCajaChica()
       if not llOk:
          self.loSql.omDisconnect()
          return False
       print('555')
       llOk = self.mxActualizarEstadoCajaChica()
       if llOk:
          self.loSql.omCommit()
       print('666')
       self.loSql.omDisconnect()
       return llOk

   def mxValParamObservarDetalleCajaChica(self):
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CSERIAL' in self.paData or not re.match('^[A-Z0-9]{5}$', self.paData['CSERIAL']):
          self.pcError = 'ID-SERIAL DEL DETALLE DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'COBSERV' in self.paData or len(self.paData['COBSERV']) < 10 or len(self.paData['COBSERV']) > 100:
          self.pcError = 'OBSERVACIÓN NO DEFINIDA O INVÁLIDA'
          return False
       elif not 'CNROCCH' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CNROCCH']):
          self.pcError = 'NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       return True

   def mxObservarDetalleCajaChica(self):
       lcSql = "SELECT cEstado, mDatos FROM E03DCCH WHERE cSerial = '%s'"%(self.paData['CSERIAL'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'ID-SERIAL NO EXISTE'
          return False
       elif R1[0][0] == 'X':
          self.pcError = 'COMPROBANTE ESTÁ ANULADO'
          return False
       elif R1[0][0] == 'B':
          self.pcError = 'COMPROBANTE YA ESTÁ CONFORME'
          return False
       try:   
          laDatos = json.loads(R1[0][1])
       except Exception:
          self.pcError = 'ERROR EN DATOS JSON - AVISE AL ERP'
          return False
       laAudito = [] 
       if 'AAUDITO' in laDatos:
          laAudito = laDatos['AAUDITO'] 
       laAudito.append({'CUSUAUD': self.paData['CUSUCOD'], 'TMODIFI': time.strftime('%Y-%m-%d %H:%M:%S'), 'CESTADO': 'O', 'COBSERV': self.paData['COBSERV']})
       laDatos['AAUDITO'] = laAudito
       lmDatos = json.dumps(laDatos)
       lcSql = "UPDATE E03DCCH SET cEstado = 'O', mDatos = '%s' WHERE cSerial = '%s'" % (lmDatos, self.paData['CSERIAL'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO APROBAR COMPROBANTE DE CAJA CHICA'
       return llOk

   # -------------------------------------------------------------------------
   # Conformidad de caja chica por Contabilidad
   # 2022-08-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omConformidadCajaChica(self):
       llOk = self.mxValParamConformidadCajaChica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxConformidadCajaChica()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxValParamConformidadCajaChica(self):
       if not 'CUSUCOD' in self.paData or not re.match('^[0-9]{4}$', self.paData['CUSUCOD']):
          self.pcError = 'CÓDIGO DE USUARIO NO DEFINIDO O INVÁLIDO'
          return False
       elif not 'CNROCCH' in self.paData or not re.match('^[A-Z0-9]{4}$', self.paData['CNROCCH']):
          self.pcError = 'NÚMERO DE CAJA CHICA NO DEFINIDO O INVÁLIDO'
          return False
       return True

   def mxConformidadCajaChica(self):
       lcSql = "SELECT A.cEstado, A.mDatos, B.cTipo FROM E03MCCH A INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh WHERE A.cNroCch = '%s'"%(self.paData['CNROCCH'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'NÚMERO DE CAJA CHICA [%s] NO EXISTE'%(self.paData['CNROCCH'])
          return False
       elif R1[0][0] == 'X':
          self.pcError = 'CAJA CHICA ESTÁ ANULADA'
          return False
       elif R1[0][0] == 'O':
          self.pcError = 'CAJA CHICA ESTÁ OBSERVADA'
          return False
       elif R1[0][0] in ['D', 'E']:
          self.pcError = 'CAJA CHICA YA ESTÁ APROBADO Y/O CONTABILIZADA'
          return False
       elif not (R1[0][0] == 'C'):
          self.pcError = 'ESTADO DE CAJA CHICA NO PERMITE DAR CONFORMIDAD'
          return False
       print ("111")
       if R1[0][1] != "":
          laDatos = json.loads(R1[0][1])
       else:
          laDatos = {}
       # try:   
       #    laDatos = json.loads(R1[0][1])
       # except Exception:
       #    self.pcError = 'ERROR EN DATOS JSON - AVISE AL ERP'
       #    return False
       print ("222")
       laCont = {'TCNTRREV': time.strftime('%Y-%m-%d %H:%M:%S')}
       laDatos.update(laCont)
       lmDatos = json.dumps(laDatos)
       # laDatos = {'TCNTRREV': time.strftime('%Y-%m-%d %H:%M:%S')}
       # lmDatos = json.dumps(laDatos)
       lcSql = "UPDATE E03MCCH SET cUsuCnt = '%s', cEstado = 'D', mDatos = '%s', cUsucod = '%s', tModifi = NOW() WHERE cNroCch = '%s'" % (self.paData['CUSUCOD'], lmDatos, self.paData['CUSUCOD'], self.paData['CNROCCH'])
       print ("333")
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO DAR CONFORMIDAD A CAJA CHICA'
       self.paData = {'OK': 'OK'}
       return llOk

   # -------------------------------------------------------------------------
   # Conformidad de caja chica por auditoria
   # 2022-08-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omConformidadCajaChicaAud(self):
       llOk = self.mxValParamConformidadCajaChica()
       if not llOk:
          return False
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxConformidadCajaChicaAud()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxConformidadCajaChicaAud(self):
       lcSql = "SELECT A.cEstado, A.mDatos, B.cTipo FROM E03MCCH A INNER JOIN E03TCCH B ON B.cCajaCh = A.cCajaCh WHERE A.cNroCch = '%s'"%(self.paData['CNROCCH'])
       R1 = self.loSql.omExecRS(lcSql)
       if len(R1) == 0:
          self.pcError = 'NÚMERO DE CAJA CHICA [%s] NO EXISTE'%(self.paData['CNROCCH'])
          return False
       elif R1[0][0] == 'X':
          self.pcError = 'CAJA CHICA ESTÁ ANULADA'
          return False
       elif R1[0][0] == 'O':
          self.pcError = 'CAJA CHICA ESTÁ OBSERVADA'
          return False
       elif R1[0][0] in ['D', 'E']:
          self.pcError = 'CAJA CHICA YA ESTÁ APROBADO Y/O CONTABILIZADA'
          return False
       elif not (R1[0][0] == 'B'):
          self.pcError = 'ESTADO DE CAJA CHICA NO PERMITE DAR CONFORMIDAD'
          return False
       laDatos = {'TAUDRREV': time.strftime('%Y-%m-%d %H:%M:%S')}
       lmDatos = json.dumps(laDatos)       
       lcSql = "UPDATE E03MCCH SET cUsuAud = '%s', cEstado = 'C', mDatos = '%s', cUsucod = '%s', tModifi = NOW() WHERE cNroCch = '%s'" % (self.paData['CUSUCOD'], lmDatos, self.paData['CUSUCOD'], self.paData['CNROCCH'])
       llOk = self.loSql.omExec(lcSql)
       if not llOk:
          self.pcError = 'NO SE PUDO DAR CONFORMIDAD A CAJA CHICA'
       self.paData = {'OK': 'OK'}
       return llOk

   # -------------------------------------------------------------------------
   # Inicializar cajas chicas
   # 2022-08-22 FPM Creacion
   # -------------------------------------------------------------------------
   def omInicializarCajaChica(self):
       llOk = self.loSql.omConnect()
       if not llOk:
          self.pcError = self.loSql.pcError
          return False
       llOk = self.mxInicializarCajaChica()
       if llOk:
          self.loSql.omCommit()
       self.loSql.omDisconnect()
       return llOk

   def mxInicializarCajaChica(self):
       # lcNroCch = '0000'
       lcNroCch = '0066'
       lcSql = "SELECT cCajaCh, cCodUsu FROM E03TCCH WHERE cEstado = 'A'"
       R1 = self.loSql.omExecRS(lcSql)
       for r in R1:
           lcNroCch = fxCorrelativo(lcNroCch)
           if r[1] == '1871':
              lcCodUsu = '0000'
           else:
              lcCodUsu = r[1]
           # lcSql = """INSERT INTO E03MCCH (cNroCch, cCajaCh, tEnvio, nMonto, cGlosa, mDatos, cUsuCod) VALUES 
           #            ('%s', '%s', NOW(), 0.00, 'CAJA CHICA - SETIEMBRE 2022', '', 'U666')"""%(lcNroCch, r[0])
           # lcSql = """INSERT INTO E03MCCH (cNroCch, cCajaCh, cUsures, tEnvio, nMonto, cGlosa, mDatos, cUsuCod) VALUES 
           #            ('%s', '%s', '%s', NOW(), 0.00, 'CAJA CHICA - OCTUBRE 2022', '', 'U666')"""%(lcNroCch, r[0], lcCodUsu)
           lcSql = """INSERT INTO E03MCCH (cNroCch, cCajaCh, cUsures, tEnvio, nMonto, cGlosa, mDatos, cUsuCod) VALUES 
                      ('%s', '%s', '%s', NOW(), 0.00, 'CAJA CHICA - DICIEMBRE 2022', '', 'U666')"""%(lcNroCch, r[0], lcCodUsu)
           print(lcSql)
           llOk = self.loSql.omExec(lcSql)
           if not llOk:
              self.pcError = 'ERROR AL INSERTAR CAJA CHICA'
              return False

       self.paData = {'OK': 'OK'}
       return True    

# ---------------------------------------------
# Funcion principal para ser llamado desde php
# ---------------------------------------------
def main(p_cParam):
   laData = json.loads(p_cParam)
   if 'ID' not in laData:
       print('{"ERROR": "NO HAY ID DE PROCESO"}')
       return
   elif laData['ID'] == 'CCH5140G':
       lo = CCajaChica()
       lo.paData = laData
       llOk = lo.omGrabarDetalleCajaChica()
       if llOk:
          print(json.dumps(lo.paData))
          return
   elif laData['ID'] == 'CCH5150A':
       lo = CCajaChica()
       lo.paData = laData
       llOk = lo.omAprobarDetalleCajaChica()
       if llOk:
          print(json.dumps(lo.paData))
          return
   elif laData['ID'] == 'CCH5150B':
       lo = CCajaChica()
       lo.paData = laData
       llOk = lo.omConformidadCajaChicaAud()
       if llOk:
          print(json.dumps(lo.paData))
          return
   elif laData['ID'] == 'CCH5150O':
       lo = CCajaChica()
       lo.paData = laData
       llOk = lo.omObservarDetalleCajaChica()
       if llOk:
          print(json.dumps(lo.paData))
          return
   elif laData['ID'] == 'CCH5160C':
       lo = CCajaChica()
       lo.paData = laData
       llOk = lo.omConformidadCajaChica()
       if llOk:
          print(json.dumps(lo.paData))
          return
   elif laData['ID'] == 'CCH5100I':
       lo = CCajaChica()
       lo.paData = laData
       llOk = lo.omInicializarCajaChica()
       if llOk:
          print(json.dumps(lo.paData))
          return
   else:
       laData = {'ERROR': 'ID [%s] NO DEFINIDA' % (laData['ID'])}
       print(json.dumps(laData))
       return
   laData = {'ERROR': lo.pcError}
   print(json.dumps(laData))
   return

if __name__ == "__main__":
   main(sys.argv[1])

'''
python3 -m py_compile CCajaChica.py

python3 CCajaChica.py '{"ID": "CCH5140G", "CUSUCOD": "1221", "CNROCCH": "0001", "CSERIAL": "*", "CCODOPE": "AFLO", "CTIPDOC": "01", "CNROCOM": "E001-00000001", "DCOMPRO": "2022-09-10", "CGLOSA": "GLOSA DEL COMPROBANTE", "NMONTO": 100.00, "NMONBAS": 0, "NMONEXO": 0, "NMONIGV": 15, "NMONTIR": 0, "CNRORUC": "20141637941", "CCODEMP": "1221"}'
python3 CCajaChica.py '{"ID": "CCH5140G", "CUSUCOD": "1221", "CNROCCH": "0001", "CSERIAL": "00001", "CCODOPE": "AFLO", "CTIPDOC": "01", "CNROCOM": "E001-00000001", "DCOMPRO": "2022-09-10", "CGLOSA": "GLOSA DEL COMPROBANTE", "NMONTO": 100.00, "NMONBAS": 0, "NMONEXO": 0, "NMONIGV": 12, "NMONTIR": 0, "CNRORUC": "20141637941", "CCODEMP": "1221"}'
- Aprobar comprobante por auditoria
python3 CCajaChica.py '{"ID": "CCH5150A", "CUSUCOD": "1221", "CSERIAL": "00001"}'
- Observar comprobante por auditoria
python3 CCajaChica.py '{"ID": "CCH5150O", "CUSUCOD": "1221", "CSERIAL": "00001", "COBSERV": "RUBRO DEL PROVEEDOR NO CORRESPONDE"}'
python3 CCajaChica.py '{"ID": "CCH5100I"}'

'''
