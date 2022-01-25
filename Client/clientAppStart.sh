#!/bin/sh
BASE_PATH=`cd ${0%/*} && echo $PWD/`
INTERPRETER="${BASE_PATH}/Bin/Interpreter/php.sh"
ENTRY="${BASE_PATH}/ClientApp/Public/Client.php"

if [ ! -d "${BASE_PATH}Tmp/" ]; then
    mkdir -m777 -p "${BASE_PATH}Tmp/"
fi

$INTERPRETER $ENTRY -v $*