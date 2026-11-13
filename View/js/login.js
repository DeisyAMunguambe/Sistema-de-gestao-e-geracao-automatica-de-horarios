var form = document.getElementById("loginForm");
var emailInput = document.getElementById("email");
var passwordInput = document.getElementById("password");
var emailField = document.getElementById("fieldEmail");
var passwordField = document.getElementById("fieldPassword");
var emailError = document.getElementById("emailError");
var passwordError = document.getElementById("passwordError");
var formMessage = document.getElementById("formMessage");
var submitButton = document.getElementById("submitButton");
var toggleButton = document.getElementById("togglePassword");

toggleButton.addEventListener("click", function () {
  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    toggleButton.setAttribute("aria-label", "Esconder palavra-passe");
  } else {
    passwordInput.type = "password";
    toggleButton.setAttribute("aria-label", "Mostrar palavra-passe");
  }
});

function showError(field, errorElement, message) {
  field.classList.add("field--invalid");
  errorElement.textContent = message;
}

function clearError(field, errorElement) {
  field.classList.remove("field--invalid");
  errorElement.textContent = "";
}

function emailIsValid(email) {
  var atPosition = email.indexOf("@");
  var dotPosition = email.lastIndexOf(".");

  if (atPosition < 1) {
    return false;
  }
  if (dotPosition < atPosition + 2) {
    return false;
  }
  if (dotPosition === email.length - 1) {
    return false;
  }
  return true;
}

function showMessage(text, type) {
  formMessage.textContent = text;
  formMessage.className = "login-form__message login-form__message--" + type;
}

emailInput.addEventListener("input", function () {
  clearError(emailField, emailError);
});
passwordInput.addEventListener("input", function () {
  clearError(passwordField, passwordError);
});

form.addEventListener("submit", function (event) {
  event.preventDefault();

  var email = emailInput.value.trim();
  var password = passwordInput.value;
  var isValid = true;

  formMessage.textContent = "";
  formMessage.className = "login-form__message";

  if (email === "") {
    showError(emailField, emailError, "Escreva o seu email.");
    isValid = false;
  } else if (!emailIsValid(email)) {
    showError(emailField, emailError, "Escreva um email válido, por exemplo nome@email.com.");
    isValid = false;
  }

  if (password === "") {
    showError(passwordField, passwordError, "Escreva a sua palavra-passe.");
    isValid = false;
  }

  if (!isValid) {
    if (emailField.classList.contains("field--invalid")) {
      emailInput.focus();
    } else {
      passwordInput.focus();
    }
    return;
  }

  submitButton.disabled = true;
  submitButton.classList.add("is-loading");
  submitButton.querySelector(".login-form__submit-text").textContent = "A entrar...";

  form.submit();
});

window.addEventListener("pageshow", function () {
  submitButton.disabled = false;
  submitButton.classList.remove("is-loading");
  submitButton.querySelector(".login-form__submit-text").textContent = "Entrar";
});
