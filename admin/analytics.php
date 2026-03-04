<?php
include 'functions.php';
validatecookie();
include("header.php");
?>

<div id="main">
    <div class="full">
        <h2>Tournament Analytics 📊</h2>
        <div style="text-align:right; margin-bottom:20px;">
            <a href="index.php" style="color:var(--text-muted); text-decoration:none;">&larr; Back to Dashboard</a>
        </div>
        
        <style>
            .analytics-grid {
                display: grid;
                grid-template-columns: 1fr;
                gap: 20px;
            }
            @media (min-width: 768px) {
                .analytics-grid {
                    grid-template-columns: 1fr 1fr;
                }
            }
        </style>
        <div class="analytics-grid">
            <div class="dashboard-card" style="padding:20px; align-items:flex-start;">
                <h3 style="margin-top:0; font-size:1.1rem; color:var(--text-muted); margin-bottom:15px; border-bottom:1px solid var(--border-color); width:100%; padding-bottom:10px;">Signups (Last 7 Days)</h3>
                <canvas id="signupChart" style="width:100%;"></canvas>
            </div>
            <div class="dashboard-card" style="padding:20px; align-items:flex-start;">
                <h3 style="margin-top:0; font-size:1.1rem; color:var(--text-muted); margin-bottom:15px; border-bottom:1px solid var(--border-color); width:100%; padding-bottom:10px;">Payment Status</h3>
                <canvas id="paymentChart" style="width:100%;"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="../js/lib/chart.js"></script>
<script>
fetch('get_stats.php')
    .then(response => response.json())
    .then(data => {
        // Signups Chart
        const ctx1 = document.getElementById('signupChart');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: data.signups.map(item => item.date),
                datasets: [{
                    label: 'New Brackets',
                    data: data.signups.map(item => item.count),
                    borderColor: '#fbbf24', // Accent Orange/Yellow
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true, grid: { color: '#334155' } },
                    x: { grid: { color: '#334155' } }
                }
            }
        });

        // Payment Chart
        const ctx2 = document.getElementById('paymentChart');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Unpaid', 'Paid', 'Exempt'],
                datasets: [{
                    data: [
                        data.payment.find(p => p.paid == "0")?.count || 0,
                        data.payment.find(p => p.paid == "1")?.count || 0,
                        data.payment.find(p => p.paid == "2")?.count || 0
                    ],
                    backgroundColor: ['#ef4444', '#22c55e', '#3b82f6'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#fff' } }
                }
            }
        });
    });
</script>

<?php include("../footer.php"); // close body/html if footer assumes it, otherwise close manually ?>
<?php include('footer.php'); ?>
</body>
</html>


