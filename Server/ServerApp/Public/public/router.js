import Overview from '../components/Overview.js'
import NewTask from '../components/Task/NewTask.js'
import MonitorTask from '../components/Task/MonitorTask.js'
import CompletedTask from '../components/Task/CompletedTask.js'

const routes = [
    { path: '/', component: Overview},
    { path: '/NewTask', component: NewTask },
    { path: '/MonitorTask', component: MonitorTask },
    { path: '/CompletedTask', component: CompletedTask },
];

const router = new VueRouter({
    routes // short for `routes: routes`
});

export default router;