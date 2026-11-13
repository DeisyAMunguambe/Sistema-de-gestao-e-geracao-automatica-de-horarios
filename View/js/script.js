// Menu responsivo
const toggle = document.querySelector(".header__toggle");
const menu = document.getElementById("menu");

toggle.addEventListener("click", () => {
  const isOpen = menu.classList.toggle("is-open");
  toggle.setAttribute("aria-expanded", isOpen);
});

// Fecha o menu ao clicar numa ligação
menu.querySelectorAll(".header__link").forEach((link) => {
  link.addEventListener("click", () => {
    menu.classList.remove("is-open");
    toggle.setAttribute("aria-expanded", "false");
  });
});
