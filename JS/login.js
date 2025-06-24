document.addEventListener('DOMContentLoaded', () => {
    const adminTab = document.getElementById('admin-tab');
    const superAdminTab = document.getElementById('superadmin-tab');
    const roleInput = document.getElementById('role');
    const loginForm = document.getElementById('login-form');
    const errorMessageDiv = document.getElementById('error-message');
    const submitButton = loginForm.querySelector('button[type="submit"]');

    function setActiveTab(tab) {
        adminTab.classList.remove('active-tab');
        adminTab.classList.add('inactive-tab');
        superAdminTab.classList.remove('active-tab');
        superAdminTab.classList.add('inactive-tab');

        tab.classList.remove('inactive-tab');
        tab.classList.add('active-tab');
        
        roleInput.value = (tab === adminTab) ? 'admin' : 'superadmin';
    }

    if (adminTab) {
        adminTab.addEventListener('click', () => setActiveTab(adminTab));
    }
    if(superAdminTab) {
        superAdminTab.addEventListener('click', () => setActiveTab(superAdminTab));
    }

    if (loginForm) {
        loginForm.addEventListener('submit', function(event) {
            event.preventDefault();
            console.log("Form submission intercepted by JavaScript.");

            submitButton.disabled = true;
            submitButton.textContent = 'Logging In...';
            errorMessageDiv.textContent = '';

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            fetch('PHP/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Network response was not ok, status: ${response.status}`);
                }
                return response.json();
            })
            .then(result => {
                if (result.status === 'success') {
                    window.location.href = result.redirect;
                } else {
                    errorMessageDiv.textContent = result.message || 'An unknown error occurred.';
                }
            })
            .catch(error => {
                console.error('Login Fetch Error:', error);
                errorMessageDiv.textContent = 'A client-side error occurred. Check the console.';
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.textContent = 'Log In';
            });
        });
    }
});
