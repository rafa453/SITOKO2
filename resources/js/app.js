import Alpine from 'alpinejs';

window.Alpine = Alpine;
window.loadChart = () => import('chart.js/auto').then(({ default: Chart }) => Chart);

Alpine.start();
