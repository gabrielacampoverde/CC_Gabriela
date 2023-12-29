#! /bin/bash
while true
do
   echo 'INICIO BACKUP CORPERP ' >> LogTime.txt
   date >> LogTime.txt
   PGPASSWORD='12Consorcio34' pg_dump -h 10.0.7.93 -p 5432 -U postgres -F c -b -v -f CORPERP.backup 'CORPERP'
   echo 'FIN BACKUP CORPERP ' >> LogTime.txt
   date >> LogTime.txt
   echo 'INICIO BACKUP ISPMERP ' >> LogTime.txt
   date >> LogTime.txt
   PGPASSWORD='70840304' pg_dump -h 10.0.7.162 -p 5432 -U u70840304 -F c -b -v -f ISPMERP.backup 'ISPMERP'
   echo 'FIN BACKUP ISPMERP ' >> LogTime.txt
   date >> LogTime.txt
   ./execBackupFTPII.sh
   sleep 1800
done
