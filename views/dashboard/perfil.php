<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['usuario'])) {
    $_rProto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $_rHost  = $_SERVER['HTTP_HOST'];
    $_rBase  = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    header("Location: {$_rProto}://{$_rHost}{$_rBase}/views/usuarios/login.php");
    exit;
}
$usuario = $_SESSION['usuario'];

$rolNombre = match((string)$usuario['id_rol']) {
    '1','administrador' => 'Administrador',
    '2','empleado'      => 'Empleado',
    default             => 'Cliente',
};
$rolColor = match((string)$usuario['id_rol']) {
    '1','administrador' => ['bg'=>'#FEE2E2','color'=>'#DC2626','icon'=>'fa-user-shield'],
    '2','empleado'      => ['bg'=>'#DBEAFE','color'=>'#2563EB','icon'=>'fa-user-tie'],
    default             => ['bg'=>'#D1FAE5','color'=>'#059669','icon'=>'fa-user'],
};

$titulo = "MI PERFIL";
require_once __DIR__ . '/../../Controllers/PerfilController.php';
$controller = new PerfilController();
$controller->manejarPeticion();
$perfil = $controller->obtenerUsuario();

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/sidebar.php';
?>

<div class="max-w-2xl mx-auto space-y-6">

  <!-- Header -->
  <div>
    <h1 class="text-3xl font-heading font-bold text-retro-dark flex items-center gap-2">
      <i class="fas fa-user-circle text-retro-red"></i> Mi Perfil
    </h1>
    <p class="text-gray-500 font-body text-sm mt-1">Edita tu información personal y contraseña.</p>
  </div>

  <!-- Alertas -->
  <?php if (isset($_SESSION['perfil_ok'])): ?>
  <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-check-circle"></i>
    <span class="font-body text-sm"><?= htmlspecialchars($_SESSION['perfil_ok']) ?></span>
  </div>
  <?php unset($_SESSION['perfil_ok']); endif; ?>

  <?php if (isset($_SESSION['perfil_error'])): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-center gap-2">
    <i class="fas fa-exclamation-circle"></i>
    <span class="font-body text-sm"><?= htmlspecialchars($_SESSION['perfil_error']) ?></span>
  </div>
  <?php unset($_SESSION['perfil_error']); endif; ?>

  <!-- Card perfil -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">

    <!-- Banner + avatar -->
    <div class="h-24 bg-gradient-to-r from-retro-dark to-gray-700 relative">
      <div class="absolute -bottom-10 left-8">
        <div class="relative group cursor-pointer" onclick="document.getElementById('inputFoto').click()" title="Cambiar foto">
          <?php if (!empty($perfil['foto'])): ?>
          <img src="../../img/perfiles/<?= htmlspecialchars($perfil['foto']) ?>"
               id="avatarImg"
               class="w-20 h-20 rounded-full border-4 border-white shadow-lg object-cover">
          <?php else: ?>
          <div id="avatarImg"
               class="w-20 h-20 rounded-full border-4 border-white shadow-lg flex items-center justify-center text-3xl font-heading font-bold"
               style="background:<?= $rolColor['bg'] ?>;color:<?= $rolColor['color'] ?>;">
            <?= strtoupper(substr($perfil['nombre'] ?? 'U', 0, 1)) ?>
          </div>
          <?php endif; ?>
          <!-- Overlay hover -->
          <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
            <i class="fas fa-camera text-white text-lg"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="pt-14 px-8 pb-6">
      <div class="flex items-center gap-3 mb-6">
        <div>
          <p class="text-xl font-heading font-bold text-retro-dark">
            <?= htmlspecialchars(($perfil['nombre'] ?? '').' '.($perfil['apellidos'] ?? '')) ?>
          </p>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold mt-1"
                style="background:<?= $rolColor['bg'] ?>;color:<?= $rolColor['color'] ?>;">
            <i class="fas <?= $rolColor['icon'] ?> text-xs"></i>
            <?= $rolNombre ?>
          </span>
        </div>
      </div>

      <!-- Formulario -->
      <form action="perfil.php" method="POST" enctype="multipart/form-data" class="space-y-5">
        <!-- Input oculto para la foto -->
        <input type="file" id="inputFoto" name="foto" accept="image/jpeg,image/png,image/webp,image/gif"
               class="hidden" onchange="previsualizarFoto(this)">
        <p id="fotoNombre" class="text-xs text-gray-400 hidden"></p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($perfil['nombre'] ?? '') ?>" required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm transition">
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Apellidos <span class="text-red-500">*</span></label>
            <input type="text" name="apellidos" value="<?= htmlspecialchars($perfil['apellidos'] ?? '') ?>" required
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm transition">
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Correo electrónico</label>
            <input type="email" value="<?= htmlspecialchars($perfil['email'] ?? '') ?>" disabled
              class="w-full px-4 py-3 border-2 border-gray-100 bg-gray-50 text-gray-400 rounded-xl font-body text-sm cursor-not-allowed">
            <p class="text-xs text-gray-400 mt-1">El correo no se puede cambiar.</p>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Teléfono</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($perfil['telefono'] ?? '') ?>"
              class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm transition">
          </div>
        </div>

        <!-- Separador contraseña -->
        <div class="border-t border-gray-100 pt-5">
          <p class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-lock text-gray-400"></i> Cambiar contraseña
            <span class="font-normal text-gray-400 text-xs">(dejar en blanco para no cambiar)</span>
          </p>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="relative">
              <label class="block text-sm font-bold text-gray-700 mb-1">Nueva contraseña</label>
              <input type="password" name="password" id="pwd" placeholder="••••••••"
                class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm transition">
              <button type="button" onclick="togglePwd('pwd','eyePwd')"
                class="absolute right-3 top-9 text-gray-400 hover:text-gray-600">
                <i class="fas fa-eye text-sm" id="eyePwd"></i>
              </button>
            </div>
            <div class="relative">
              <label class="block text-sm font-bold text-gray-700 mb-1">Confirmar contraseña</label>
              <input type="password" name="confirmar" id="pwd2" placeholder="••••••••"
                class="w-full px-4 py-3 pr-11 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-retro-red font-body text-sm transition">
              <button type="button" onclick="togglePwd('pwd2','eyePwd2')"
                class="absolute right-3 top-9 text-gray-400 hover:text-gray-600">
                <i class="fas fa-eye text-sm" id="eyePwd2"></i>
              </button>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <a href="javascript:history.back()"
            class="px-5 py-2 text-gray-500 hover:bg-gray-100 rounded-xl font-body text-sm transition">
            Cancelar
          </a>
          <button type="submit"
            class="px-6 py-2 bg-retro-red hover:bg-red-700 text-white rounded-xl font-heading text-sm shadow transition flex items-center gap-2">
            <i class="fas fa-save"></i> Guardar cambios
          </button>
        </div>

      </form>
    </div>
  </div>

  <!-- Info de sesión -->
  <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
    <p class="text-sm font-bold text-gray-700 mb-3 flex items-center gap-2">
      <i class="fas fa-info-circle text-gray-400"></i> Información de sesión
    </p>
    <div class="grid grid-cols-2 gap-4 text-sm font-body">
      <div class="bg-gray-50 rounded-xl p-3">
        <p class="text-xs text-gray-400 mb-1">ID de usuario</p>
        <p class="font-semibold text-gray-700">#<?= $perfil['id_usuario'] ?? '—' ?></p>
      </div>
      <div class="bg-gray-50 rounded-xl p-3">
        <p class="text-xs text-gray-400 mb-1">Rol</p>
        <p class="font-semibold" style="color:<?= $rolColor['color'] ?>"><?= $rolNombre ?></p>
      </div>
      <div class="bg-gray-50 rounded-xl p-3 col-span-2">
        <p class="text-xs text-gray-400 mb-1">Correo</p>
        <p class="font-semibold text-gray-700"><?= htmlspecialchars($perfil['email'] ?? '—') ?></p>
      </div>
    </div>
  </div>

</div>

<script>
function togglePwd(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon  = document.getElementById(iconId);
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

function previsualizarFoto(input) {
  if (!input.files || !input.files[0]) return;
  const file   = input.files[0];
  const reader = new FileReader();
  reader.onload = function(e) {
    const container = document.getElementById('avatarImg').parentElement;
    // Reemplazar el avatar con la imagen previsualizada
    container.innerHTML = `
      <img src="${e.target.result}" id="avatarImg"
           class="w-20 h-20 rounded-full border-4 border-white shadow-lg object-cover">
      <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
        <i class="fas fa-camera text-white text-lg"></i>
      </div>`;
  };
  reader.readAsDataURL(file);
  // Mostrar nombre del archivo
  const label = document.getElementById('fotoNombre');
  label.textContent = '📎 ' + file.name;
  label.classList.remove('hidden');
}
</script>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
