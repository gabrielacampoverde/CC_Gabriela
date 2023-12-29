#! /bin/bash
d=$(date +%u)
lcFileCORP="CORPERP_$d.backup"
lcFileISPM="ISPMERP_$d.backup"
ftp -inv 10.11.103.18<<FINFTP
        user anonymous brendius94@gmail.com
        binary
        lcd /var/www/html/ERP-II/Jobs/
        cd D:/BackupFTP/
        put CORPERP.backup $lcFileCORP
        put ISPMERP.backup $lcFileISPM
        bye
FINFTP
