document.addEventListener("DOMContentLoaded", function () {
    // 1. Password Visibility Toggle
    const togglePasswordBtn = document.getElementById("togglePassword");
    const passwordInput = document.getElementById("password");
    const togglePasswordIcon = document.getElementById("togglePasswordIcon");

    if (togglePasswordBtn && passwordInput && togglePasswordIcon) {
        togglePasswordBtn.addEventListener("click", function () {
            const isPassword =
                passwordInput.getAttribute("type") === "password";
            passwordInput.setAttribute(
                "type",
                isPassword ? "text" : "password",
            );

            togglePasswordIcon.classList.toggle("bi-eye", !isPassword);
            togglePasswordIcon.classList.toggle("bi-eye-slash", isPassword);
        });
    }

    // 2. Form Submit Loading State
    const formLogin = document.getElementById("formLogin");
    const btnSubmit = document.getElementById("btnSubmit");
    const btnSubmitText = document.getElementById("btnSubmitText");
    const btnSubmitIcon = document.getElementById("btnSubmitIcon");

    if (formLogin && btnSubmit) {
        formLogin.addEventListener("submit", function () {
            btnSubmit.disabled = true;
            btnSubmit.classList.add("opacity-75");
            if (btnSubmitIcon) {
                btnSubmitIcon.className =
                    "spinner-border spinner-border-sm me-1";
            }
            if (btnSubmitText) {
                btnSubmitText.textContent = "Memverifikasi...";
            }
        });
    }
});
