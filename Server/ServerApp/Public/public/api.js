
const API_INDEX = "/ServerApp.php";


const apiRequest = (sModule, sController, sAction, arrExtraParameters = {}) => {
    axios.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded';
    const params = new URLSearchParams();
    params.append('module', sModule);
    params.append('controller', sController);
    params.append('action', sAction);
    for (let k in arrExtraParameters) {
        if (arrExtraParameters.hasOwnProperty(k)) {
            params.append(k, arrExtraParameters[k]);
        }
    }

    return axios.post(API_INDEX, params).then((response) => {
        console.log("THEN_0", response);
        if (response.data.code != '200'){
            console.log('REJCET_0');
            return Promise.reject(response.data.message);
        }
        console.log("THEN_0_OK", response);

        return response;
    }).catch((error) => {
        console.log("CATCH_0", error);
        throw error;
    });
};



export const API_GET_GIT_VERSION = (arrExtraParameters) => {
    return apiRequest('Index', 'Index', 'getGitVersion');
};

export const API_BUILD_SERVER = (arrExtraParameters) => {
    return apiRequest('Builder', 'ServerBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_SERVER_HA = (arrExtraParameters) => {
    return apiRequest('Builder', 'ServerHaBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_LINUX_CLIENT = (arrExtraParameters) => {
    if (arrExtraParameters.sType === 'zip'){
        return apiRequest('Builder', 'LinuxClientBuilder', 'build', arrExtraParameters)
    } else {
        return apiRequest('Builder', 'LinuxClientOfRpmOrDebBuilder', 'build', arrExtraParameters)
    }
};


export const API_CHECK_BUILDER_ENV = (arrExtraParameters) => {
    if (arrExtraParameters.sType === 'zip'){
        return apiRequest('Builder', 'LinuxClientBuilder', 'check', arrExtraParameters)
    } else {
        return apiRequest('Builder', 'LinuxClientOfRpmOrDebBuilder', 'check', arrExtraParameters)
    }
};

export const API_BUILD_LINUX_CLIENT_BS = (arrExtraParameters) => {
    return apiRequest('Builder', 'LinuxClientBsBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_LINUX_CLIENT_VOLCDPHA = (arrExtraParameters) => {
    return apiRequest('Builder', 'LinuxClientVolCdpHaBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_WINDOWS_CLIENT = (arrExtraParameters) => {
    return apiRequest('Builder', 'WindowsClientBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_WINDOWS_CLIENT_BS = (arrExtraParameters) => {
    return apiRequest('Builder', 'WindowsClientBsBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_WINDOWS_CLIENT_OS = (arrExtraParameters) => {
    return apiRequest('Builder', 'WindowsClientOsBuilder', 'build', arrExtraParameters)
};

export const API_BUILD_WINDOWS_CLIENT_VOLCDPHA = (arrExtraParameters) => {
    return apiRequest('Builder', 'WindowsClientVolCdpHaBuilder', 'build', arrExtraParameters)
};