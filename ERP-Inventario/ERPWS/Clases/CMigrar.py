#-*- coding: utf-8 -*-
import sys
import json
import random
import time
import re
from datetime import datetime
from CSql import *
from CBase import *
#reload(sys)
#sys.setdefaultencoding('utf8')

#########################################################
## Migra datos de activos fijos a otra base de datos.
#########################################################
class CMigrar(CBase):
   poFile = None
   
   def __init__(self):
       self.loSql  = CSql()   # UCSMINS
       self.loSql1 = CSql()   # UCSMAFJ
       self.loSqlS = CSqlServer()   # SQL Server
       self.loSqlP = CSql()   # Postgres
       self.pcError  = ''
       self.laUniAca = []
       self.laDatos  = []

   # Migra activos fijos de UCSMERP
   # 2023-03-30 Creacion
   def omMigrarA04MAFJ_Inventario(self):
      print ('Conectando con UCSMERP ...')
      llOk = self.loSql.omConnect()   # UCSMERP
      if not llOk:
         self.pcError = 'ERROR CONECTAR DB UCSMERP'
         return False
      print ('Conectando con UCSMAFJ ...')
      llOk = self.loSql1.omConnect(8)   # UCSMAFJ
      if not llOk:
         self.pcError = 'ERROR CONECTAR DB UCSMAFJ'
         return False
      print ('Migrando Activos Fijos...')
      llOk = self.mxMigrarE04MAFJ_NuevosInv()
      if llOk:
         self.loSql1.omCommit()
      self.loSql.omDisconnect()
      self.loSql1.omDisconnect()
      print ('Proceso terminado...')
      return llOk

   def mxMigrarE04MAFJ_NuevosInv(self):
      import datetime
      x = datetime.datetime.now()
      cyear = (x.year)-1
      lcPeri = (x.year)-1
      lcPeriod = str(lcPeri) + '-12-31'
      #print(lcPeriod)
      lcSql = "SELECT cActFij, cTipAfj, nCorrel, cEstado, cSituac, cDescri, cIndact, cCenRes, cCodEmp, cNroRuc, cRazSoc, dFecAlt, nMontmn, nGastos, nMontme, cMoneda, nSerfac, mDatos,\
                      mFotogr, ccodold, nMoncal, nDepacu, nDeprec, cUsuCod FROM E04mafj WHERE  DFECALT <=  '%s' ORDER BY  dFecAlt" %(lcPeriod)
      print(lcSql)
      R1 = self.loSql.omExecRS(lcSql)
      for laTmp in R1:
         #prin
         # t(laTmp[17])
         lcDescri = laTmp[5].replace("'", "''")
         lcRazSoc = laTmp[10].replace("'", "''")
         lmDatos = laTmp[17].replace("'", "''")
         lcSql = "INSERT INTO E04MAFJ (cActFij, cTipAfj, nCorrel, cEstado, cSituac, cDescri, cIndact, cCenRes, cCodEmp, cNroRuc, cRazSoc, dFecAlt, nMontmn, nGastos, nMontme, cMoneda, nSerfac, mDatos, mFotogr, ccodold, nMoncal, nDepacu, nDeprec, cPerInv, cUsuCod) \
                     VALUES ('%s', '%s', '%s', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')"%\
                     (laTmp[0], laTmp[1], laTmp[2], laTmp[3],laTmp[4],lcDescri,laTmp[6], laTmp[7], laTmp[8], laTmp[9],lcRazSoc,laTmp[11],laTmp[12], laTmp[13], laTmp[14], laTmp[15],laTmp[16],lmDatos,laTmp[18], laTmp[19], laTmp[20], laTmp[21],laTmp[22], cyear,laTmp[23])
         #print(lcSql)
         llOk = self.loSql1.omExec(lcSql)
         if not llOk:
            self.pcError = 'NO SE PUDO INSERTAR ACTIVOS FIJOS'
            return False
      return True
   
   # Migra personal nuevos creados en UCSMERP A UCSMAFJ
   # 2023-03-30 Creacion
   def omMigrarUsuariosNuevos(self):
      print ('Conectando con UCSMERP ...')
      llOk = self.loSql.omConnect()   # UCSMERP
      if not llOk:
         self.pcError = 'ERROR CONECTAR DB UCSMERP'
         return False
      print ('Conectando con UCSMAFJ ...')
      llOk = self.loSql1.omConnect(8)   # UCSMAFJ
      if not llOk:
         self.pcError = 'ERROR CONECTAR DB UCSMAFJ'
         return False
      print ('Migrando Empleados...')
      llOk = self.mxMigrarUsuariosNuevos()
      if llOk:
         self.loSql1.omCommit()
      self.loSql.omDisconnect()
      self.loSql1.omDisconnect()
      print ('Termino Proceso de Migrando Empleados...')
      return llOk

   def mxMigrarUsuariosNuevos(self):
      lcSql = "SELECT A.cCodUsu, B.cNroDni, A.cEstado, A.cNivel, A.cCargo, A.cUsuInf, B.cNombre, B.cSexo, B.cEstado, SUBSTRING(B.cNroCel, 1, 12), SUBSTRING(B.cEmail, 1, 90), B.cClave, B.dNacimi, B.cDirecc \
               FROM s01tusu A \
               INNER JOIN S01MPER B ON B.cNroDni = A.cNroDni  \
               WHERE A.tModifi::DATE = NOW()::DATE ORDER BY A.tModifi"
      print(lcSql)
      R1 = self.loSql.omExecRS(lcSql)
      for laTmp in R1:
         lcSql = "SELECT cCodUsu FROM S01TUSU WHERE cCodUsu = '%s'"%(laTmp[0])
         #print(lcSql)
         R2 = self.loSql1.omExecRS(lcSql)
         if R2:
            continue
         lcSql = "SELECT cNroDni FROM S01MPER WHERE cNroDni = '%s'"%(laTmp[1])
         #print(lcSql)
         R2 = self.loSql1.omExecRS(lcSql)
         if not R2:
            lcNombre = laTmp[6].replace("'","''")
            lcDireccion = laTmp[13].replace("'","''")
            lcSql = "INSERT INTO S01MPER (cNroDni, cEstado, cNombre, cSexo, cNroCel, cEmail, cClave, cUsuCod, dNacimi, cDirecc) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '0666','%s', '%s')"%(laTmp[1], laTmp[8], lcNombre, laTmp[7], laTmp[9], laTmp[10], laTmp[11], laTmp[12], lcDireccion)
            print (lcSql)
            llOk = self.loSql1.omExec(lcSql)
            if not llOk:
               #print (lcSql)
               self.pcError = '* ERROR AL INSERTAR S01MPER'
               return False
         lcSql = "INSERT INTO S01TUSU (cCodUsu, cNroDni, cEstado, cNivel, cCargo, cUsuInf, cUsuCod) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '0666')"%(laTmp[0], laTmp[1], laTmp[2], laTmp[3], laTmp[4], laTmp[5])
         llOk1 = self.loSql1.omExec(lcSql)
         if not llOk:
            #print (lcSql)
            self.pcError = '* ERROR AL INSERTAR S01TUSU'
            return False
      return True
   
   # Migra Centro de Responsabilidad nuevos creados en UCSMERP A UCSMAFJ
   # 2023-03-30 Creacion
   def omMigrarCResponsabilidadNuevos(self):
      print ('Conectando con UCSMERP ...')
      llOk = self.loSql.omConnect()   # UCSMERP
      if not llOk:
         self.pcError = 'ERROR CONECTAR DB UCSMERP'
         return False
      print ('Conectando con UCSMAFJ ...')
      llOk = self.loSql1.omConnect(8)   # UCSMAFJ
      if not llOk:
         self.pcError = 'ERROR CONECTAR DB UCSMAFJ'
         return False
      print ('Migrando Centros de Responsabilidad...')
      llOk = self.mxMigrarCResponsabilidadNuevos()
      if llOk:
         self.loSql1.omCommit()
      self.loSql.omDisconnect()
      self.loSql1.omDisconnect()
      print ('Proceso terminado...')
      return llOk
   
   def mxMigrarCResponsabilidadNuevos(self):
      lcSql = "SELECT A.cCenRes, A.cEstado, A.cDescri, A.cCenCos, A.cResOld, A.cDesRes, B.cCenCos, B.cDescri, B.cEstado, B.cUniAca, B.cNivel, B.cTipEst, B.cEstPre, B.cCodAnt, B.cOrden, B.cTipo, B.cDepend, B.xCenCos, B.cClase \
               FROM S01TRES A \
               INNER JOIN S01TCCO B ON B.cCenCos = A.cCenCos  \
               WHERE A.tModifi::DATE = NOW()::DATE ORDER BY A.tModifi"
      #print(lcSql)
      R1 = self.loSql.omExecRS(lcSql)
      for laTmp in R1:
         lcSql = "SELECT cCenRes FROM S01TRES WHERE cCenRes = '%s'"%(laTmp[0])
         #print(lcSql)
         R2 = self.loSql1.omExecRS(lcSql)
         if R2:
            continue
         lcSql = "SELECT cCenCos FROM S01TCCO WHERE cCenCos = '%s'"%(laTmp[6])
         #print(lcSql)
         R2 = self.loSql1.omExecRS(lcSql)
         if not R2:
            lcNombre = laTmp[6].replace("'","''")
            lcDireccion = laTmp[13].replace("'","''")
            lcSql = "INSERT INTO S01TCCO (cCenCos, cDescri, cEstado, cUniAca, cNivel, cTipEst, cEstPre, cCodAnt, cAfecta, cOrden, cUsuCod, cTipo, cDepend, cClase) \
                     VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', 'N', '%s', '0666','%s', '%s', '%s')"%\
                        (laTmp[6], laTmp[7], laTmp[8], laTmp[9], laTmp[10], laTmp[11], laTmp[12], laTmp[13], laTmp[14], laTmp[15], laTmp[6], laTmp[18])
            print (lcSql)
            llOk = self.loSql1.omExec(lcSql)
            if not llOk:
               #print (lcSql)
               self.pcError = '* ERROR AL INSERTAR S01TCCO'
               return False
         lcSql = "INSERT INTO S01TRES (cCenRes, cEstado, cDescri, cCencos, cResOld, cDesRes, cUsuCod) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '0666')"%(laTmp[0], laTmp[1], laTmp[2], laTmp[3], laTmp[4], laTmp[5])
         llOk = self.loSql1.omExec(lcSql)
         if not llOk:
            #print (lcSql)
            self.pcError = '* ERROR AL INSERTAR S01TRES'
            return False
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
   elif laData['ID'] == 'AFJ0001':
      lo = CMigrar()
      lo.paData = laData
      llOk = lo.omMigrarA04MAFJ_Inventario()
      if llOk:
         print(json.dumps(lo.paData))
         return
   elif laData['ID'] == 'AFJ0002':
      lo = CMigrar()
      lo.paData = laData
      llOk = lo.omMigrarUsuariosNuevos()
      if llOk:
         print(json.dumps(lo.paData))
         return
   elif laData['ID'] == 'AFJ0003':
      lo = CMigrar()
      lo.paData = laData
      llOk = lo.omMigrarCResponsabilidadNuevos()
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

#  
#python3 ERPWS/Clases/CMigrar.py '{"ID":"AFJ0002"}' 