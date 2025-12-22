// app.js - comportamiento sencillo: alternar sidebar
document.addEventListener('DOMContentLoaded', function(){
  var toggle = document.getElementById('toggleBtn');
  var sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) {
    toggle.addEventListener('click', function(){
      sidebar.classList.toggle('open');
    });
    // Cerrar sidebar al tocar fuera (en pantallas pequeñas)
    document.addEventListener('click', function(e){
      if (!sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('open')) {
        sidebar.classList.remove('open');
      }
    });

    // Submenu behavior for Producto
    document.querySelectorAll('.menu .menu-parent').forEach(function(el){
      el.addEventListener('click', function(ev){
        ev.preventDefault();
        var item = el.parentElement;
        item.classList.toggle('open');
      });
    });

    // Collapse sidebar (desktop) behavior with persistence
    var collapseBtn = document.getElementById('collapseBtn');
    if (collapseBtn) {
      function setCollapseState(collapsed){
        if (collapsed) {
          document.body.classList.add('sidebar-collapsed');
          collapseBtn.textContent = '▶';
          collapseBtn.setAttribute('aria-expanded', 'true');
        } else {
          document.body.classList.remove('sidebar-collapsed');
          collapseBtn.textContent = '◀';
          collapseBtn.setAttribute('aria-expanded', 'false');
        }
      }

      collapseBtn.addEventListener('click', function(ev){
        ev.preventDefault();
        var collapsed = !document.body.classList.contains('sidebar-collapsed');
        setCollapseState(collapsed);
        // persist
        try { localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0'); } catch(e){}
        console.log('sidebar collapse toggled:', collapsed);
      });
      // restore state
      try {
        var saved = localStorage.getItem('sidebar-collapsed');
        if (saved === '1') setCollapseState(true); else setCollapseState(false);
      } catch(e) {}
    }
  }
});
