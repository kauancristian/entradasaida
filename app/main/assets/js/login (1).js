function showAlert(title, message, isError = false) {
  const modal = document.getElementById('alertModal');
  const overlay = document.getElementById('modalOverlay');
  const alertTitle = document.getElementById('alertTitle');
  const alertMessage = document.getElementById('alertMessage');
  const alertIcon = modal.querySelector('.alert-icon');
  
  alertTitle.textContent = title;
  alertMessage.textContent = message;
  
  if (isError) {
    modal.classList.add('error');
    alertIcon.innerHTML = `
      <path d="M12 8v4M12 16h.01M22 12c0 5.523-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2s10 4.477 10 10z"/>
    `;
  } else {
    modal.classList.remove('error');
    alertIcon.innerHTML = `
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
      <polyline points="22 4 12 14.01 9 11.01"></polyline>
    `;
  }
  
  modal.classList.add('active');
  overlay.classList.add('active');
}

function closeAlert() {
  const modal = document.getElementById('alertModal');
  const overlay = document.getElementById('modalOverlay');
  
  modal.classList.remove('active');
  overlay.classList.remove('active');
}

document.getElementById("loginForm").addEventListener("submit", function(event) {
  event.preventDefault();
  
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  
  if (email === "teste@gmail.com" && password === "teste") {
    showAlert(
      "Login Bem Sucedido!",
      "Você será redirecionado para a página principal.",
      false
    );
    setTimeout(() => {
      window.location.href = "index.php";
    }, 1500);
  } else {
    showAlert(
      "Erro no Login",
      "Usuário ou senha incorretos! Por favor, tente novamente.",
      true
    );
  }
});

const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", function() {
  const type = password.getAttribute("type") === "password" ? "text" : "password";
  password.setAttribute("type", type);
  
  this.querySelector("i").classList.toggle("fa-eye");
  this.querySelector("i").classList.toggle("fa-eye-slash");
}); 