<!--footer-->
<style>
    :root {
        --primary-color: #E1009F;
        --secondary-color: #333;
        --accent-color: #FFD700;
        --footer-bg: #1a1e25;
        --footer-secondary: #151a21;
        --footer-text: #ffffff;
        --footer-muted: rgba(255,255,255,0.7);
    }

    .footer {
        background: var(--footer-bg);
        position: relative;
        overflow: hidden;
    }

    .footer::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
    }

    .footer-brand {
        font-size: 1.8rem;
        font-weight: 700;
        background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 1.5rem;
        display: inline-block;
    }

    .footer-description {
        color: var(--footer-muted);
        line-height: 1.8;
        font-size: 0.95rem;
    }

    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .footer-links li {
        margin-bottom: 1rem;
    }

    .footer-links a {
        color: var(--footer-muted);
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }

    .footer-links a i {
        color: var(--primary-color);
        margin-right: 10px;
        font-size: 0.8rem;
        transition: transform 0.3s ease;
    }

    .footer-links a:hover {
        color: var(--footer-text);
    }

    .footer-links a:hover i {
        transform: translateX(5px);
    }

    .social-links {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .social-links a {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        color: var(--footer-text);
        transition: all 0.3s ease;
    }

    .social-links a:hover {
        background: var(--primary-color);
        transform: translateY(-5px);
    }

    .contact-info p {
        color: var(--footer-muted);
        margin-bottom: 1.2rem;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
    }

    .contact-info p i {
        color: var(--primary-color);
        margin-right: 12px;
        font-size: 1.1rem;
    }

    .contact-info p:hover {
        color: var(--footer-text);
    }

    .copyright {
        background: var(--footer-secondary);
        padding: 1.5rem 0;
        color: var(--footer-muted);
    }

    .copyright a {
        color: var(--primary-color);
        text-decoration: none;
        transition: color 0.3s ease;
    }

    .copyright a:hover {
        color: var(--accent-color);
    }

    .section-heading {
        color: var(--footer-text);
        font-size: 1.3rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        position: relative;
        padding-bottom: 1rem;
    }

    .section-heading::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 2px;
        background: var(--primary-color);
    }

    @media (max-width: 768px) {
        .footer {
            text-align: center;
        }

        .section-heading::after {
            left: 50%;
            transform: translateX(-50%);
        }

        .social-links {
            justify-content: center;
        }

        .footer-links a {
            justify-content: center;
        }

        .contact-info p {
            justify-content: center;
        }

        .copyright .text-md-end {
            text-align: center !important;
            margin-top: 1rem;
        }
    }
</style>

<footer class="footer py-5">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <h3 class="footer-brand">KDJ International School</h3>
                <p class="footer-description">Empowering students with quality education and shaping future leaders through innovative learning approaches.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            <div class="col-lg-4">
                <h4 class="section-heading">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.php"><i class="fas fa-chevron-right"></i>Home</a></li>
                    <!-- <li><a href="about.php"><i class="fas fa-chevron-right"></i>About</a></li>
                    <li><a href="contact.php"><i class="fas fa-chevron-right"></i>Contact</a></li> -->
                    <li><a href="admin/login.php"><i class="fas fa-chevron-right"></i>Admin Portal</a></li>
                    <li><a href="user/login.php"><i class="fas fa-chevron-right"></i>Student Portal</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h4 class="section-heading">Contact Information</h4>
                <?php
                $sql="SELECT * from tblpage where PageType='contactus'";
                $query = $dbh -> prepare($sql);
                $query->execute();
                $results=$query->fetchAll(PDO::FETCH_OBJ);

                if($query->rowCount() > 0) {
                    foreach($results as $row) { ?>
                        <div class="contact-info">
                            <p><i class="fas fa-map-marker-alt"></i><?php echo htmlentities($row->PageDescription);?></p>
                            <p><i class="fas fa-phone"></i><?php echo htmlentities($row->MobileNumber);?></p>
                            <p><i class="fas fa-envelope"></i>info@kdjschool.com</p>
                        </div>
                <?php }} ?>
            </div>
        </div>
    </div>
</footer>

<div class="copyright">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> KDJ International School. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0">Powered by <a href="https://www.linkedin.com/in/habeeb-oluwafemi-0b1999bb/" target="_blank">Digi-Tech Solutions | +234(0)8131017099</a></p>
            </div>
        </div>
    </div>
</div>
