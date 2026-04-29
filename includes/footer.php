    </div>
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <small>&copy; <?php echo date('Y'); ?> V.O.N RUBICUTS HOME SERVICE. All rights reserved.</small><br>
            <small>Developed by Dhon Marck V. Hermosura, IT Specialist</small>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Show/Hide password toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Single password field (login page)
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        }
        
        // Registration page - confirm password
        const toggleConfirmBtn = document.getElementById('toggleConfirmPassword');
        const confirmInput = document.getElementById('confirm_password');
        
        if (toggleConfirmBtn && confirmInput) {
            toggleConfirmBtn.addEventListener('click', function() {
                const type = confirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                confirmInput.setAttribute('type', type);
                
                const icon = this.querySelector('i');
                icon.classList.toggle('bi-eye');
                icon.classList.toggle('bi-eye-slash');
            });
        }
    });
    </script>
</body>
</html>
