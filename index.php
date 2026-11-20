<?php
session_start();
if (!isset($_SESSION['usuario_dni'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Portal de Certificados ISEM | MYS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #f1f5f9;
    }
    .navbar-custom {
      background-color: #0f172a;
    }
    .table > :not(caption) > * > * {
      vertical-align: middle;
    }
  </style>
</head>
<body>

  <!-- Navbar Superior -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2 fw-bold" href="index.php">
        <i class="bi bi-file-earmark-check-fill text-primary fs-4"></i>
        <div>
          <span class="d-block lh-1">Certificados ISEM</span>
          <small class="text-white-50 fw-normal" style="font-size: 0.72rem;">Mantenimiento y Supervisión S.A.</small>
        </div>
      </a>

      <div class="d-flex align-items-center gap-2 gap-md-3 ms-auto flex-wrap">
        <span class="badge bg-secondary bg-opacity-25 text-light border border-secondary border-opacity-50 fw-normal py-2 px-3 rounded-pill d-none d-md-inline-flex align-items-center gap-1" id="badgeUpdate">
          <i class="bi bi-clock-history"></i> Cargando base...
        </span>

        <div class="d-flex align-items-center gap-2 text-white small bg-white bg-opacity-10 px-3 py-1 rounded-pill">
          <i class="bi bi-person-circle text-info"></i>
          <span class="fw-medium text-truncate" style="max-width: 190px;"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
        </div>

        <a href="logout.php" class="btn btn-danger btn-sm fw-semibold rounded-pill px-3">
          <i class="bi bi-box-arrow-right me-1"></i>Salir
        </a>
      </div>
    </div>
  </nav>

  <main class="container py-4">

    <!-- Tarjetas de Estadísticas -->
    <div class="row g-3 mb-4">
      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-primary border-4">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <div class="text-uppercase text-muted fw-bold small">Certificados Registrados</div>
              <div class="fs-3 fw-bold text-dark mt-1" id="statCerts">-</div>
            </div>
            <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
              <i class="bi bi-award-fill fs-3"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-success border-4">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <div class="text-uppercase text-muted fw-bold small">Trabajadores Habilitados</div>
              <div class="fs-3 fw-bold text-dark mt-1" id="statWorkers">-</div>
            </div>
            <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
              <i class="bi bi-people-fill fs-3"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-3 h-100 border-start border-info border-4">
          <div class="card-body d-flex align-items-center justify-content-between">
            <div>
              <div class="text-uppercase text-muted fw-bold small">Resultados Mostrados</div>
              <div class="fs-3 fw-bold text-dark mt-1" id="statFiltered">-</div>
            </div>
            <div class="bg-info bg-opacity-10 text-info rounded-3 p-3">
              <i class="bi bi-funnel-fill fs-3"></i>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Panel de Búsqueda y Filtros -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
      <div class="card-body p-3 p-md-4">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-lg-6">
            <label for="searchInput" class="form-label fw-semibold small text-secondary">
              Buscar por DNI o Apellidos y Nombres
            </label>
            <div class="input-group">
              <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
              <input type="text" class="form-control" id="searchInput" placeholder="Ej. 04207095 o ESPIRITU GONZALES..." autocomplete="off" />
            </div>
          </div>

          <div class="col-12 col-lg-4">
            <label for="courseFilter" class="form-label fw-semibold small text-secondary">
              Filtrar por Capacitación
            </label>
            <select class="form-select" id="courseFilter">
              <option value="">Todas las capacitaciones</option>
            </select>
          </div>

          <div class="col-12 col-lg-2 d-grid">
            <button class="btn btn-outline-secondary fw-semibold" id="btnClear" type="button">
              <i class="bi bi-eraser-fill me-1"></i>Limpiar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Banner de Trabajador Único -->
    <div class="alert alert-primary border-0 shadow-sm rounded-3 d-none align-items-center justify-content-between flex-wrap gap-2 mb-4" id="workerBanner" role="alert">
      <div class="d-flex align-items-center gap-3">
        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
          <i class="bi bi-person-vcard-fill fs-5"></i>
        </div>
        <div>
          <h2 class="h6 fw-bold mb-0 text-dark" id="workerName">TRABAJADOR</h2>
          <small class="text-primary fw-semibold" id="workerDni">DNI: -</small>
        </div>
      </div>
      <span class="badge bg-primary rounded-pill px-3 py-2 fs-6" id="workerCount">0 certificados</span>
    </div>

    <!-- Tabla de Resultados -->
    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-uppercase small text-secondary">
            <tr>
              <th class="ps-3">DNI</th>
              <th>Apellidos y Nombres</th>
              <th>Capacitación (Catálogo)</th>
              <th>Fecha</th>
              <th class="text-center">Nota</th>
              <th class="text-end pe-3">Certificado PDF</th>
            </tr>
          </thead>
          <tbody id="tableBody" class="small">
            <tr>
              <td colspan="6" class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                Cargando registros...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-3 text-muted small flex-wrap gap-2">
      <span id="limitNote"></span>
      <span>Portal interno RRHH • MYS</span>
    </div>

  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const BASE_URL = "https://systemsft.com/CERTIFICADOS%20ISEM/";
    const MAX_ROWS_DEFAULT = 100;
    let allRecords = [];

    const searchInput = document.getElementById("searchInput");
    const courseFilter = document.getElementById("courseFilter");
    const btnClear = document.getElementById("btnClear");
    const tableBody = document.getElementById("tableBody");
    const workerBanner = document.getElementById("workerBanner");

    function buildPdfUrl(dni, filename) {
      return `${BASE_URL}${encodeURIComponent(dni)}/${encodeURIComponent(filename)}`;
    }

    function normalizeText(str) {
      return (str || "")
        .toString()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase();
    }

    function populateCourses(records) {
      const courses = [...new Set(records.map(r => r.t))].sort((a, b) => a.localeCompare(b));
      courses.forEach(course => {
        const opt = document.createElement("option");
        opt.value = course;
        opt.textContent = course;
        courseFilter.appendChild(opt);
      });
    }

    function renderTable() {
      const rawQuery = searchInput.value.trim();
      const query = normalizeText(rawQuery);
      const selectedCourse = courseFilter.value;

      const filtered = allRecords.filter(r => {
        const matchCourse = !selectedCourse || r.t === selectedCourse;
        if (!matchCourse) return false;
        if (!query) return true;
        return r.d.includes(query) || normalizeText(r.n).includes(query);
      });

      document.getElementById("statFiltered").textContent = filtered.length.toLocaleString("es-PE");

      // Banner destacado cuando coincide un único trabajador
      const uniqueDnis = [...new Set(filtered.map(r => r.d))];
      if (rawQuery.length >= 4 && uniqueDnis.length === 1 && filtered.length > 0) {
        workerBanner.classList.remove("d-none");
        workerBanner.classList.add("d-flex");
        document.getElementById("workerName").textContent = filtered[0].n;
        document.getElementById("workerDni").textContent = `DNI: ${filtered[0].d}`;
        document.getElementById("workerCount").textContent = `${filtered.length} certificado(s)`;
      } else {
        workerBanner.classList.remove("d-flex");
        workerBanner.classList.add("d-none");
      }

      if (filtered.length === 0) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-3 d-block mb-2"></i>
              No se encontraron certificados con los criterios ingresados.
            </td>
          </tr>`;
        document.getElementById("limitNote").textContent = "";
        return;
      }

      const isSearching = query.length > 0 || selectedCourse !== "";
      const visibleRows = isSearching ? filtered : filtered.slice(0, MAX_ROWS_DEFAULT);

      tableBody.innerHTML = visibleRows.map(r => {
        const url = buildPdfUrl(r.d, r.a);
        return `
          <tr>
            <td class="ps-3">
              <span class="badge bg-light text-dark border font-monospace fs-6">${r.d}</span>
            </td>
            <td class="fw-semibold text-dark">${r.n}</td>
            <td>${r.t}</td>
            <td class="text-nowrap text-secondary">${r.f}</td>
            <td class="text-center">
              <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                ${r.p}
              </span>
            </td>
            <td class="text-end pe-3 text-nowrap">
              <div class="btn-group btn-group-sm" role="group">
                <a class="btn btn-primary fw-medium" href="${url}" target="_blank" rel="noopener">
                  <i class="bi bi-file-earmark-pdf-fill me-1"></i>Ver PDF
                </a>
                <a class="btn btn-outline-secondary fw-medium" href="${url}" download="${r.a}" title="Descargar PDF">
                  <i class="bi bi-download"></i>
                </a>
              </div>
            </td>
          </tr>
        `;
      }).join("");

      if (!isSearching && filtered.length > MAX_ROWS_DEFAULT) {
        document.getElementById("limitNote").textContent =
          `Mostrando los primeros ${MAX_ROWS_DEFAULT} de ${filtered.length.toLocaleString("es-PE")} certificados. Usa el buscador para filtrar toda la base.`;
      } else {
        document.getElementById("limitNote").textContent = "";
      }
    }

    async function init() {
      try {
        const res = await fetch(`datos_protegidos.php?v=${Date.now()}`);
        if (res.status === 403) {
          window.location.href = "login.php";
          return;
        }
        if (!res.ok) throw new Error("No se pudo cargar la base de certificados");
        const data = await res.json();

        allRecords = data.registros || [];
        document.getElementById("badgeUpdate").innerHTML = `<i class="bi bi-clock-history"></i> Actualizado: ${data.actualizado}`;
        document.getElementById("statCerts").textContent = (data.total_certificados || allRecords.length).toLocaleString("es-PE");
        document.getElementById("statWorkers").textContent = (data.total_trabajadores || 0).toLocaleString("es-PE");

        populateCourses(allRecords);
        renderTable();
      } catch (err) {
        tableBody.innerHTML = `
          <tr>
            <td colspan="6" class="text-center py-4 text-danger">
              <i class="bi bi-exclamation-octagon-fill me-1"></i> Error: ${err.message}
            </td>
          </tr>`;
      }
    }

    searchInput.addEventListener("input", renderTable);
    courseFilter.addEventListener("change", renderTable);
    btnClear.addEventListener("click", () => {
      searchInput.value = "";
      courseFilter.value = "";
      renderTable();
    });

    init();
  </script>
</body>
</html>