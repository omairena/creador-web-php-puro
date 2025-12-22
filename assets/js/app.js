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
  }
});
