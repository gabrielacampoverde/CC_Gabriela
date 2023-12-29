# encoding=utf8  
import datetime
from datetime import timedelta
import random

class CBase:

   def __init__(self):
       self.pcError = None
       self.loSql   = None

class CDate(CBase):
   pcClave = None
   
   def valDate(self, p_cFecha):
       llOk = True
       try:
          ldFecha = datetime.datetime.strptime(p_cFecha, "%Y-%m-%d").date()
       except:
          llOk = False
       return llOk
  
   def mxValDate(self, p_cFecha):
       llOk = True
       try:
          ldFecha = datetime.datetime.strptime(p_cFecha, "%Y-%m-%d").date()
       except:
          ldFecha = None
       return ldFecha
  
   def add(self, p_cFecha, p_nDias):
       llOk = self.valDate(p_cFecha)
       if not llOk:
          return None
       ldFecha = self.mxValDate(p_cFecha)
       ldFecha = ldFecha + timedelta(days = p_nDias)
       return ldFecha.strftime('%Y-%m-%d')
      
   def diff(self, p_cFecha1, p_cFecha2):
       llOk = self.valDate(p_cFecha1)
       if not llOk:
          return None
       llOk = self.valDate(p_cFecha2)
       if not llOk:
          return None
       ldFecha1 = self.mxValDate(p_cFecha1)
       ldFecha2 = self.mxValDate(p_cFecha2)
       d = ldFecha1 - ldFecha2
       return d.days

   def dow(self, p_cFecha):
       llOk = self.valDate(p_cFecha)
       if not llOk:
          return None
       ldFecha = self.mxValDate(p_cFecha)
       return ldFecha.weekday()

   def day(self, p_cFecha):
       llOk = self.valDate(p_cFecha)
       if not llOk:
          return None
       ldFecha = self.mxValDate(p_cFecha)
       return int(ldFecha.strftime('%d'))

   def month(self, p_cFecha):
       llOk = self.valDate(p_cFecha)
       if not llOk:
          return None
       ldFecha = self.mxValDate(p_cFecha)
       return int(ldFecha.strftime('%m'))

   def month_name(self, p_nMes):
       switcher = {
           1 : "Enero",
           2 : "Febrero",
           3 : "Marzo",
           4 : "Abril",
           5 : "Mayo",
           6 : "Junio",
           7 : "Julio",
           8 : "Agosto",
           9 : "Setiembre",
           10 : "Octubre",
           11 : "Noviembre",
           12 : "Diciembre",
       }
       return switcher.get(p_nMes, "Mes invalido")
   
   def diff_years(self, p_cFecha, anios):
       llOk = self.valDate(p_cFecha)
       if not llOk:
          return None
       ldFecha = self.mxValDate(p_cFecha)
       lnDia = int(ldFecha.strftime('%d'))
       lnMes = int(ldFecha.strftime('%m'))
       lnAnio = int(ldFecha.strftime('%Y'))
       #SI ES FEB
       if lnMes == 2:
          ldtemp = ldFecha + datetime.timedelta(days=1)
          if int(ldtemp.strftime('%m')) != lnMes:
             ldFecha = ldFecha + datetime.timedelta(days=1)
             lnDia = int(ldFecha.strftime('%d'))
             lnMes = int(ldFecha.strftime('%m'))
             lnAnio = int(ldFecha.strftime('%Y')) - 1
             ldFecha = '%s-%s-%s'%(lnAnio,lnMes,lnDia)
             ldFecha = self.mxValDate(ldFecha)
             ldFecha = ldFecha - datetime.timedelta(days=1)
             lnDia = int(ldFecha.strftime('%d'))
             lnMes = int(ldFecha.strftime('%m'))
             lnAnio = int(ldFecha.strftime('%Y'))
          else:
             lnAnio = lnAnio - anios
       else:
          lnAnio = lnAnio - anios
       ldFecha = '%s-%s-%s'%(lnAnio,lnMes,lnDia)
       ldFecha = self.mxValDate(ldFecha)
       return ldFecha.strftime('%Y-%m-%d')

   def year(self, p_cFecha):
       llOk = self.valDate(p_cFecha)
       if not llOk:
          return None
       ldFecha = self.mxValDate(p_cFecha)
       return int(ldFecha.strftime('%Y'))

def fxFileRep():
    lcFile = str(random.random())
    lcFile = lcFile[-8:]
    lcFile = lcFile.replace('.', '')
    return 'R' + lcFile

def fxString(p_cLinea, p_nLenght):
    lcLinea = p_cLinea + ' ' * p_nLenght
    '''
    i = lcLinea.count('Ñ')
    i += lcLinea.count('Á')
    i += lcLinea.count('É')
    i += lcLinea.count('Í')
    i += lcLinea.count('Ó')
    i += lcLinea.count('Ú')
    lcLinea = lcLinea[:p_nLenght + i]
    '''
    #print '1)', lcLinea
    lcLinea = lcLinea.replace('Á', 'A')
    lcLinea = lcLinea.replace('É', 'E')
    lcLinea = lcLinea.replace('Í', 'I')
    lcLinea = lcLinea.replace('Ó', 'O')
    lcLinea = lcLinea.replace('Ó', 'O')
    #print '2)', lcLinea
    lcLinea = lcLinea.replace('Ú', 'U')
    i = lcLinea.count('Ñ')
    lcLinea = lcLinea[:p_nLenght + i]
    return lcLinea

def fxString_1(p_cLinea, p_nLenght):
    lcLinea = p_cLinea + ' ' * p_nLenght
    i = lcLinea.count('Ñ')
    i += lcLinea.count('Á')
    i += lcLinea.count('É')
    i += lcLinea.count('Í')
    i += lcLinea.count('Ó')
    i += lcLinea.count('Ú')
    lcLinea = lcLinea[:p_nLenght + i]
    return lcLinea

def fxNumber(p_nNumero, p_nLenght, p_nDec = 2):
    p_nNumero = float(p_nNumero) + 0.001
    #lcLinea = "{:12,.2f}".format(p_nNumero)
    lcFormat = "{:12,.%sf}"%(p_nDec) 
    lcLinea = lcFormat.format(p_nNumero)
    #if p_nDec == 3:
    #   print lcFormat, lcLinea
    lcLinea = ' ' * p_nLenght + lcLinea 
    lcLinea = lcLinea[-p_nLenght:]
    return lcLinea

def fxInteger(p_nNumero, p_nLenght):
    p_nNumero = int(p_nNumero)
    lcLinea = str(p_nNumero)
    lcLinea = ' ' * p_nLenght + lcLinea 
    lcLinea = lcLinea[-p_nLenght:]
    return lcLinea

def fxMeses(p_nMes):
    if p_nMes < 1 or p_nMes > 12:
       return 'Error'
    laMeses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre', 'Octubre', 'Noviembre', 'Diciembre']
    return laMeses[p_nMes - 1]

def fxFechaLarga(p_dFecha):
    lnMes = int(p_dFecha[5: -3])
    lcFecha = p_dFecha[-2:] + ' de ' + fxMeses(lnMes) + ' del ' + p_dFecha[:4]
    return lcFecha
    
def fxFechaMes(p_dFecha):
    lcMes = fxMeses(int(p_dFecha[5: -3]))
    lcMes = lcMes[:3]
    lcMes = lcMes.upper()
    #lcFecha = p_dFecha[-2:] + ' de ' + fxMeses(lnMes) + ' del ' + p_dFecha[:4]
    lcFecha = p_dFecha[:5] + lcMes + p_dFecha[-3:]
    return lcFecha

def fxCorrelativo(p_cCodigo):
    lcCodigo = p_cCodigo
    i = len(lcCodigo) - 1
    while i >= 0:
       lcDigito = p_cCodigo[i]
       if lcDigito == '9':
          lcDigito = 'A'
       elif lcDigito < '9':
          lcDigito = str(int(lcDigito) + 1)
       elif lcDigito < 'Z':
          lcDigito = chr(ord(lcDigito) + 1)
       elif lcDigito == 'Z':
          lcDigito = '0'
       lcCodigo = lcCodigo[:i] + lcDigito + lcCodigo[i + 1:]
       if lcDigito != '0':
          break
       i -= 1
    return lcCodigo;

