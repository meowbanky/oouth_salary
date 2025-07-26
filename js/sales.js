// js/sales.js
import { showLoader, hideLoader, gritter } from './utils.js';

document.addEventListener('DOMContentLoaded', () => {
    const menuTrigger = document.getElementById('menu-trigger');
    menuTrigger?.addEventListener('click', () => {
        document.querySelector('sidebar')?.classList.toggle('open');
    });

    // Optional: Handle modal
    document.getElementById('openModal')?.addEventListener('click', () => {
        const modal = document.getElementById('myModal');
        modal.style.display = 'block';
        modal.innerHTML = '<div class="modal-content"><span class="close">&times;</span><p>Modal content here</p></div>';
        document.querySelector('.close').addEventListener('click', () => modal.style.display = 'none');
    });
});