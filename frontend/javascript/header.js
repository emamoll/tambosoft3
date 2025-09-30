document.addEventListener("DOMContentLoaded", function () {
  const toggleBtn = document.getElementById("menu-toggle");
  const sidebar = document.getElementById("sidebar");
  const userToggle = document.getElementById("user-toggle");
  const userDropdown = document.getElementById("user-dropdown");
  const notifToggle = document.getElementById("notif-toggle");
  const notifPanel = document.getElementById("notif-panel");

  // Toggle menú lateral
  if (toggleBtn && sidebar) {
    toggleBtn.addEventListener("click", () => {
      sidebar.classList.toggle("expanded");
      toggleBtn.classList.toggle("active");
    });
  }

  // Toggle dropdown usuario
  if (userToggle && userDropdown) {
    userToggle.addEventListener("click", () => {
      userDropdown.classList.toggle("active");
    });
  }

  // Toggle panel de notificaciones
  if (notifToggle && notifPanel) {
    notifToggle.addEventListener("click", () => {
      notifPanel.classList.toggle("active");
    });
  }
});
