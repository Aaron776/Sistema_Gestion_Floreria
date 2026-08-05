<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Página no encontrada · Pétalos</title>
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

    body {
      font-family: 'Inter', sans-serif;
      background: #faf8f5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      background-image: 
        radial-gradient(ellipse at 20% 30%, rgba(200, 122, 90, 0.06) 0%, transparent 50%),
        radial-gradient(ellipse at 80% 70%, rgba(200, 122, 90, 0.04) 0%, transparent 50%);
    }

    .error-container {
      max-width: 600px;
      width: 100%;
      text-align: center;
      padding: 2.5rem;
    }

    .error-icon {
      font-size: 8rem;
      color: #c87a5a;
      opacity: 0.15;
      margin-bottom: -0.5rem;
      line-height: 1;
    }

    .error-code {
      font-size: 8rem;
      font-weight: 800;
      color: #2d2a24;
      letter-spacing: -4px;
      line-height: 1;
      margin-top: -1.5rem;
    }

    .error-code span {
      color: #c87a5a;
    }

    .error-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: #2d2a24;
      margin-top: 0.5rem;
    }

    .error-description {
      color: #6b5d4f;
      font-size: 1.05rem;
      max-width: 400px;
      margin: 0.8rem auto 2rem;
      line-height: 1.7;
    }

    .error-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .btn-primary {
      padding: 0.9rem 2.4rem;
      border-radius: 60px;
      border: none;
      background: #2d2a24;
      color: #f4f1eb;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.25s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Inter', sans-serif;
    }

    .btn-primary:hover {
      background: #1e1a16;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-secondary {
      padding: 0.9rem 2.4rem;
      border-radius: 60px;
      border: 2px solid #dccfc2;
      background: transparent;
      color: #2d2a24;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.25s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-family: 'Inter', sans-serif;
    }

    .btn-secondary:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
      transform: translateY(-2px);
    }

    .error-illustration {
      margin: 1.5rem 0;
      font-size: 4rem;
      color: #dccfc2;
      display: flex;
      justify-content: center;
      gap: 1rem;
    }

    .error-illustration i {
      opacity: 0.5;
    }

    .error-illustration i:last-child {
      opacity: 0.8;
      color: #c87a5a;
    }

    .quick-links {
      margin-top: 2.5rem;
      padding-top: 2rem;
      border-top: 1px solid #ede8e0;
      display: flex;
      justify-content: center;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .quick-links a {
      color: #7f6e5d;
      text-decoration: none;
      font-size: 0.85rem;
      transition: color 0.2s;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .quick-links a:hover {
      color: #c87a5a;
    }

    @media (max-width: 480px) {
      .error-code {
        font-size: 6rem;
      }
      .error-icon {
        font-size: 6rem;
      }
      .error-title {
        font-size: 1.5rem;
      }
      .error-actions {
        flex-direction: column;
        align-items: center;
      }
      .btn-primary, .btn-secondary {
        width: 100%;
        justify-content: center;
      }
    }
  </style>
</head>
<body>

  <div class="error-container">
    <div class="error-icon">
      <i class="fas fa-seedling"></i>
    </div>

    <div class="error-code">
      4<span>0</span>4
    </div>

    <div class="error-illustration">
      <i class="fas fa-seedling"></i>
      <i class="fas fa-leaf"></i>
      <i class="fas fa-seedling"></i>
    </div>

    <h1 class="error-title">Página no encontrada</h1>
    <p class="error-description">
      Lo sentimos, la página que buscas no existe o ha sido movida. 
      Pero no te preocupes, aún puedes encontrar lo que necesitas.
    </p>

    <div class="error-actions">
      <a href="#" class="btn-primary">
        <i class="fas fa-home"></i> Ir al inicio
      </a>
      <a href="#" class="btn-secondary">
        <i class="fas fa-arrow-left"></i> Volver atrás
      </a>
    </div>

    <div class="quick-links">
      <a href="#"><i class="fas fa-shopping-bag"></i> Pedidos</a>
      <a href="#"><i class="fas fa-seedling"></i> Productos</a>
      <a href="#"><i class="fas fa-users"></i> Clientes</a>
      <a href="#"><i class="fas fa-question-circle"></i> Ayuda</a>
    </div>
  </div>

</body>
</html>