#! /bin/bash
i=0
while true
do
   echo 'INICIO Actualizacion Matriculas Idiomas' >> LogTimeERP-II.txt
   date >> LogTimeERP-II.txt
   python3 MenuERP.py 2
   echo 'FIN Actualizacion Matriculas Idiomas' >> LogTimeERP-II.txt
   date >> LogTimeERP-II.txt
   lcHora=$(date +%H)
   lcDia=$(date +%-u)
   #echo "$NOW"
   if [ "$lcHora" = '00' ]
      then
      echo 'INICIO CARGAR CANTIDAD DE JURADOS POR UNIDAD ACADEMICA' >> LogTimeERP-II.txt
      date >> LogTimeERP-II.txt
      python3 MenuERP.py 1
      echo 'FIN CARGAR CANTIDAD DE JURADOS POR UNIDAD ACADEMICA' >> LogTimeERP-II.txt
      date >> LogTimeERP-II.txt
      sleep 3600
   elif [ "$lcDia" -ge "1" ] && [ "$lcDia" -le "5" ]; then
      if [ "$lcHora" -ge "08" ] && [ "$lcHora" -le "19" ]; then
         sleep 120
      else
         sleep 600
      fi
   else
      sleep 1800
   fi
done
