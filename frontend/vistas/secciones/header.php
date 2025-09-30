<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Valores por defecto si no hay sesión
$nombreUsuario = $_SESSION["username"] ?? "Invitado";
$imagenUsuario = $_SESSION["imagen"] ?? ""; 
?>
<header class="header">
  <nav class="nav-bar">
    <!-- Botón hamburguesa -->
    <button class="menu-toggle" id="menu-toggle" aria-label="Abrir menú">
      <span class="bar"></span>
      <span class="bar"></span>
      <span class="bar"></span>
    </button>

    <!-- Logo -->
    <a href="../usuario/index.php" class="logo">
      <img src="../../img/logoChico.png" alt="Logo Tambosoft" />
    </a>

    <div class="user-info">
      <!-- Campana -->
      <i class="fas fa-bell notif-icon"></i>

      <!-- Avatar -->
      <img src="../../img/<?php echo htmlspecialchars($imagenUsuario); ?>"
        alt="Avatar de <?php echo htmlspecialchars($nombreUsuario); ?>" class="user-avatar" />

      <!-- Dropdown usuario -->
      <div class="user-dropdown" id="user-dropdown">
        <button class="user-name" id="user-toggle">
          <?php echo htmlspecialchars($nombreUsuario); ?> <span class="arrow">▼</span>
        </button>
        <ul class="dropdown-menu" id="dropdown-menu">
          <li><a href="../usuario/cerrarSesion.php">Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </nav>
</header>