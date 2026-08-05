<!-- ===== NAVBAR ===== -->
    <header class="navbar">
      <div class="navbar-left">
        <button class="sidebar-toggle" id="sidebarToggle">
          <i class="fas fa-bars"></i>
        </button>
        <div class="navbar-brand">
          <i class="fas fa-seedling"></i>
          <span>Pétalos</span>
          <span style="font-weight:400; font-size:0.75rem; color:#7f6e5d; margin-left:0.2rem;">· gestión</span>
        </div>
      </div>
      <div class="navbar-actions">
        <div class="search-box">
          <input type="text" placeholder="Buscar pedido, cliente...">
          <i class="fas fa-search"></i>
        </div>
        <div class="navbar-icons">
          <div class="icon-badge notification-bell" id="notificationBell" title="Notificaciones">
            <i class="far fa-bell"></i>
            <span class="badge-count" id="notificationCount" style="display:none;">0</span>

            <!-- ===== PANEL DESPLEGABLE ===== -->
            <div class="notification-panel" id="notificationPanel">
              <div class="notification-header">
                <span><i class="fas fa-bell" style="color:#c87a5a; margin-right:0.4rem;"></i>Notificaciones</span>
                <a href="#" id="notificationMarkAll"><i class="fas fa-check-double"></i> Marcar leídas</a>
              </div>
              <ul class="notification-list" id="notificationList"></ul>
              <div class="notification-empty" id="notificationEmpty" style="display:none;">
                <i class="far fa-bell-slash"></i>
                <p>No tienes notificaciones por ahora.</p>
              </div>
            </div>
            <!-- ===== FIN PANEL ===== -->

          </div>
          <div class="icon-badge">
            <i class="far fa-envelope"></i>
          </div>
          <div class="avatar"><?php echo htmlspecialchars(substr($_SESSION['nombre'], 0, 2)); ?></div>
        </div>
      </div>
    </header>

    <style>
      .navbar {
        position: relative;
        z-index: 999;
      }

      .navbar-icons .icon-badge.notification-bell {
        cursor: pointer;
        transition: 0.2s;
      }

      .navbar-icons .icon-badge.notification-bell:hover {
        border-color: #c87a5a;
        color: #c87a5a;
      }

      .navbar-icons .icon-badge.notification-bell .badge-count {
        position: absolute;
        top: -6px;
        right: -6px;
        min-width: 18px;
        height: 18px;
        background: #d46a4a;
        color: #fff;
        border-radius: 20px;
        border: 2px solid #fff;
        font-size: 0.6rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 4px;
        line-height: 1;
      }

      .notification-panel {
        position: absolute;
        top: calc(100% + 12px);
        right: 0;
        z-index: 1200;
        width: 360px;
        max-width: calc(100vw - 40px);
        background: #fff;
        border: 1px solid #ede8e0;
        border-radius: 16px;
        box-shadow: 0 18px 48px rgba(45, 42, 36, 0.16);
        display: none;
        overflow: hidden;
      }

      .notification-bell.open .notification-panel {
        display: block;
        animation: notificationFade 0.18s ease;
      }

      @keyframes notificationFade {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
      }

      .notification-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.9rem 1.1rem;
        border-bottom: 1px solid #ede8e0;
        font-weight: 700;
        font-size: 0.85rem;
        color: #3b2e26;
      }

      .notification-header a {
        font-size: 0.7rem;
        font-weight: 600;
        color: #c87a5a;
        text-decoration: none;
        white-space: nowrap;
      }

      .notification-header a:hover {
        text-decoration: underline;
      }

      .notification-list {
        list-style: none;
        margin: 0;
        padding: 0;
        max-height: 380px;
        overflow-y: auto;
      }

      .notification-list::-webkit-scrollbar {
        width: 6px;
      }

      .notification-list::-webkit-scrollbar-thumb {
        background: #e8e1d7;
        border-radius: 6px;
      }

      .notification-item {
        display: flex;
        gap: 0.8rem;
        padding: 0.85rem 1.1rem;
        border-bottom: 1px solid #f4f0ea;
        align-items: flex-start;
      }

      .notification-item:last-child {
        border-bottom: none;
      }

      .notification-item:hover {
        background: #faf8f5;
      }

      .notification-item .notif-icon {
        width: 36px;
        height: 36px;
        border-radius: 12px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.95rem;
      }

      .notification-item .notif-body {
        flex: 1;
        min-width: 0;
      }

      .notification-item .notif-title {
        font-weight: 600;
        font-size: 0.78rem;
        color: #2d2a24;
      }

      .notification-item .notif-text {
        font-size: 0.72rem;
        color: #7f6e5d;
        margin-top: 0.15rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }

      .notification-item .notif-time {
        font-size: 0.62rem;
        color: #a28d7a;
        margin-top: 0.3rem;
      }

      .notification-item.unread {
        background: #fbf3ee;
      }

      .notification-item.unread .notif-title::after {
        content: '';
        display: inline-block;
        width: 7px;
        height: 7px;
        background: #d46a4a;
        border-radius: 50%;
        margin-left: 0.45rem;
        vertical-align: middle;
      }

      .notification-empty {
        text-align: center;
        padding: 2.2rem 1.2rem;
        color: #a28d7a;
        font-size: 0.8rem;
      }

      .notification-empty i {
        font-size: 1.8rem;
        color: #dccfc2;
        display: block;
        margin-bottom: 0.6rem;
      }

      .notification-empty p {
        margin: 0;
      }

      @media (max-width: 576px) {
        .notification-panel {
          right: 0;
          width: calc(100vw - 40px);
          max-width: calc(100vw - 40px);
        }
      }
    </style>

    <script>
      (function () {
        const CSRF = <?php echo json_encode($_SESSION['csrf_token'] ?? ''); ?>;
        const API_OBTENER = '../controladores/notificaciones/obtener.php';
        const API_MARCAR = '../controladores/notificaciones/marcar_leidas.php';

        const bell = document.getElementById('notificationBell');
        const panel = document.getElementById('notificationPanel');
        const countEl = document.getElementById('notificationCount');
        const listEl = document.getElementById('notificationList');
        const emptyEl = document.getElementById('notificationEmpty');
        const markAllEl = document.getElementById('notificationMarkAll');

        const TIPO_META = {
          pedido:     { icon: 'fa-shopping-bag',   bg: '#f9ede0', color: '#c87a5a' },
          inventario: { icon: 'fa-cubes',          bg: '#fdf3e0', color: '#a07d2a' },
          pago:       { icon: 'fa-credit-card',    bg: '#e5f1f0', color: '#3a7a72' },
          entrega:    { icon: 'fa-truck',          bg: '#e0edf5', color: '#4a7a8a' },
          sistema:    { icon: 'fa-cog',            bg: '#ede6f0', color: '#7a5a8a' }
        };
        const DEFAULT_META = { icon: 'fa-bell', bg: '#f4f0ea', color: '#6b5d4f' };

        function esc(texto) {
          const d = document.createElement('div');
          d.textContent = texto == null ? '' : String(texto);
          return d.innerHTML;
        }

        function timeAgo(fechaStr) {
          if (!fechaStr) return '';
          const fecha = new Date(String(fechaStr).replace(' ', 'T'));
          if (isNaN(fecha.getTime())) return fechaStr;
          const min = Math.floor((Date.now() - fecha.getTime()) / 60000);
          if (min < 1) return 'ahora mismo';
          if (min < 60) return 'hace ' + min + ' min';
          const h = Math.floor(min / 60);
          if (h < 24) return 'hace ' + h + ' h';
          const d = Math.floor(h / 24);
          if (d < 7) return 'hace ' + d + ' día' + (d > 1 ? 's' : '');
          return fecha.toLocaleDateString('es-EC', { day: '2-digit', month: 'short', year: 'numeric' });
        }

        function setCount(n) {
          n = parseInt(n, 10) || 0;
          if (n > 0) {
            countEl.textContent = n > 99 ? '99+' : n;
            countEl.style.display = 'flex';
          } else {
            countEl.style.display = 'none';
          }
        }

        function renderList(items) {
          listEl.innerHTML = '';
          if (!items || !items.length) {
            emptyEl.style.display = 'block';
            return;
          }
          emptyEl.style.display = 'none';
          items.forEach(function (n) {
            const meta = TIPO_META[n.tipo] || DEFAULT_META;
            const li = document.createElement('li');
            li.className = 'notification-item' + (n.leida === 'no' ? ' unread' : '');
            li.innerHTML =
              '<div class="notif-icon" style="background:' + meta.bg + ';color:' + meta.color + ';">' +
                '<i class="fas ' + meta.icon + '"></i>' +
              '</div>' +
              '<div class="notif-body">' +
                '<div class="notif-title">' + esc(n.titulo) + '</div>' +
                '<div class="notif-text">' + esc(n.mensaje) + '</div>' +
                '<div class="notif-time"><i class="far fa-clock"></i> ' + esc(timeAgo(n.fecha)) + '</div>' +
              '</div>';
            listEl.appendChild(li);
          });
        }

        function cargarNotificaciones(marcarLeidas) {
          fetch(API_OBTENER)
            .then(function (r) { return r.json(); })
            .then(function (data) {
              if (data.status !== 'success') return;
              setCount(data.no_leidas);
              renderList(data.listado);
              if (marcarLeidas && data.no_leidas > 0) {
                const body = new URLSearchParams({ csrf_token: CSRF });
                fetch(API_MARCAR, {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                  body: body.toString()
                })
                  .then(function (r) { return r.json(); })
                  .then(function () {
                    setCount(0);
                    if (bell.classList.contains('open')) cargarNotificaciones(false);
                  })
                  .catch(function () {});
              }
            })
            .catch(function () {});
        }

        bell.addEventListener('click', function (e) {
          e.stopPropagation();
          const abrir = !bell.classList.contains('open');
          bell.classList.toggle('open', abrir);
          if (abrir) {
            cargarNotificaciones(true);
          }
        });

        markAllEl.addEventListener('click', function (e) {
          e.preventDefault();
          const body = new URLSearchParams({ csrf_token: CSRF });
          fetch(API_MARCAR, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
          })
            .then(function (r) { return r.json(); })
            .then(function () {
              setCount(0);
              cargarNotificaciones(false);
            })
            .catch(function () {});
        });

        document.addEventListener('click', function (e) {
          if (!bell.contains(e.target)) {
            bell.classList.remove('open');
          }
        });

        document.addEventListener('keydown', function (e) {
          if (e.key === 'Escape') bell.classList.remove('open');
        });

        // Carga inicial + refresco periódico del contador
        cargarNotificaciones(false);
        setInterval(function () {
          cargarNotificaciones(false);
        }, 30000);
      })();
    </script>
