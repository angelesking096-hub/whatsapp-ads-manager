import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();
import './bootstrap';
document.addEventListener('click', (event) => {
  const toggle = event.target.closest('[data-mobile-menu]');
  if (toggle) document.querySelector('[data-sidebar]')?.classList.toggle('sidebar-open');
  const dismiss = event.target.closest('[data-dismiss]');
  if (dismiss) dismiss.closest('[data-flash]')?.remove();
});
