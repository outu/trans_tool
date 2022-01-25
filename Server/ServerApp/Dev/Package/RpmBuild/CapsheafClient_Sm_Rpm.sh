#!/bin/sh

rpm -e prelink


rm -rf `find .. -name ".gitkeep"`


BUILD_RPM_PATH=$(cd "${0%/*}" && echo "$PWD/")
export RPM_PATH=${BUILD_RPM_PATH}/;
export ROOT_PATH=${BUILD_RPM_PATH}/;
export BUILD_RPM_STATUS_PATH="${ROOT_PATH}capsheaf_build_status"

mkdir $BUILD_RPM_STATUS_PATH





echo "Environment: "
env


export PRODUCT_INSTALLER_BASE_NAME=capsheafclient
export PRODUCT_SUMMARY=Capsheaf Backup and Recovery System
export PRODUCT_URL=https://www.capsheaf.com.cn


if ! [ -x "$(command -v rpmbuild)" ];
then
    echo 'Warning: rpmbuild is not installed, trying to install...'
    yum -y install rpm-build
    if [ $? -ne 0 ]; then
        echo 'install rpm-build failed' > "${BUILD_RPM_STATUS_PATH}/failed"
        exit 1
    fi
fi


if ! [ -x "$(command -v unzip)" ];
then
    echo 'Warning: unzip is not installed, trying to install...'
    yum -y install unzip
    if [ $? -ne 0 ]; then
        echo 'install unzip failed' > "${BUILD_RPM_STATUS_PATH}/failed"
        exit 1
    fi
fi


while getopts "e:v:a:r:g:p:" opt
do
    case $opt in
        e)
        export ENCRYPT=$OPTARG
        ;;
        v)
        export VERSION=$OPTARG
        ;;
        g)
        export GIT_VERSION=$OPTARG
        ;;
        r)
        export RELEASE=$OPTARG
        ;;
        a)
        export ARCH=$OPTARG
        ;;
        p)
        export ZIP_PACKAGE_PATH=$OPTARG
        ;;
        ?)
        echo "unknown option${OPTARG}" > "${BUILD_RPM_STATUS_PATH}/failed"
        exit 1
        ;;
    esac
done


export DATE_NOW=`date +%Y%m%d`

export INSTALLER_FILE_NAME="${PRODUCT_INSTALLER_BASE_NAME}-${ENCRYPT}.${VERSION}.${DATE_NOW}.${GIT_VERSION}-${RELEASE}.${ARCH}"
export RPM_OUTPUT_DIR=${ROOT_PATH}Builds


export RPM_BUILD_TEMP_DIR="/root/rpmbuild"
export RPM_SOURCE_DIR=${RPM_BUILD_TEMP_DIR}/SOURCES
export RPM_BUILD_DIR=${RPM_BUILD_TEMP_DIR}/BUILD
export RPM_SRPM_DIR=${RPM_BUILD_TEMP_DIR}/SRPM
export RPM_BUILD_ROOT_DIR=${RPM_BUILD_TEMP_DIR}/BUILDROOT


if [ -z "$TAR_SOURCE_FILE" ]; then
    export TAR_SOURCE_FILE="${RPM_SOURCE_DIR}/${PRODUCT_INSTALLER_BASE_NAME}-${VERSION}.tar.gz"
fi



echo "Package Version:"
echo "    Version: ${VERSION}"
echo "    GitVersion: ${GIT_VERSION}"
echo "    Release: ${RELEASE}"
echo "    Output:  ${INSTALLER_FILE_NAME}.rpm"

echo "Package Information:"
echo "    Package path for packaging: ${PACKAGE_PATH}"
echo "    Dev path:                   ${DEV_PATH}"
echo "    Root path of Capsheaf:      ${ROOT_PATH}"
echo "    Tar source file path:       ${TAR_SOURCE_FILE}"

echo "Rpm Information:"
echo "    Rpm output directory:       ${RPM_OUTPUT_DIR}/"
echo "    Source rpm output directory:${RPM_SRPM_DIR}/"
echo "    Rpm installer dummy install:${RPM_BUILD_ROOT_DIR}/"
echo ""

if [ -z "$RPM_LONG_VERSION" ]; then
    export RPM_LONG_VERSION="${RPM_SOURCE_DIR}/${PRODUCT_INSTALLER_BASE_NAME}-${VERSION}.tar.gz"
fi

###
rm -rf /root/rpmbuild/
mkdir -p /root/rpmbuild/{BUILD,RPMS,SOURCES,SPECS,SRPMS}

rm -rf ${RPM_PATH}CapsheafClient
unzip -d ${RPM_PATH}CapsheafClient ${ZIP_PACKAGE_PATH}


tar --blocking-factor=10240 --checkpoint=1 -czf "${TAR_SOURCE_FILE}" \
    -C ${RPM_PATH}CapsheafClient \
    bin/ \
    data/ \
    doc/ \
    etc/ \
    lib/ \
    tmp




rpmbuild --target=${ARCH} -ba ${BUILD_RPM_PATH}CapsheafClient_Sm_Rpm.spec

if [ $? -ne 0 ]; then
    echo 'build rpm failed' > "${BUILD_RPM_STATUS_PATH}/failed"
    exit 1
else
    RPM_PACKAGE_NAME=`ls "${RPM_BUILD_TEMP_DIR}/RPMS/${ARCH}/"`
    echo "${RPM_BUILD_TEMP_DIR}/RPMS/${ARCH}/${RPM_PACKAGE_NAME}" > "${BUILD_RPM_STATUS_PATH}/succeed"
    exit 1
fi





