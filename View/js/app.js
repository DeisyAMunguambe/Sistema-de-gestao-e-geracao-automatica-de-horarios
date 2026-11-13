(function () {
    const root = document.documentElement;
    if (localStorage.getItem("sgh-theme") === "dark") root.dataset.bsTheme = "dark";

    window.toggleTheme = function () {
        const dark = root.dataset.bsTheme === "dark";
        root.dataset.bsTheme = dark ? "light" : "dark";
        localStorage.setItem("sgh-theme", dark ? "light" : "dark");
    };

    document.querySelectorAll("[data-confirm]").forEach((b) =>
        b.addEventListener("click", (e) => {
            if (!confirm(b.dataset.confirm)) e.preventDefault();
        }),
    );

    setTimeout(
        () => document.querySelectorAll(".main > .alert").forEach((a) => a.classList.add("hide")),
        5000,
    );

    /* Modal único de cadastro: novo, editar e remover (data-modal). */
    document.addEventListener("click", (ev) => {
        const btn = ev.target.closest("[data-modal]");
        const modalEl = document.getElementById("modalForm");
        if (!btn || !modalEl) return;

        const mode = btn.dataset.modal;
        const row = btn.dataset.row ? JSON.parse(btn.dataset.row) : null;
        const form = modalEl.querySelector("form");
        const remover = mode === "remover";

        form.reset();
        form.elements["acao"].value = remover ? "remover" : "guardar";
        form.elements["id"].value = row ? Object.values(row)[0] : 0;

        if (row) {
            Object.entries(row).forEach(([k, v]) => {
                const f = form.elements[k];
                if (f && k !== "id" && k !== "acao") f.value = v ?? "";
            });
        }

        form.querySelectorAll(".modal-body input:not([type=hidden]), .modal-body select").forEach(
            (f) => (f.disabled = remover),
        );

        const senha = form.elements["senha"];
        if (senha) senha.required = mode === "novo";

        modalEl.querySelector(".modal-title").textContent = btn.dataset.title;
        modalEl.querySelector(".modal-aviso").classList.toggle("d-none", !remover);

        const ok = modalEl.querySelector(".modal-submit");
        ok.textContent = remover ? "Remover" : "Guardar";
        ok.className = "btn modal-submit " + (remover ? "btn-danger" : "btn-primary");

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
})();
