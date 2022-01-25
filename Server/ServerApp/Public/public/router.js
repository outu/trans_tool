import Overview from '../components/Overview.js'
import BuildServer from '../components/Build/Server.js'
import BuildServerHa from '../components/Build/ServerHa.js'
import BuildWindowsClient from '../components/Build/WindowsClient.js'
import BuildWindowsClientBs from '../components/Build/WindowsClientBs.js'
import BuildWindowsClientOs from '../components/Build/WindowsClientOs.js'
import BuildWindowsClientVolCdpHa from '../components/Build/WindowsClientVolCdpHa.js'
import BuildLinuxClient from '../components/Build/LinuxClient.js'
import BuildLinuxClientBs from '../components/Build/LinuxClientBs.js'
import BuildLinuxClientVolCdpHa from '../components/Build/LinuxClientVolCdpHa.js'

const routes = [
    { path: '/', component: Overview},
    { path: '/BuildServer', component: BuildServer },
    { path: '/BuildServerHA', component: BuildServerHa },
    { path: '/BuildWindowsClient', component: BuildWindowsClient },
    { path: '/BuildWindowsClientBs', component: BuildWindowsClientBs },
    { path: '/BuildWindowsClientOs', component: BuildWindowsClientOs },
    { path: '/BuildWindowsClientVolCdpHa', component: BuildWindowsClientVolCdpHa },
    { path: '/BuildLinuxClient', component: BuildLinuxClient },
    { path: '/BuildLinuxClientBs', component: BuildLinuxClientBs },
    { path: '/BuildLinuxClientVolCdpHa', component: BuildLinuxClientVolCdpHa },
];

const router = new VueRouter({
    routes // short for `routes: routes`
});

export default router;