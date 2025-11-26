</div>
        </main>
    </div>
    
    <script src="<?php echo $baseUrl; ?>/assets/js/main.js"></script>
    <script>
        // Actualizar fecha y hora
        function updateDateTime() {
            const now = new Date();
            const options = { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            document.getElementById('currentDateTime').textContent = 
                now.toLocaleDateString('es-ES', options);
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000);
    </script>
</body>
</html>
