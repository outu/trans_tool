%define PRODUCT_INSTALLER_BASE_NAME             %{getenv:PRODUCT_INSTALLER_BASE_NAME}
%define TAR_SOURCE_FILE                         %{getenv:TAR_SOURCE_FILE}
%define ENCRYPT                                 %{getenv:ENCRYPT}
%define VERSION                                 %{getenv:VERSION}
%define RELEASE                                 %{getenv:RELEASE}
%define DATE                                    %{getenv:DATE_NOW}
%define GIT_VERSION                             %{getenv:GIT_VERSION}
%define PRODUCT_SUMMARY                         %{getenv:PRODUCT_SUMMARY}
%define PRODUCT_URL                             %{getenv:PRODUCT_URL}



Name:       %{PRODUCT_INSTALLER_BASE_NAME}
Version:    %{ENCRYPT}.%{VERSION}.%{DATE}.%{GIT_VERSION}
Release:    %{RELEASE}
Summary:    %{PRODUCT_SUMMARY}
Group:      Applications/Data
License:    commercial
URL:        %{PRODUCT_URL}
Vendor:     Capsheaf Co., Ltd.
Packager:   capsheaf <admin@capsheaf.com.cn>
Source0:    %{TAR_SOURCE_FILE}
AutoReqProv: no


%description
%{PRODUCT_SUMMARY}

%prep
%setup -c %{PRODUCT_INSTALLER_BASE_NAME}

ls -al %{_builddir}/%{name}-%{version}/
export BUILD_DIR=%{_builddir}/%{name}-%{version}/


%install

install -d -D "%{_builddir}/%{name}-%{version}/" "%{buildroot}/opt/BFYHF/cpc/"
cp -rf %{_builddir}/%{name}-%{version}/* "%{buildroot}/opt/BFYHF/cpc/"



%files
/opt/BFYHF/cpc/

#%docdir

%post

chmod 755 -R "/opt/BFYHF/cpc/" > /dev/null 2>&1
# 解决oracle模块任务完成后因权限问题无法删除文件
chmod 777 -R "/opt/BFYHF/cpc/data/drc_client/task/" > /dev/null 2>&1
chmod 777 -R "/opt/BFYHF/cpc/data/drc_client/control/" > /dev/null 2>&1

ln -sbf /opt/BFYHF/cpc/etc/rc.d/capsheaf "/etc/rc.d/init.d/capsheaf" > /dev/null 2>&1
ln -sbf /opt/BFYHF/cpc/etc/rc.d/capsheaf "/etc/rc.d/rc3.d/S85capsheaf" > /dev/null 2>&1
ln -sbf /opt/BFYHF/cpc/etc/rc.d/capsheaf "/etc/rc.d/rc5.d/S85capsheaf" > /dev/null 2>&1

%postun

unlink /etc/rc.d/init.d/capsheaf > /dev/null 2>&1
unlink /etc/rc.d/rc3.d/S85capsheaf > /dev/null 2>&1
unlink /etc/rc.d/rc5.d/S85capsheaf > /dev/null 2>&1

kill `ps aux | grep -w /opt/BFYHF/cpc | grep -v grep | awk '{print $2}'` > /dev/null 2>&1
rm -rf "/opt/BFYHF/cpc" > /dev/null 2>&1
