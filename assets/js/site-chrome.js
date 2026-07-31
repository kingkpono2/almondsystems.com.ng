/* Almond Systems - shared site chrome behaviour.
   Drives the mobile navigation toggle on every page, plus the
   transparent-to-solid header transition used on the home page only. */
(function () {
  var header = document.querySelector('.sc-header');
  var toggle = document.querySelector('#scNavToggle');
  var links = document.querySelector('#scNavLinks');

  var BURGER = '<i class="ti ti-menu-2" aria-hidden="true"></i>';
  var CLOSE = '<i class="ti ti-x" aria-hidden="true"></i>';

  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var isOpen = links.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', String(isOpen));
      toggle.innerHTML = isOpen ? CLOSE : BURGER;
    });

    links.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        links.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.innerHTML = BURGER;
      });
    });
  }

  // Home page only: the header floats over the hero, then solidifies on scroll.
  if (header && header.classList.contains('is-transparent')) {
    var ticking = false;
    var syncHeader = function () {
      header.classList.toggle('is-scrolled', window.scrollY > 48);
      ticking = false;
    };
    syncHeader();
    window.addEventListener('scroll', function () {
      if (!ticking) {
        ticking = true;
        window.requestAnimationFrame(syncHeader);
      }
    }, { passive: true });
  }
})();
