#!/bin/sh

CLIENT_PATH=`cd ${0%/*} && echo $PWD/`;
BIN_DIR=`dirname  "${CLIENT_PATH}"`
OS_INFO=`${BIN_DIR}/GetOsInfo.sh DISTRIBUTIONVERSION-OS_ARCHITECTURE`

if [ "$OS_INFO" == "UNSUPPORTED-UNKNOWN" ];then
    echo "System Not Supported."
    exit 1
else
    INTERPRETER_BIN="${CLIENT_PATH}Linux/${OS_INFO}/php"
    INTERPRETER_INI="${CLIENT_PATH}Linux/${OS_INFO}/php.ini"
    EXTENSION_DIR="${CLIENT_PATH}Linux/${OS_INFO}/ext/"
    #ZEND_EXTENSION="${INTERPRETER_PATH}Linux/${OS_INFO}/ext/xdebug.so"
fi;

# May use exec to replace self
if [ "$ZEND_EXTENSION" == "" ];then
    $INTERPRETER_BIN -dextension_dir=$EXTENSION_DIR -c$INTERPRETER_INI "$@"
else
    $INTERPRETER_BIN -dextension_dir=$EXTENSION_DIR -dzend_extension=$ZEND_EXTENSION -c$INTERPRETER_INI "$@"
fi
