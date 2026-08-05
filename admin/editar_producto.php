<?php
require_once '../autorizacion/auth.php';
require_once '../conexion/bd.php';
require_once '../helpers/Encriptar.php';



if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
  header("Location: ../acceso_denegado.php");
  exit();
}

if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$id_producto=Crypto::decrypt($_GET['id_producto']);
if(empty($id_producto) || $id_producto<=0 || !is_numeric($id_producto)){
    header("Location: gestion_productos.php");
    exit;
}

// Traer datos del producto que voy a editar
try {
  $sql=$conexion->prepare("SELECT nombre,descripcion,precio,stock,id_categoria,imagen FROM productos WHERE id_producto=:id_producto");
  $sql->bindParam(":id_producto",$id_producto,PDO::PARAM_INT);
  $sql->execute();
  $producto=$sql->fetch(PDO::FETCH_OBJ);
  if(empty($producto)){
    header("Location: gestion_productos.php");
    exit;
  }

  // Traer lisatdo de categorias
    $sql=$conexion->prepare("SELECT id_categoria as categoria_id,nombre FROM categorias");
    $sql->execute();
    $categorias=$sql->fetchAll(PDO::FETCH_OBJ);

} catch (PDOException $e) {
  error_log("Error en la consultas a la base de datos: " . $e->getMessage());
  $producto = [];
}

include_once '../templates/header.php';
?>
  <style>
    .create-container {
      max-width: 600px;
      width: 100%;
      margin: 0 auto;
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(12px);
      border-radius: 40px;
      padding: 2.8rem 2.5rem;
      border: 1px solid rgba(255, 245, 235, 0.5);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.06), 0 8px 24px rgba(0, 0, 0, 0.03);
    }

    .create-header {
      text-align: center;
      margin-bottom: 2rem;
    }

    .create-header .brand {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.6rem;
      font-weight: 700;
      color: #2d2a24;
      text-decoration: none;
      margin-bottom: 0.3rem;
    }

    .create-header .brand i {
      color: #c87a5a;
      font-size: 1.8rem;
    }

    .create-header .back-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      color: #7f6e5d;
      text-decoration: none;
      font-size: 0.85rem;
      margin-bottom: 0.8rem;
      transition: color 0.2s;
    }

    .create-header .back-link:hover {
      color: #c87a5a;
    }

    .create-header .icon-wrapper {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 64px;
      height: 64px;
      background: rgba(200, 122, 90, 0.1);
      border-radius: 50%;
      margin-bottom: 0.8rem;
    }

    .create-header .icon-wrapper i {
      font-size: 2rem;
      color: #c87a5a;
    }

    .create-header h1 {
      font-size: 1.6rem;
      font-weight: 700;
      color: #2d2a24;
    }

    .create-header p {
      color: #7f6e5d;
      font-size: 0.95rem;
      margin-top: 0.2rem;
    }

    .create-header .badge-admin {
      display: inline-block;
      background: #ede6f0;
      color: #7a5a8a;
      padding: 0.2rem 0.8rem;
      border-radius: 60px;
      font-size: 0.7rem;
      font-weight: 600;
      margin-top: 0.3rem;
    }

    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-group label {
      display: block;
      font-weight: 600;
      font-size: 0.82rem;
      color: #2d2a24;
      margin-bottom: 0.3rem;
    }

    .form-group label .required {
      color: #b05b4b;
      margin-left: 2px;
    }

    .form-group .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .form-group .input-wrapper i {
      position: absolute;
      left: 1rem;
      color: #a28d7a;
      font-size: 0.95rem;
      transition: color 0.2s;
    }

    .form-group .input-wrapper:focus-within i {
      color: #c87a5a;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.8rem 1rem 0.8rem 2.8rem;
      border-radius: 60px;
      border: 2px solid #ede8e0;
      background: #faf8f5;
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      transition: all 0.2s;
      color: #2d2a24;
      appearance: none;
    }

    .form-group select {
      padding-right: 2.8rem;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b5d4f' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1.2rem center;
      cursor: pointer;
    }

    .form-group select:focus {
      outline: none;
      border-color: #c87a5a;
      background-color: #ffffff;
      box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #c87a5a;
      background: #ffffff;
      box-shadow: 0 0 0 4px rgba(200, 122, 90, 0.08);
    }

    .form-group textarea {
      padding: 0.8rem 1rem 0.8rem 2.8rem;
      border-radius: 24px;
      min-height: 90px;
      resize: vertical;
      line-height: 1.6;
    }

    .form-group input::placeholder,
    .form-group textarea::placeholder {
      color: #b5aca2;
    }

    .form-group input.error,
    .form-group select.error,
    .form-group textarea.error {
      border-color: #b05b4b;
      background: #fde8e4;
    }

    .form-group .error-text {
      font-size: 0.75rem;
      color: #b05b4b;
      margin-top: 0.3rem;
      display: none;
      align-items: center;
      gap: 0.4rem;
    }

    .form-group .error-text.show {
      display: flex;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.8rem;
    }

    /* ===== IMAGEN ===== */
    .current-image {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.8rem;
      background: #f8f5f0;
      border-radius: 16px;
      margin-bottom: 0.8rem;
    }

    .current-image img {
      width: 64px;
      height: 64px;
      border-radius: 12px;
      object-fit: cover;
      background: #ede8e0;
    }

    .current-image .image-info {
      flex: 1;
    }

    .current-image .image-info .file-name {
      font-weight: 500;
      font-size: 0.85rem;
      color: #2d2a24;
    }

    .current-image .image-info .file-hint {
      font-size: 0.75rem;
      color: #7f6e5d;
      margin-top: 0.1rem;
    }

    .image-upload-area {
      border: 2px dashed #ede8e0;
      border-radius: 24px;
      padding: 1.8rem;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s;
      background: #faf8f5;
      position: relative;
    }

    .image-upload-area:hover {
      border-color: #c87a5a;
      background: #f8f5f0;
    }

    .image-upload-area.dragover {
      border-color: #c87a5a;
      background: #f5efe8;
    }

    .image-upload-area .upload-icon {
      font-size: 2.5rem;
      color: #c87a5a;
      opacity: 0.5;
      margin-bottom: 0.5rem;
    }

    .image-upload-area .upload-text {
      font-size: 0.9rem;
      color: #6b5d4f;
    }

    .image-upload-area .upload-text strong {
      color: #c87a5a;
    }

    .image-upload-area .upload-hint {
      font-size: 0.75rem;
      color: #a28d7a;
      margin-top: 0.3rem;
    }

    .image-upload-area input[type="file"] {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      opacity: 0;
      cursor: pointer;
    }

    .image-preview {
      display: none;
      margin-top: 1rem;
      padding: 0.8rem;
      background: #f8f5f0;
      border-radius: 16px;
      align-items: center;
      gap: 1rem;
    }

    .image-preview.show {
      display: flex;
    }

    .image-preview img {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      object-fit: cover;
      background: #ede8e0;
    }

    .image-preview .file-info {
      flex: 1;
      text-align: left;
    }

    .image-preview .file-info .file-name {
      font-weight: 500;
      font-size: 0.85rem;
      color: #2d2a24;
    }

    .image-preview .file-info .file-size {
      font-size: 0.75rem;
      color: #7f6e5d;
    }

    .image-preview .btn-remove-image {
      background: #fde8e4;
      border: none;
      width: 32px;
      height: 32px;
      border-radius: 32px;
      cursor: pointer;
      color: #b05b4b;
      transition: all 0.2s;
      font-size: 0.8rem;
    }

    .image-preview .btn-remove-image:hover {
      background: #fcd4cc;
      transform: scale(1.05);
    }

    /* ===== BOTONES ===== */
    .form-actions {
      display: flex;
      gap: 0.8rem;
      margin-top: 0.5rem;
    }

    .btn-create {
      flex: 1;
      padding: 0.9rem;
      border-radius: 60px;
      border: none;
      background: #2d2a24;
      color: #f4f1eb;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.25s;
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
    }

    .btn-create:hover {
      background: #1e1a16;
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(45, 42, 36, 0.2);
    }

    .btn-create i {
      color: #c87a5a;
    }

    .btn-create:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    .btn-cancel {
      padding: 0.9rem 1.8rem;
      border-radius: 60px;
      border: 2px solid #dccfc2;
      background: transparent;
      color: #2d2a24;
      font-weight: 600;
      font-size: 0.95rem;
      cursor: pointer;
      transition: all 0.25s;
      font-family: 'Inter', sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      text-decoration: none;
    }

    .btn-cancel:hover {
      border-color: #b05b4b;
      background: #fde8e4;
      transform: translateY(-2px);
    }

    .alert {
      padding: 0.8rem 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-bottom: 1.2rem;
    }

    .alert-danger {
      background: #fde8e4;
      color: #b05b4b;
      border: 1px solid #f5c6bf;
    }

    .alert-danger ul {
      list-style: none;
      margin: 0;
      padding: 0;
    }

    .alert-danger ul li {
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .alert-success {
      background: #e2f0e6;
      color: #3f6a4f;
      border: 1px solid #c8e0d0;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .create-container {
        padding: 2rem 1.5rem;
        border-radius: 28px;
      }
      .create-header h1 {
        font-size: 1.4rem;
      }
      .create-header .icon-wrapper {
        width: 54px;
        height: 54px;
      }
      .create-header .icon-wrapper i {
        font-size: 1.6rem;
      }
      .form-row {
        grid-template-columns: 1fr;
        gap: 0;
      }
      .form-actions {
        flex-direction: column;
      }
      .btn-create, .btn-cancel {
        width: 100%;
        justify-content: center;
      }
      .image-preview {
        flex-direction: column;
        text-align: center;
      }
      .image-preview .file-info {
        text-align: center;
      }
    }
  </style>

  <div class="create-container">
    <div class="create-header">
      <a href="gestion_productos.php" class="brand">
        <i class="fas fa-seedling"></i>
        <span>Pétalos</span>
      </a>
      <div class="icon-wrapper">
        <i class="fas fa-edit"></i>
      </div>
      <h1>Editar producto</h1>
      <p>Actualiza la información de <strong><?= htmlspecialchars($producto->nombre, ENT_QUOTES, 'UTF-8') ?></strong></p>
      <span class="badge-admin"><i class="fas fa-user-shield"></i> Panel de Administración</span>
    </div>

    <?php if (isset($_SESSION['errores'])) : ?>
      <div class="alert alert-danger">
        <ul>
          <?php foreach ($_SESSION['errores'] as $error) : ?>
            <li><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php unset($_SESSION['errores']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['exito'])) : ?>
      <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars(is_array($_SESSION['exito']) ? $_SESSION['exito'][0] : $_SESSION['exito']); ?>
      </div>
      <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>

    <form id="editProductForm" action="../controladores/admin/editar_producto.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?=$_SESSION['csrf_token']?>">
      <input type="hidden" name="id_producto" value="<?= htmlspecialchars(Crypto::encrypt($id_producto), ENT_QUOTES, 'UTF-8') ?>">

      <!-- Nombre -->
      <div class="form-group">
        <label for="name">Nombre del producto <span class="required">*</span></label>
        <div class="input-wrapper">
          <i class="fas fa-tag"></i>
          <input type="text" id="name" name="nombre" value="<?= htmlspecialchars($producto->nombre, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Ramo de Rosas Rojas" required>
        </div>
      </div>

      <!-- Categoría -->
      <div class="form-group">
        <label for="category">Categoría <span class="required">*</span></label>
        <div class="input-wrapper">
          <i class="fas fa-tags"></i>
          <select id="category" name="categoria" required>
            <option value="">Selecciona una categoría</option>
            <?php foreach($categorias as $item): ?>
              <option value="<?=$item->categoria_id?>" <?= $item->categoria_id == $producto->id_categoria ? 'selected' : '' ?>><?=htmlspecialchars($item->nombre)?></option>
            <?php endforeach ?>
          </select>
        </div>
      </div>

      <!-- Descripción -->
      <div class="form-group">
        <label for="description">Descripción <span class="required">*</span></label>
        <div class="input-wrapper">
          <i class="fas fa-align-left"></i>
          <textarea id="description" name="descripcion" placeholder="Describe el producto, sus características y usos..." required><?= htmlspecialchars($producto->descripcion, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
      </div>

      <!-- Precio y Stock -->
      <div class="form-row">
        <div class="form-group">
          <label for="price">Precio <span class="required">*</span></label>
          <div class="input-wrapper">
            <i class="fas fa-dollar-sign"></i>
            <input type="number" name="precio" id="price" value="<?= htmlspecialchars($producto->precio, ENT_QUOTES, 'UTF-8') ?>" step="0.01" min="0" required>
          </div>
        </div>

        <div class="form-group">
          <label for="stock">Stock <span class="required">*</span></label>
          <div class="input-wrapper">
            <i class="fas fa-boxes"></i>
            <input type="number" id="stock" name="stock" value="<?= htmlspecialchars($producto->stock, ENT_QUOTES, 'UTF-8') ?>" min="0" required>
          </div>
        </div>
      </div>

      <!-- Imagen -->
      <div class="form-group">
        <label for="image">Imagen del producto</label>

        <?php if(!empty($producto->imagen)): ?>
          <div class="current-image" id="currentImage">
            <img src="../app/fotos_productos/<?= htmlspecialchars($producto->imagen, ENT_QUOTES, 'UTF-8') ?>" alt="Imagen actual">
            <div class="image-info">
              <div class="file-name"><?= htmlspecialchars($producto->imagen, ENT_QUOTES, 'UTF-8') ?></div>
              <div class="file-hint">Imagen actual del producto</div>
            </div>
          </div>
        <?php endif; ?>

        <div class="image-upload-area" id="imageUploadArea">
          <div class="upload-icon">
            <i class="fas fa-cloud-upload-alt"></i>
          </div>
          <div class="upload-text">
            <?= !empty($producto->imagen) ? 'Arrastra una nueva imagen para <strong>reemplazar</strong>' : 'Arrastra y suelta tu imagen aquí o <strong>selecciona un archivo</strong>' ?>
          </div>
          <div class="upload-hint">
            Formatos: JPG, PNG, GIF · Tamaño máximo: 5MB
          </div>
          <input type="file" id="image" name="imagen" accept="image/*">
        </div>

        <div class="image-preview" id="imagePreview">
          <img id="previewImg" src="#" alt="Vista previa">
          <div class="file-info">
            <div class="file-name" id="fileName">imagen.jpg</div>
            <div class="file-size" id="fileSize">0 KB</div>
          </div>
          <button type="button" class="btn-remove-image" id="removeImage">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Botones -->
      <div class="form-actions">
        <button type="submit" class="btn-create" id="submitBtn">
          <i class="fas fa-save"></i> Guardar cambios
        </button>
        <a href="gestion_productos.php" class="btn-cancel">
          <i class="fas fa-times"></i> Cancelar
        </a>
      </div>
    </form>
  </div>

  <script>
    const imageInput = document.getElementById('image');
    const previewArea = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const fileNameEl = document.getElementById('fileName');
    const fileSizeEl = document.getElementById('fileSize');
    const removeBtn = document.getElementById('removeImage');
    const uploadArea = document.getElementById('imageUploadArea');

    imageInput.addEventListener('change', function(e) {
      const file = this.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewArea.classList.add('show');
          uploadArea.style.display = 'none';
          fileNameEl.textContent = file.name;
          fileSizeEl.textContent = (file.size / 1024).toFixed(1) + ' KB';
        };
        reader.readAsDataURL(file);
      }
    });

    removeBtn.addEventListener('click', function() {
      imageInput.value = '';
      previewArea.classList.remove('show');
      uploadArea.style.display = 'block';
    });

    uploadArea.addEventListener('dragover', function(e) {
      e.preventDefault();
      this.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
      e.preventDefault();
      this.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
      e.preventDefault();
      this.classList.remove('dragover');
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        imageInput.files = files;
        imageInput.dispatchEvent(new Event('change'));
      }
    });

    document.querySelectorAll('.form-group input, .form-group select, .form-group textarea').forEach(function(input) {
      input.addEventListener('input', function() {
        this.classList.remove('error');
      });
    });
  </script>
<?php include_once '../templates/footer.php';?>