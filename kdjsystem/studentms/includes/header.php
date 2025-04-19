<!--header-->
<header class="header" id="home">
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-primary">KDJ</span>SMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link px-3" href="index.php">
                            <i class="bi bi-house-door"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="user/login.php">
                            <i class="bi bi-person"></i> Student Portal
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="admin/login.php">
                            <i class="bi bi-shield-lock"></i> Admin Portal
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>

<style>
:root {
    --primary-color: #2196F3;
    --secondary-color: #1976D2;
    --accent-color: #FFC107;
    --text-color: #333;
    --light-bg: #f8f9fa;
    --dark-bg: #343a40;
}

.header {
    margin-bottom: 70px;
}

.navbar {
    padding: 15px 0;
    transition: all 0.3s ease;
}

.navbar-brand {
    font-size: 1.5rem;
    color: var(--dark-bg);
}

.nav-link {
    font-weight: 500;
    color: var(--text-color) !important;
    transition: all 0.3s ease;
    position: relative;
}

.nav-link:hover {
    color: var(--primary-color) !important;
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: all 0.3s ease;
}

.nav-link:hover::after {
    width: 50%;
}

@media (max-width: 991px) {
    .navbar-collapse {
        background: white;
        padding: 1rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-top: 1rem;
    }
    
    .nav-link::after {
        display: none;
    }
}
</style>