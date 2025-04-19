<!-- plugins:css -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.2.96/css/materialdesignicons.min.css" rel="stylesheet">
<style>
:root {
    --sidebar-width: 250px;
    --sidebar-bg: #1e1e2d;
    --header-height: 70px;
    --primary-color: #0d6efd;
    --sidebar-text: rgba(255,255,255,0.7);
    --sidebar-hover: rgba(255,255,255,0.1);
    --accent-color: #00c689;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f8f9fa;
    margin: 0;
    padding: 0;
    color: #212529;
}

/* Sidebar Styles */
.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    position: fixed;
    height: 100vh;
    left: 0;
    top: 0;
    z-index: 100;
    padding-top: 1rem;
}

.sidebar .brand {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 600;
    padding: 1rem 1.5rem;
    margin-bottom: 1rem;
    letter-spacing: 0.5px;
}

.nav-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.nav-item {
    display: flex;
    align-items: center;
    padding: 0.875rem 1.5rem;
    color: var(--sidebar-text);
    text-decoration: none;
    transition: all 0.3s;
    cursor: pointer;
    font-size: 0.9375rem;
    border-left: 3px solid transparent;
    line-height: 1.4;
    letter-spacing: 0.2px;
    position: relative;
}

.nav-item:hover, .nav-item.active {
    color: #fff;
    background: var(--sidebar-hover);
    border-left-color: var(--accent-color);
}

.nav-item i {
    margin-right: 1rem;
    width: 24px;
    text-align: center;
    font-size: 1.375rem;
    opacity: 0.85;
    line-height: 1;
}

.nav-item:hover i, .nav-item.active i {
    opacity: 1;
}

/* Main Content */
.main-panel {
    margin-left: var(--sidebar-width);
    min-height: 100vh;
    background: #f8f9fa;
}

.header {
    background: #fff;
    padding: 1rem 2rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: var(--header-height);
}

.header h4 {
    margin: 0;
    font-weight: 500;
    color: #6c757d;
    font-size: 1rem;
}

.profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #6c757d;
    font-size: 0.875rem;
    cursor: pointer;
}

.profile img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

.profile i {
    font-size: 0.75rem;
    margin-left: 0.25rem;
}

.content-wrapper {
    padding: 2rem;
}

/* Card Styles */
.card {
    background: #fff;
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
}

.card-body {
    padding: 2rem;
}

.card-title {
    margin-bottom: 1.5rem;
    font-size: 1.125rem;
    font-weight: 500;
    color: #212529;
}

/* Form Styles */
.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: normal;
    color: #212529;
    font-size: 0.875rem;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    background-clip: padding-box;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
    border-color: var(--accent-color);
    box-shadow: 0 0 0 0.25rem rgba(0,198,137,0.1);
    outline: none;
}

select.form-control {
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.btn-primary {
    color: #fff;
    background-color: var(--accent-color);
    border-color: var(--accent-color);
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    border-radius: 0.375rem;
    font-weight: 500;
    min-width: 120px;
    letter-spacing: 0.3px;
    transition: all 0.2s ease-in-out;
}

.btn-primary:hover {
    background-color: #00b37a;
    border-color: #00b37a;
    transform: translateY(-1px);
}

.btn-primary:active {
    transform: translateY(0);
}

/* Form Validation Styles */
.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

.form-control.is-invalid ~ .invalid-feedback {
    display: block;
}

/* Placeholder Styles */
.form-control::placeholder {
    color: #6c757d;
    opacity: 0.65;
}
</style>
<link rel="shortcut icon" href="../assets/images/favicon.png" />
