import hashlib 
import psycopg2 
# import pymssql 
import json 
 
class CSql(): 
    def __init__(self):
        self.h       = None 
        self.plOk    = True 
        self.pcError = None 
        self.RS      = None
 
    def omConnect(self, p_nDB = None):
        self.plOk = True  
        lcConnect = "host=localhost dbname=UCSMERP user=postgres password=postgres port=5432"
        # lcConnect = "host=localhost dbname=UCSM_FPM user=postgres password=postgres port=5432"  
        # lcConnect = "host=10.0.7.170 dbname=UCSMERP port=5432 user=ucsmerpweb password=12UC\$M3RP34";  
        if p_nDB == 1:  
           lcConnect = "host=localhost dbname=UCSMListener user=postgres password=postgres port=5432" 
        elif p_nDB == 2:  
           #lcConnect = "host=10.0.7.159 dbname=UCSMINS port=5432 user=ucsmerpweb password=7d88531d42f806ec28a446c3fe0448ee"
           lcConnect = "host=localhost dbname=UCSMINS user=postgres password=postgres port=5432" 
           #lcConnect = "host=localhost dbname=UCSMINS port=5432 user=ucsmerpweb password=7d88531d42f806ec28a446c3fe0448ee"  
        elif p_nDB == 3:  
           lcConnect = "host=localhost dbname=UCSMASBANC user=postgres password=postgres port=5432" 
        elif p_nDB == 4:  
           lcConnect = "host=localhost dbname=UCSMFactElec user=postgres password=postgres port=5432"
        elif p_nDB == 5: 
           lcConnect = "host=localhost dbname=UCSMBack user=postgres password=postgres port=5432"
           #host=10.10.110.13 dbname=BoticaFact port=5432 user=postgres password=PostgresAliviari159; 
        elif p_nDB == 6: 
           # lcConnect = "host=localhost dbname=UCSMERP_0111 user=postgres password=postgres port=5432"
           lcConnect = "host=localhost dbname=UCSM_1103 user=postgres password=postgres port=5432"
        elif p_nDB == 7: 
           # lcConnect = "host=localhost dbname=UCSMERP_0111 user=postgres password=postgres port=5432"
           lcConnect = "host=localhost dbname=UCSMDEEP user=postgres password=postgres port=5432"
        elif p_nDB == -1: 
           # lcConnect = "host=localhost dbname=UCSMERP_0111 user=postgres password=postgres port=5432"
           lcConnect = "host=localhost dbname=UCSM1 user=postgres password=postgres port=5432"
        else:
           lcConnect = "host=localhost dbname=UCSMERP user=postgres password=postgres port=5432"
           #host=10.0.7.170 dbname=UCSMERP_DW port=5432 user=ucsmerpweb password=12UC\$M3RP34";  
           # lcConnect = "host=10.0.7.170 dbname=UCSMERP port=5432 user=ucsmerpweb password=12UC\$M3RP34"; 
        #print(lcConnect)
        try:  
           self.h = psycopg2.connect(lcConnect)   
        except psycopg2.DatabaseError:  
           self.plOk = False  
           self.pcError = 'ERROR AL CONECTAR CON LA BASE DE DATOS'  
        return self.plOk  
 
    def omExecRS_old(self, p_cSql): 
        #print p_cSql 
        self.plOk = True 
        lcCursor = self.h.cursor() 
        try: 
           lcCursor.execute(p_cSql) 
           RS = lcCursor.fetchall() 
        except psycopg2.DatabaseError as e: 
           self.plOk = False 
           # print e.message 
           self.pcError = 'ERROR AL EJECUTAR COMANDO SELECT' 
           RS = None 
        return RS 
 
    def omExecRS(self, p_cSql): 
        #print p_cSql 
        lcCursor = self.h.cursor() 
        try: 
           lcCursor.execute(p_cSql) 
        except psycopg2.DatabaseError as e: 
           lcCursor = None 
        return lcCursor 
 
    def fetch(self, p_cCursor):
        RS = p_cCursor.fetchone() 
        return RS 
 
    def omExec(self, p_cSql): 
        #print p_cSql 
        self.plOk = True 
        lcCursor = self.h.cursor() 
        try: 
           lcCursor.execute(p_cSql) 
        except psycopg2.DatabaseError as e: 
           self.plOk = False 
           # print e.message 
           self.pcError = 'ERROR AL ACTUALIZAR LA BASE DE DATOS' 
        return self.plOk 
 
    def omDisconnect(self): 
        self.h.close() 
 
    def omCommit(self): 
        self.h.commit() 
 
class CSqlServer(): 
   def __init__(self): 
       self.h       = None 
       self.plOk    = True 
       self.pcError = None 
 
   def omConnect(self, p_nDB = 0): 
       self.plOk = True 
       try: 
          if p_nDB == 1: 
             # DB Fotografias 
             self.h = pymssql.connect("10.0.2.78\PICDB", "userAppUCSM", "4pp$UcSm", "UCSM_PIC") 
          else: 
             # DB Matriculas 
             self.h = pymssql.connect("10.0.2.61:1433\SVRDB01", "userAppUCSM", "4pp$UcSm", "UCSM") 
       except: 
          self.plOk = False 
          self.pcError = 'ERROR AL CONECTAR CON SQL-SERVER' 
       return self.plOk 
 
   def omExecRS(self, p_cSql): 
       #print p_cSql 
       self.plOk = True 
       lcCursor = self.h.cursor() 
       try: 
          lcCursor.execute(p_cSql) 
          RS = lcCursor.fetchall() 
       except pymssql.DatabaseError as e: 
          # print e.message 
          self.plOk = False 
          self.pcError = 'ERROR AL EJECUTAR COMANDO SQL' 
          RS = None 
       return RS 
 
   def omExec(self, p_cSql): 
       self.plOk = True 
       lcCursor = self.h.cursor() 
       try: 
          lcCursor.execute(p_cSql) 
       except pymssql.DatabaseError as e: 
          self.plOk = False 
          self.pcError = 'ERROR AL ACTUALIZAR BASE DE DATOS' 
          # print e.message 
       return self.plOk 
 
   def omDisconnect(self): 
       self.h.close() 
 
   def omCommit(self): 
       self.h.commit() 
