(function () {
  var header = document.querySelector(".site-header");
  if (!header) return;

  var threshold = 16;
  var compact = false;

  function onScroll() {
    var y = window.scrollY || document.documentElement.scrollTop;
    var next = y > threshold;
    if (next === compact) return;
    compact = next;
    header.classList.toggle("is-scrolled", compact);
  }

  onScroll();
  window.addEventListener("scroll", onScroll, { passive: true });
})();
