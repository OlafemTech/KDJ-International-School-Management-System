document.addEventListener('DOMContentLoaded', function() {
    // Get the refresh button
    const refreshBtn = document.querySelector('.report-summary-header .btn-icons');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', updateDashboardStats);
    }

    function updateDashboardStats() {
        // Show loading state
        const refreshIcon = refreshBtn.querySelector('i');
        refreshIcon.className = 'icon-refresh rotating';
        
        fetch('ajax/get_dashboard_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateUI(data.data);
                    showToast('Dashboard updated successfully!', 'success');
                } else {
                    throw new Error(data.error || 'Failed to update dashboard');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to update dashboard', 'error');
            })
            .finally(() => {
                // Reset refresh button
                refreshIcon.className = 'icon-refresh';
            });
    }

    function updateUI(stats) {
        // Update summary cards
        updateElement('.total-classes', stats.totalclass);
        updateElement('.total-students', stats.totalstudents);
        updateElement('.total-teachers', stats.totalteachers);
        updateElement('.total-notices', stats.totalnotice);

        // Update student gender stats
        updateElement('.male-students', stats.malestudents);
        updateElement('.female-students', stats.femalestudents);

        // Update teacher gender stats
        updateElement('.male-teachers', stats.maleteachers);
        updateElement('.female-teachers', stats.femaleteachers);

        // Update qualifications table
        const tbody = document.querySelector('.qualifications-table tbody');
        if (tbody && stats.qualifications) {
            tbody.innerHTML = stats.qualifications.map(qual => `
                <tr>
                    <td>${escapeHtml(qual.qualification)}</td>
                    <td>${qual.count}</td>
                    <td>${qual.percentage}%</td>
                </tr>
            `).join('');
        }

        // Update last updated time
        const timestamp = document.querySelector('.last-updated');
        if (timestamp && stats.lastUpdated) {
            timestamp.textContent = stats.lastUpdated;
        }
    }

    function updateElement(selector, value) {
        const element = document.querySelector(selector);
        if (element) {
            element.textContent = value;
        }
    }

    function escapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Trigger reflow
        toast.offsetHeight;
        
        // Add visible class
        toast.classList.add('visible');
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.classList.remove('visible');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
});
