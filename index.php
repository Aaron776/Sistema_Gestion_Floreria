<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pétalos · Landing Page</title>
  <!-- Google Fonts & Font Awesome 6 -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html {
      scroll-behavior: smooth;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: #faf8f5;
      color: #2d2a24;
      line-height: 1.6;
      overflow-x: hidden;
    }

    /* ===== NAVBAR ===== */
    .landing-nav {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 1000;
      padding: 0.8rem 2.5rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(200, 122, 90, 0.08);
      transition: all 0.3s;
    }

    .landing-nav.scrolled {
      background: rgba(255, 255, 255, 0.95);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.5rem;
      font-weight: 700;
      color: #2d2a24;
      text-decoration: none;
    }

    .nav-brand i {
      color: #c87a5a;
      font-size: 1.8rem;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 2rem;
      list-style: none;
    }

    .nav-links a {
      text-decoration: none;
      color: #5a4a3a;
      font-weight: 500;
      font-size: 0.9rem;
      transition: color 0.2s;
    }

    .nav-links a:hover {
      color: #c87a5a;
    }

    .nav-actions {
      display: flex;
      gap: 0.8rem;
    }

    .btn-outline-nav {
      padding: 0.5rem 1.4rem;
      border-radius: 40px;
      border: 2px solid #dccfc2;
      background: transparent;
      font-weight: 600;
      font-size: 0.85rem;
      color: #2d2a24;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      font-family: 'Inter', sans-serif;
    }

    .btn-outline-nav:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
    }

    .btn-primary-nav {
      padding: 0.5rem 1.6rem;
      border-radius: 40px;
      border: none;
      background: #2d2a24;
      color: #f4f1eb;
      font-weight: 600;
      font-size: 0.85rem;
      cursor: pointer;
      transition: all 0.2s;
      text-decoration: none;
      font-family: 'Inter', sans-serif;
    }

    .btn-primary-nav:hover {
      background: #1e1a16;
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(45, 42, 36, 0.15);
    }

    .hamburger {
      display: none;
      flex-direction: column;
      gap: 5px;
      background: none;
      border: none;
      cursor: pointer;
      padding: 0.3rem;
    }

    .hamburger span {
      width: 26px;
      height: 2.5px;
      background: #2d2a24;
      border-radius: 4px;
      transition: 0.3s;
    }

    /* ===== HERO ===== */
    .hero {
      min-height: 100vh;
      display: flex;
      align-items: center;
      padding: 6rem 2.5rem 4rem;
      background: linear-gradient(135deg, #faf8f5 0%, #f4f1eb 100%);
      position: relative;
      overflow: hidden;
    }

    .hero::before {
      content: '';
      position: absolute;
      top: -30%;
      right: -10%;
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(200, 122, 90, 0.06) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .hero::after {
      content: '';
      position: absolute;
      bottom: -20%;
      left: -5%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(200, 122, 90, 0.04) 0%, transparent 70%);
      border-radius: 50%;
      pointer-events: none;
    }

    .hero-content {
      max-width: 1200px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 4rem;
      align-items: center;
      position: relative;
      z-index: 1;
      width: 100%;
    }

    .hero-text {
      display: flex;
      flex-direction: column;
      gap: 1.5rem;
    }

    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(200, 122, 90, 0.1);
      color: #c87a5a;
      padding: 0.4rem 1.2rem;
      border-radius: 60px;
      font-size: 0.8rem;
      font-weight: 600;
      width: fit-content;
      border: 1px solid rgba(200, 122, 90, 0.15);
    }

    .hero-badge i {
      font-size: 0.7rem;
    }

    .hero-text h1 {
      font-size: 3.8rem;
      font-weight: 800;
      line-height: 1.1;
      letter-spacing: -1.5px;
    }

    .hero-text h1 .highlight {
      color: #c87a5a;
      position: relative;
    }

    .hero-text h1 .highlight::after {
      content: '';
      position: absolute;
      bottom: 4px;
      left: 0;
      width: 100%;
      height: 8px;
      background: rgba(200, 122, 90, 0.15);
      border-radius: 10px;
    }

    .hero-text p {
      font-size: 1.15rem;
      color: #6b5d4f;
      max-width: 480px;
      line-height: 1.7;
    }

    .hero-buttons {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin-top: 0.5rem;
    }

    .btn-hero-primary {
      padding: 0.9rem 2.4rem;
      border-radius: 60px;
      border: none;
      background: #2d2a24;
      color: #f4f1eb;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.25s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Inter', sans-serif;
    }

    .btn-hero-primary:hover {
      background: #1e1a16;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-hero-secondary {
      padding: 0.9rem 2.4rem;
      border-radius: 60px;
      border: 2px solid #dccfc2;
      background: transparent;
      color: #2d2a24;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.25s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Inter', sans-serif;
    }

    .btn-hero-secondary:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
      transform: translateY(-2px);
    }

    .hero-stats {
      display: flex;
      gap: 2.5rem;
      margin-top: 0.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid #ede8e0;
    }

    .hero-stats .stat-item {
      display: flex;
      flex-direction: column;
    }

    .hero-stats .stat-number {
      font-size: 1.6rem;
      font-weight: 700;
      color: #2d2a24;
    }

    .hero-stats .stat-label {
      font-size: 0.8rem;
      color: #7f6e5d;
    }

    .hero-image {
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }

    .hero-image .image-placeholder {
      width: 100%;
      max-width: 480px;
      aspect-ratio: 1;
      background: linear-gradient(145deg, #f0e8df, #e8dfd4);
      border-radius: 40px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      font-size: 6rem;
      color: #c87a5a;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.04);
      position: relative;
      border: 1px solid rgba(200, 122, 90, 0.08);
    }

    .image-placeholder i {
      opacity: 0.7;
    }

    .image-placeholder .label {
      font-size: 1.2rem;
      font-weight: 600;
      color: #6b5d4f;
      background: rgba(255,255,255,0.7);
      padding: 0.5rem 1.5rem;
      border-radius: 60px;
      backdrop-filter: blur(4px);
    }

    /* ===== SECCIONES ===== */
    .section {
      padding: 5rem 2.5rem;
      max-width: 1200px;
      margin: 0 auto;
    }

    .section-header {
      text-align: center;
      margin-bottom: 3.5rem;
    }

    .section-header h2 {
      font-size: 2.5rem;
      font-weight: 700;
      letter-spacing: -0.5px;
    }

    .section-header h2 span {
      color: #c87a5a;
    }

    .section-header p {
      color: #6b5d4f;
      font-size: 1.05rem;
      max-width: 500px;
      margin: 0.5rem auto 0;
    }

    /* ===== SERVICIOS ===== */
    .services-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 2rem;
    }

    .service-card {
      background: #ffffff;
      padding: 2rem 1.8rem;
      border-radius: 28px;
      border: 1px solid #f1ebe4;
      transition: all 0.25s;
      text-align: center;
    }

    .service-card:hover {
      border-color: #c87a5a;
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.04);
    }

    .service-card .icon {
      font-size: 2.8rem;
      color: #c87a5a;
      margin-bottom: 1rem;
    }

    .service-card h3 {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .service-card p {
      color: #6b5d4f;
      font-size: 0.95rem;
    }

    /* ===== TESTIMONIOS ===== */
    .testimonials-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(min(280px, 100%), 1fr));
      gap: 2rem;
    }

    .testimonial-card {
      background: #ffffff;
      padding: 2rem;
      border-radius: 28px;
      border: 1px solid #f1ebe4;
    }

    .testimonial-card .stars {
      color: #f5b342;
      margin-bottom: 0.8rem;
    }

    .testimonial-card blockquote {
      font-size: 0.95rem;
      color: #4b3d33;
      font-style: italic;
      margin-bottom: 1rem;
    }

    .testimonial-card .author {
      display: flex;
      align-items: center;
      gap: 0.8rem;
    }

    .testimonial-card .author .avatar {
      width: 44px;
      height: 44px;
      border-radius: 44px;
      background: #dccfc2;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      color: #3b2e26;
    }

    .testimonial-card .author .name {
      font-weight: 600;
      font-size: 0.9rem;
    }

    .testimonial-card .author .role {
      font-size: 0.75rem;
      color: #7f6e5d;
    }

    /* ===== CTA FINAL ===== */
    .cta-section {
      background: #2d2a24;
      border-radius: 40px;
      padding: 4rem 3rem;
      margin: 3rem auto 0;
      text-align: center;
      color: #f4f1eb;
    }

    .cta-section h2 {
      font-size: 2.4rem;
      font-weight: 700;
      margin-bottom: 0.8rem;
    }

    .cta-section p {
      color: #b5aca2;
      font-size: 1.05rem;
      max-width: 500px;
      margin: 0 auto 2rem;
    }

    .cta-section .btn-cta {
      padding: 0.9rem 2.8rem;
      border-radius: 60px;
      border: none;
      background: #c87a5a;
      color: #fff;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.25s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Inter', sans-serif;
    }

    .cta-section .btn-cta:hover {
      background: #b86a4a;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(200, 122, 90, 0.3);
    }

    /* ===== FOOTER ===== */
    .footer {
      max-width: 1200px;
      margin: 0 auto;
      padding: 3rem 2.5rem 1.5rem;
      border-top: 1px solid #ede8e0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 1.5rem;
    }

    .footer .copy {
      font-size: 0.8rem;
      color: #a28d7a;
    }

    .footer .social {
      display: flex;
      gap: 1.2rem;
    }

    .footer .social a {
      color: #7f6e5d;
      font-size: 1.1rem;
      transition: color 0.2s;
    }

    .footer .social a:hover {
      color: #c87a5a;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 1024px) {
      .hero-content {
        grid-template-columns: 1fr;
        gap: 2.5rem;
        text-align: center;
      }
      .hero-text p {
        margin: 0 auto;
      }
      .hero-text h1 {
        font-size: 3rem;
      }
      .hero-buttons {
        justify-content: center;
      }
      .hero-stats {
        justify-content: center;
      }
      .hero-image .image-placeholder {
        max-width: 400px;
      }
    }

    @media (max-width: 768px) {
      .landing-nav {
        padding: 0.6rem 1.5rem;
      }
      .nav-links {
        display: none;
        flex-direction: column;
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: rgba(255,255,255,0.98);
        padding: 1.5rem;
        gap: 1.2rem;
        border-bottom: 1px solid #ede8e0;
      }
      .nav-links.open {
        display: flex;
      }
      .hamburger {
        display: flex;
      }
      .nav-actions {
        display: none;
      }
      .hero {
        padding: 5rem 1.5rem 3rem;
      }
      .hero-text h1 {
        font-size: 2.4rem;
      }
      .section {
        padding: 3rem 1.5rem;
      }
      .section-header h2 {
        font-size: 2rem;
      }
      .cta-section {
        padding: 2.5rem 1.5rem;
        border-radius: 28px;
      }
      .cta-section h2 {
        font-size: 1.8rem;
      }
      .footer {
        flex-direction: column;
        text-align: center;
        padding: 2rem 1.5rem 1rem;
      }
    }

    @media (max-width: 480px) {
      .hero-text h1 {
        font-size: 2rem;
      }
      .hero-buttons {
        flex-direction: column;
        align-items: center;
      }
      .hero-stats {
        flex-direction: column;
        gap: 0.8rem;
        align-items: center;
      }
      .service-card {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

  <!-- ===== NAVBAR ===== -->
  <nav class="landing-nav" id="navbar">
    <a href="#" class="nav-brand">
      <i class="fas fa-seedling"></i>
      <span>Pétalos</span>
    </a>

    <ul class="nav-links" id="navLinks">
      <li><a href="#servicios">Servicios</a></li>
      <li><a href="#testimonios">Testimonios</a></li>
      <li><a href="#contacto">Contacto</a></li>
    </ul>

    <div class="nav-actions">
      <a href="login.php" class="btn-outline-nav">Iniciar sesión</a>
      <a href="registro.php" class="btn-primary-nav">Registrarse</a>
    </div>

    <button class="hamburger" id="hamburger" aria-label="Menú">
      <span></span>
      <span></span>
      <span></span>
    </button>
  </nav>

  <!-- ===== HERO ===== -->
  <section class="hero">
    <div class="hero-content">
      <div class="hero-text">
        <div class="hero-badge">
          <i class="fas fa-sparkles"></i>
          Nueva plataforma 2026
        </div>
        <h1>
          Gestiona tu <br>
          <span class="highlight">floristería</span> con <br>
          amor y tecnología
        </h1>
        <p>
          Administra pedidos, clientes, inventario y ventas en un solo lugar. 
          Diseñado para floristas que quieren crecer.
        </p>
        <div class="hero-buttons">
          <a href="#" class="btn-hero-primary">
            <i class="fas fa-rocket"></i> Empezar ahora
          </a>
          <a href="#" class="btn-hero-secondary">
            <i class="fas fa-play-circle"></i> Ver demo
          </a>
        </div>
        <div class="hero-stats">
          <div class="stat-item">
            <span class="stat-number">2,400+</span>
            <span class="stat-label">Pedidos gestionados</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">98%</span>
            <span class="stat-label">Satisfacción</span>
          </div>
          <div class="stat-item">
            <span class="stat-number">350+</span>
            <span class="stat-label">Floristerías</span>
          </div>
        </div>
      </div>
      <div class="hero-image">
        <div class="image-placeholder">
          <i class="fas fa-seedling"></i>
          <span class="label">🌸 Pétalos</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== SERVICIOS ===== -->
  <section class="section" id="servicios">
    <div class="section-header">
      <h2>Todo lo que <span>necesitas</span></h2>
      <p>Herramientas diseñadas para que tu floristería florezca</p>
    </div>
    <div class="services-grid">
      <div class="service-card">
        <div class="icon"><i class="fas fa-shopping-bag"></i></div>
        <h3>Gestión de pedidos</h3>
        <p>Recibe, organiza y da seguimiento a todos tus pedidos en tiempo real.</p>
      </div>
      <div class="service-card">
        <div class="icon"><i class="fas fa-boxes"></i></div>
        <h3>Inventario inteligente</h3>
        <p>Controla tus flores, materiales y arreglos con alertas de stock bajo.</p>
      </div>
      <div class="service-card">
        <div class="icon"><i class="fas fa-users"></i></div>
        <h3>Clientes y ventas</h3>
        <p>Historial de compras, preferencias y análisis de tus clientes.</p>
      </div>
      <div class="service-card">
        <div class="icon"><i class="fas fa-chart-line"></i></div>
        <h3>Reportes y analytics</h3>
        <p>Visualiza tus ventas, tendencias y toma decisiones con datos.</p>
      </div>
    </div>
  </section>

  <!-- ===== TESTIMONIOS ===== -->
  <section class="section" id="testimonios">
    <div class="section-header">
      <h2>Lo que dicen <span>nuestros clientes</span></h2>
      <p>Historias reales de floristerías que ya confían en Pétalos</p>
    </div>
    <div class="testimonials-grid">
      <div class="testimonial-card">
        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
        <blockquote>"Pétalos transformó mi negocio. Antes perdía pedidos, ahora todo está organizado y mis ventas subieron un 40%."</blockquote>
        <div class="author">
          <div class="avatar">MG</div>
          <div><div class="name">María González</div><div class="role">Floristería La Rosa</div></div>
        </div>
      </div>
      <div class="testimonial-card">
        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
        <blockquote>"La interfaz es hermosa y muy fácil de usar. Mis empleados aprendieron en un día. Recomendado 100%."</blockquote>
        <div class="author">
          <div class="avatar">JR</div>
          <div><div class="name">Javier Ramírez</div><div class="role">Jardín de Flores</div></div>
        </div>
      </div>
      <div class="testimonial-card">
        <div class="stars"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i></div>
        <blockquote>"El sistema de inventario me salvó de tener pérdidas. Ahora sé exactamente qué y cuándo pedir."</blockquote>
        <div class="author">
          <div class="avatar">AL</div>
          <div><div class="name">Ana Lucía</div><div class="role">Pétalos & Co.</div></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== CTA FINAL ===== -->
  <section class="section" id="contacto">
    <div class="cta-section">
      <h2>¿Listo para hacer <br>crecer tu floristería?</h2>
      <p>Únete a cientos de floristas que ya están usando Pétalos para gestionar su negocio.</p>
      <a href="#" class="btn-cta">
        <i class="fas fa-seedling"></i> Comenzar ahora
      </a>
    </div>
  </section>

  <!-- ===== FOOTER ===== -->
  <footer class="footer">
    <div class="copy">
      <i class="fas fa-seedling" style="color:#c87a5a; margin-right:6px;"></i>
      &copy; 2026 Pétalos · Hecho con amor para floristas
    </div>
    <div class="social">
      <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
      <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
      <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
    </div>
  </footer>

  <script>
    // Navbar scroll effect
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 20) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });

    // Hamburger menu
    const hamburger = document.getElementById('hamburger');
    const navLinks = document.getElementById('navLinks');

    hamburger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
    });

    // Cerrar menú al hacer clic en un enlace
    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => {
        navLinks.classList.remove('open');
      });
    });
  </script>

</body>
</html>