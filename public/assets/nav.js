(function(){
  console.log('[SellExa] nav.js loaded'); // cek di DevTools

  const header = document.querySelector('.main-header');
  const btn = document.querySelector('.nav-toggle');
  const nav = document.getElementById('primary-nav');
  if(!header || !btn || !nav) return;

  btn.addEventListener('click', function(){
    const open = header.classList.toggle('is-open');
    document.body.classList.toggle('nav-open', open);
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
  });

  // Tutup saat klik di luar
  document.addEventListener('click', function(e){
    if(!header.classList.contains('is-open')) return;
    if(!header.contains(e.target)){
      header.classList.remove('is-open');
      document.body.classList.remove('nav-open');
      btn.setAttribute('aria-expanded','false');
    }
  });

  // Tutup saat resize ke desktop
  window.addEventListener('resize', function(){
    if(window.innerWidth > 860 && header.classList.contains('is-open')){
      header.classList.remove('is-open');
      document.body.classList.remove('nav-open');
      btn.setAttribute('aria-expanded','false');
    }
  });

  // Tutup pakai Escape
  document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && header.classList.contains('is-open')){
      header.classList.remove('is-open');
      document.body.classList.remove('nav-open');
      btn.setAttribute('aria-expanded','false');
    }
  });
})();
