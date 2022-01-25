#!/bin/sh
#Opreate System Check Script for Linux like systems
#Vendor: www.capsheaf.com.cn
#Author: admin@yantao.info
#CAUTION: Edit this file with line ending '\n'.

# Can not use function keyword in some OS
showUsage()
{
cat <<EOF
Author: tsoftware <admin@yantao.info>
USAGE: GetOsInfo.sh [FORMAT]
        FORMAT      Default format is: "OS_DIST:DISTRIBUTION:OS_VER:VERSION:OS_ARCHITECTURE"
                    Can CUSTOM result by changing Position and Separator.
EOF

}

# Can not use "double equal" to compare string in some OS, use "single equal" instead
if [ "$1" = "--help" -o "$1" =  "-h" ]
then
    showUsage
    exit 1;
fi

# use lsb first
which lsb_release >/dev/null 2>&1
if [ "$?" -eq 0 ]
then
    OS_DIST=`lsb_release -is`
    OS_VER=`lsb_release -rs`
else
    #echo "NOT EXISTS";
    OS_RELEASE=`cat /etc/*-release`
    if echo $OS_RELEASE | grep -q '^DISTRIB_ID=' ; then # For lsb-release file
        OS_DIST=`echo $OS_RELEASE | grep -q '^DISTRIB_ID=' | awk -F'=' '{ print $2; exit;}'`;
        OS_VER=`echo $OS_RELEASE | grep -q '^DISTRIB_RELEASE=' | awk -F'=' '{ print $2; exit;}'`;
    elif echo $OS_RELEASE | grep -q '^ID=' ; then # For os-release file
        OS_DIST=`echo $OS_RELEASE | grep -q '^ID=' | awk -F'=' '{ print $2; exit;}'`;
        OS_VER=`echo $OS_RELEASE | grep -q '^VERSION_ID=' | awk -F'=' '{ print $2; exit;}'`;
        if [ -z "$OS_VERSION" ]; then
            OS_VERSION=`echo $OS_RELEASE | grep -q 'VERSION=' | awk -F'=' '{ print $2; exit;}'`;
        fi
    elif echo $OS_RELEASE | grep -q 'CentOS' ; then # For CentOS version 5,6
        OS_DIST="CentOS"
        LINE=`echo $OS_RELEASE | grep -i '^CentOS release '` && OS_VER=`echo $LINE | awk '{ print $3; exit;}'`;
        LINE=`echo $OS_RELEASE | grep -i '^CentOS Linux release '` && OS_VER=`echo $LINE | awk '{ print $4; exit;}'`
    elif echo $OS_RELEASE | grep -q 'Red Hat' ; then # For CentOS version 5,6
        OS_DIST="RedHatEnterpriseServer"
        LINE=`echo $OS_RELEASE | grep -i '^Red Hat Enterprise Linux Server release '` && OS_VER=`echo $LINE | awk '{ print $7; exit;}'`;
    elif echo $OS_RELEASE | grep -q 'Fedora' ; then
        OS_DIST="Fedora"
        LINE=`echo $OS_RELEASE | grep -i '^Fedora release '` && OS_VER=`echo $LINE | awk '{ print $3; exit;}'`;

    # CAN STILL HANDLE OTHER SPECIAL CASE THERE

    else
        OS_DIST="UnSupported"
        OS_VER="Unknown"
    fi
fi

# To Uppercase
OS_DIST=`echo "$OS_DIST" | awk '{$1=$1;print}' | tr '[:lower:]' '[:upper:]'` # awk is used for trim leading and trailing space or tab characters and also squeeze sequences of tabs and spaces into a single space.
OS_ARCHITECTURE=`arch | tr '[:upper:]' '[:lower:]'`
OS_VERSION_MAJOR=${OS_VER%%.*} # Notice: Some system not have version, so maybe empty

case "$OS_DIST" in
    "CENTOS" | "RHEL" | "REDHATENTERPRISESERVER" | "ORACLESERVER" | "FEDORA" | "AMZN" | "NEOKYLIN")
        DISTRIBUTION="RHEL"

        if [ "${OS_VERSION_MAJOR}" == " " ]; then
            VERSION=6
        else
            VERSION=$OS_VERSION_MAJOR
        fi
        ;;
    "DEBIAN" | "UBUNTU" | "KALI" | "RASPBIAN")
        DISTRIBUTION="DEBIAN"
        VERSION=$OS_VERSION_MAJOR
        ;;
    "ARCH" | "ANTERGOS")
        DISTRIBUTION="ARCH"
        VERSION=$OS_VERSION_MAJOR
        ;;
    "NEOKYLIN LINUX DESKTOP" | "NEOKYLINADVANCEDSERVER")
        DISTRIBUTION="RHEL"
        VERSION=$OS_VERSION_MAJOR
        ;;
    "REDFLAG")
        DISTRIBUTION="RHEL"
        VERSION=6
        ;;
    *) # Others
        DISTRIBUTION=$OS_DIST
        VERSION=$OS_VERSION_MAJOR
        ;;

esac


if [ -z "$1" ];
then
    # DEFAULT FORMAT=RAW_OS_NAME:OS_LIKE:FULL_VERSION:MAJOR_VERSION:OS_ARCH
    # Can use "awk -F':' '{ print $2 }'" to split the result want to use
    echo $OS_DIST:$DISTRIBUTION:$OS_VER:$VERSION:$OS_ARCHITECTURE
else
    FORMAT="$1"
    for VAR_NAME in "OS_DIST" "DISTRIBUTION" "OS_VER" "VERSION" "OS_ARCHITECTURE"
    do
        FORMAT=${FORMAT//${VAR_NAME}/${!VAR_NAME}}
    done
    echo "$FORMAT"
fi

