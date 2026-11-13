var header = document.querySelector(".header");
var toggle = document.querySelector(".header__toggle");
var menu = document.getElementById("menu");
var links = document.querySelectorAll(".header__link");

function openMenu() {
  menu.classList.add("is-open");
  toggle.setAttribute("aria-expanded", "true");
  toggle.setAttribute("aria-label", "Fechar menu");
}

function closeMenu() {
  menu.classList.remove("is-open");
  toggle.setAttribute("aria-expanded", "false");
  toggle.setAttribute("aria-label", "Abrir menu");
}

toggle.addEventListener("click", function () {
  if (menu.classList.contains("is-open")) {
    closeMenu();
  } else {
    openMenu();
  }
});

for (var i = 0; i < links.length; i++) {
  links[i].addEventListener("click", closeMenu);
}

document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeMenu();
  }
});

function updateHeader() {
  if (window.scrollY > 20) {
    header.classList.add("is-scrolled");
  } else {
    header.classList.remove("is-scrolled");
  }
}

window.addEventListener("scroll", updateHeader);
updateHeader();

var sections = [];
for (var j = 0; j < links.length; j++) {
  var id = links[j].getAttribute("href").replace("#", "");
  sections.push(document.getElementById(id));
}

function updateActiveLink() {
  var position = window.scrollY + window.innerHeight / 3;
  var current = -1;

  for (var k = 0; k < sections.length; k++) {
    if (sections[k] && sections[k].offsetTop <= position) {
      current = k;
    }
  }

  for (var m = 0; m < links.length; m++) {
    if (m === current) {
      links[m].classList.add("is-active");
    } else {
      links[m].classList.remove("is-active");
    }
  }
}

window.addEventListener("scroll", updateActiveLink);
updateActiveLink();

var revealItems = document.querySelectorAll(".reveal");

if ("IntersectionObserver" in window) {
  var observer = new IntersectionObserver(function (entries) {
    for (var n = 0; n < entries.length; n++) {
      if (entries[n].isIntersecting) {
        entries[n].target.classList.add("is-visible");
      } else {
        entries[n].target.classList.remove("is-visible");
      }
    }
  }, { threshold: 0.12 });

  for (var p = 0; p < revealItems.length; p++) {
    observer.observe(revealItems[p]);
  }
} else {
  for (var q = 0; q < revealItems.length; q++) {
    revealItems[q].classList.add("is-visible");
  }
}
