document.querySelectorAll('[data-confirm]').forEach(x=>x.addEventListener('click',e=>{if(!confirm(x.dataset.confirm||'¿Confirmas?'))e.preventDefault()}));
