#!/bin/bash

if [ -f "./mysql-backup-config.sh" ];
then
    source "./mysql-backup-config.sh"
else
    DIRECTORY_CONFIG="mysql-backup-configs"
    DIRECTORY_BACKUPS="backups"
fi

DATE_NOW=$(date +"%y%m%d")
DATE_NOW_DAY=$(date +"%d")
MYSQLDUMP="$(which mysqldump)"

function backup_database {

    printf "Backing up $DIRECTORY_SITE...\n" | tee -a $LOGFILE

    PATH_TARGET=$USER_HOME/$DIRECTORY_BACKUPS/$DIRECTORY_SITE

    if [ -d $PATH_TARGET ]
    then
        rm -r $PATH_TARGET
        mkdir -p $PATH_TARGET
    else
        mkdir -p $PATH_TARGET
    fi

    $MYSQLDUMP -u $DB_USER -h $DB_HOST -p$DB_PASS $DB_NAME | gzip > $PATH_TARGET/${DB_NAME}_$DATE_NOW.sql.gz

}

if [ "$(ls -A $DIRECTORY_CONFIG)" ];
then

    for file in $DIRECTORY_CONFIG/*
    do
        source $file
        backup_database
    done

else

    DIRECTORY_SITE="website.com"
    DB_HOST="mysql.website.com"
    DB_USER="user"
    DB_PASS="password"
    DB_NAME="database"

    backup_database

fi

printf "Done"