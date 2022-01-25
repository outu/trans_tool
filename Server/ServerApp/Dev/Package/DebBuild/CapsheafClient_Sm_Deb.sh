#!/bin/bash


rm -rf `find .. -name ".gitkeep"`

BUILD_DEB_PATH=$(cd "${0%/*}" && echo "$PWD/")
export RPM_PATH=${BUILD_DEB_PATH}/;
export ROOT_PATH=${BUILD_DEB_PATH}/;
export BUILD_DEB_STATUS_PATH="${ROOT_PATH}capsheaf_build_status"

mkdir $BUILD_DEB_STATUS_PATH




echo "Environment: "
env


export PRODUCT_INSTALLER_BASE_NAME=capsheafclient
export PRODUCT_SUMMARY=Capsheaf Backup and Recovery System
export PRODUCT_URL=https://www.capsheaf.com.cn


if ! [ -x "$(command -v dpkg-deb)" ];
then
    echo 'Warning: dpkg-deb is not installed, trying to install...'
    apt-get install dpkg-deb
    if [ $? -ne 0 ]; then
        echo 'install dpkg-deb failed' > "${BUILD_DEB_STATUS_PATH}/failed"
        exit 1
    fi
fi

if ! [ -x "$(command -v unzip)" ];
then
    echo 'Warning: unzip is not installed, trying to install...'
    apt-get install dpkg-deb
    if [ $? -ne 0 ]; then
        echo 'install unzip failed' > "${BUILD_DEB_STATUS_PATH}/failed"
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
        echo "unknown option${OPTARG}" > "${BUILD_DEB_STATUS_PATH}/failed"
        exit 1
        ;;
    esac
done

export DATE_NOW=`date +%Y%m%d`

export INSTALLER_FILE_NAME="${PRODUCT_INSTALLER_BASE_NAME}-${ENCRYPT}.${VERSION}.${DATE_NOW}.${GIT_VERSION}-${RELEASE}.${ARCH}"
export DEB_OUTPUT_DIR=${ROOT_PATH}Builds



export DEB_BUILD_TEMP_DIR="/root/debbuild"
export BUILD_DIR=${DEB_BUILD_TEMP_DIR}/CapsheafClient/BUILDROOT/
export DEB_BUILD_DIR=${DEB_BUILD_TEMP_DIR}/CapsheafClient/BUILDROOT
mkdir -p ${DEB_BUILD_TEMP_DIR}
mkdir -p ${BUILD_DIR}


echo "Package Version:"
echo "    Version: ${VERSION}"
echo "    GitVersion: ${GIT_VERSION}"
echo "    Release: ${RELEASE}"
echo "    Output:  ${INSTALLER_FILE_NAME}.deb"

echo "Package Information:"
echo "    Package path for packaging: ${PACKAGE_PATH}"
echo "    Dev path:                   ${DEV_PATH}"
echo "    Root path of Capsheaf:      ${ROOT_PATH}"
echo "    Copy to Root path:          ${BUILD_DIR}"
echo "    Patch file path:            ${PATCH_SH}"

echo "Deb Information:"
echo "    Deb output directory:       ${DEB_OUTPUT_DIR}/"
echo "    Deb installer temp folder:  ${BUILD_DIR}"
echo ""

###
rm -rf ${DEB_BUILD_TEMP_DIR}
mkdir -p ${BUILD_DIR}

mkdir -p ${BUILD_DIR}DEBIAN
mkdir -p ${BUILD_DIR}opt/BFYHF/cpc


cat <<EOT >> ${BUILD_DIR}DEBIAN/control
Package:${PRODUCT_INSTALLER_BASE_NAME}
Version:${VERSION}
Architecture:${ARCH}
Section:utils
Priority:optional
Maintainer:capsheaf
Homepage:${PRODUCT_URL}
Description:${PRODUCT_SUMMARY}
EOT


cat <<EOT >> ${BUILD_DIR}DEBIAN/postinst
#!/bin/bash
ln -sf /opt/BFYHF/cpc/etc/rc.d/capsheaf "/etc/init.d/capsheaf" > /dev/null 2>&1
ln -sf /opt/BFYHF/cpc/etc/rc.d/capsheaf "/etc/rc3.d/S85capsheaf" > /dev/null 2>&1
ln -sf /opt/BFYHF/cpc/etc/rc.d/capsheaf "/etc/rc5.d/S85capsheaf" > /dev/null 2>&1
EOT


cat <<EOT >> ${BUILD_DIR}DEBIAN/postrm
#!/bin/bash

unlink /etc/init.d/capsheaf > /dev/null 2>&1
unlink /etc/rc3.d/S85capsheaf > /dev/null 2>&1
unlink /etc/rc5.d/S85capsheaf > /dev/null 2>&1
kill \`ps aux | grep -w /opt/BFYHF/cpc | grep -v grep | awk '{print $2}'\` > /dev/null 2>&1
rm -rf /opt/BFYHF/cpc > /dev/null 2>&1
EOT
chmod 755 ${BUILD_DIR}DEBIAN/postinst
chmod 755 ${BUILD_DIR}DEBIAN/postrm


rm -rf ${RPM_PATH}CapsheafClient
unzip -d ${RPM_PATH}CapsheafClient ${ZIP_PACKAGE_PATH}


\cp -rf ${RPM_PATH}CapsheafClient/bin ${BUILD_DIR}opt/BFYHF/cpc
\cp -rf ${RPM_PATH}CapsheafClient/data ${BUILD_DIR}opt/BFYHF/cpc
\cp -rf ${RPM_PATH}CapsheafClient/doc ${BUILD_DIR}opt/BFYHF/cpc
\cp -rf ${RPM_PATH}CapsheafClient/etc ${BUILD_DIR}opt/BFYHF/cpc
\cp -rf ${RPM_PATH}CapsheafClient/lib ${BUILD_DIR}opt/BFYHF/cpc
\cp -rf ${RPM_PATH}CapsheafClient/tmp ${BUILD_DIR}opt/BFYHF/cpc



chmod 755 -R ${BUILD_DIR}opt/BFYHF/cpc
chmod 777 -R ${BUILD_DIR}opt/BFYHF/cpc/data/drc_client/task/
chmod 777 -R ${BUILD_DIR}opt/BFYHF/cpc/data/drc_client/control/

echo "Making deb package..."
dpkg-deb --build ${DEB_BUILD_DIR} ${DEB_BUILD_TEMP_DIR}/${INSTALLER_FILE_NAME}.deb


if [ $? -ne 0 ]; then
    echo 'build deb failed' > "${BUILD_DEB_STATUS_PATH}/failed"
    exit 1
else
    DEB_PACKAGE_NAME=`ls "${DEB_BUILD_TEMP_DIR}" | grep deb`
    echo "${DEB_BUILD_TEMP_DIR}/${DEB_PACKAGE_NAME}" > "${BUILD_DEB_STATUS_PATH}/succeed"
    exit 1
fi

